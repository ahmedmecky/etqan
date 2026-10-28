<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SopLibrary;
use App\Support\WorkCards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * بطاقات العمل المبسطة (من خطوات الإجراءات): لكل وظيفة · لموظف بالاسم · لإجراء واحد (كل الوظائف المشاركة فيه)
 */
class WorkCardController extends Controller
{
    public function index(Request $request): View
    {
        return view('workcards.index', [
            'cards' => WorkCards::all()->map(fn ($c) => $c + ['n' => WorkCards::count($c)]),
            'sops'  => SopLibrary::all()->map(fn ($s, $code) => ['code' => $code, 'title' => $s['title']])->values(),
            'mine'  => WorkCards::forUser($request->user()),
            'manage' => JobController::canManage($request->user()),
        ]);
    }

    /** بطاقة وظيفة: ?sop=SH-003 لإجراء واحد · ?user=ID باسم الموظف */
    public function show(Request $request, string $key): View
    {
        $card = WorkCards::get($key) ?? abort(404);
        $sop = $this->sopParam($request);
        $user = null;
        if ($request->filled('user')) {
            $user = User::query()->where('tenant_id', $request->user()->tenant_id)->findOrFail((int) $request->query('user'));
            abort_unless($user->id === $request->user()->id || JobController::canManage($request->user()), 403);
        }

        return $this->render($request, [[$card, WorkCards::steps($card, $sop), $user]], $sop);
    }

    /** بطاقتي */
    public function mine(Request $request): RedirectResponse
    {
        $card = WorkCards::forUser($request->user());
        if (! $card) {
            return redirect()->route('workcards.index')->with('status', 'لم تُحدد وظيفتك بعد. اختر البطاقة المناسبة من القائمة أو اطلب من مدير المخزن تحديد وظيفتك.');
        }

        return redirect()->route('workcards.show', [$card['key'], 'user' => $request->user()->id]);
    }

    /** كل بطاقات إجراء واحد: صفحة لكل وظيفة مشاركة في الخطوات */
    public function sop(Request $request, string $code): View
    {
        abort_unless(SopLibrary::get($code), 404);
        $list = WorkCards::forSop($code)->map(fn ($c) => [$c, WorkCards::steps($c, $code), null])->all();

        return $this->render($request, $list, $code);
    }

    private function sopParam(Request $request): ?string
    {
        $s = strtoupper((string) $request->query('sop', ''));

        return preg_match('/^SH-\d{3}$/', $s) && SopLibrary::get($s) ? $s : null;
    }

    private function render(Request $request, array $list, ?string $sop): View
    {
        return view('workcards.print', [
            'list'   => $list,
            'sop'    => $sop ? SopLibrary::get($sop) : null,
            'tenant' => $request->user()->tenant,
            'golden' => WorkCards::GOLDEN,
            'printedAt' => now('Africa/Cairo')->format('Y-m-d'),
        ]);
    }
}