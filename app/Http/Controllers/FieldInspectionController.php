<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Models\InspectionDelegate;
use App\Support\FieldCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * التفتيش الميداني بالتفويض (SH-012-F04)
 *   المفوضون وروابطهم الشخصية · سجل الفحوصات · مراجعة المدير الصيدلي
 */
class FieldInspectionController extends Controller
{
    /** السجل + المفوضون */
    public function index(Request $request): View
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : Carbon::today('Africa/Cairo')->format('Y-m');
        $who = (int) $request->query('who', 0);
        $only = $request->query('only', '');

        $all = FormRecord::query()->where('form_code', FieldCheck::CODE)->where('record_date', 'like', $month . '%')
            ->orderByDesc('id')->get();
        $rows = $all->filter(fn ($r) => (! $who || (int) ($r->data['delegateId'] ?? 0) === $who)
            && ($only !== 'bad' || ($r->data['bad'] ?? 0) > 0)
            && ($only !== 'open' || $r->status !== FormRecord::STATUS_FINAL))->values();

        $items = $all->flatMap(fn ($r) => $r->data['items'] ?? []);
        $zones = $items->groupBy(fn ($i) => trim((string) ($i['zone'] ?? '')) ?: '—')
            ->map(fn ($g, $z) => ['zone' => $z, 'n' => $g->count(), 'bad' => $g->filter(fn ($i) => ! empty($i['issues']))->count()])
            ->sortByDesc('bad')->values();

        return view('inspections.field', [
            'rows' => $rows, 'month' => $month, 'who' => $who, 'only' => $only,
            'delegates' => InspectionDelegate::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'canManage' => FieldCheck::canManage($request->user()),
            'stats' => [
                'checks' => $all->count(), 'items' => $items->count(),
                'bad' => $items->filter(fn ($i) => ! empty($i['issues']))->count(),
                'open' => $all->where('status', '!=', FormRecord::STATUS_FINAL)->count(),
                'accuracy' => $items->count() ? round(($items->count() - $items->filter(fn ($i) => ! empty($i['issues']))->count()) / $items->count() * 100, 1) : null,
            ],
            'zones' => $zones,
        ]);
    }

    public function show(FormRecord $record): View
    {
        abort_unless($record->form_code === FieldCheck::CODE, 404);
        $record->loadMissing('logs.user');

        return view('inspections.field-show', ['r' => $record, 'd' => $record->data ?? [],
            'canManage' => FieldCheck::canManage(request()->user())]);
    }

    /** مراجعة المدير الصيدلي وإغلاق الفحص */
    public function review(Request $request, FormRecord $record): RedirectResponse
    {
        abort_unless($record->form_code === FieldCheck::CODE, 404);
        abort_unless(FieldCheck::canManage($request->user()), 403);
        $d = $record->data ?? [];
        $v = $request->validate([
            'note'  => [($d['bad'] ?? 0) > 0 ? 'required' : 'nullable', 'string', 'max:1500'],
            'devNo' => ['nullable', 'string', 'max:40'],
        ], ['note.required' => 'يوجد عدم مطابقة: اكتب الإجراء المتخذ (تصحيح الفاتورة · إبلاغ العميل · انحراف).']);

        $d['review'] = ['by' => $request->user()->displayName(), 'at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'note' => trim((string) ($v['note'] ?? '')), 'devNo' => trim((string) ($v['devNo'] ?? ''))];
        $d['stage'] = 'reviewed';
        $record->update(['data' => $d, 'status' => FormRecord::STATUS_FINAL, 'finalized_by' => $request->user()->id, 'finalized_at' => now()]);
        $record->log('مراجعة وإغلاق', trim(($v['note'] ?? '') . (! empty($v['devNo']) ? ' · ' . $v['devNo'] : '')) ?: 'مطابق');

        return redirect()->route('field.index')->with('status', "تمت مراجعة {$record->number}.");
    }

    /* ===================== المفوضون ===================== */

    public function storeDelegate(Request $request): RedirectResponse
    {
        abort_unless(FieldCheck::canManage($request->user()), 403);
        $v = $request->validate(['name' => ['required', 'string', 'max:120'], 'job' => ['nullable', 'string', 'max:120']],
            ['name.required' => 'اكتب اسم المفوض.']);
        $d = InspectionDelegate::create($v + ['tenant_id' => $request->user()->tenant_id, 'token' => InspectionDelegate::newToken(), 'created_by' => $request->user()->id]);

        return back()->with('status', "تم إنشاء رابط {$d->name}. انسخه وأرسله له.")->with('newLink', $d->id);
    }

    public function toggleDelegate(Request $request, InspectionDelegate $delegate): RedirectResponse
    {
        abort_unless(FieldCheck::canManage($request->user()), 403);
        $delegate->update(['is_active' => ! $delegate->is_active]);

        return back()->with('status', $delegate->is_active ? "أُعيد تفعيل رابط {$delegate->name}." : "تم إيقاف رابط {$delegate->name}. لن يعمل حتى إعادة التفعيل.");
    }

    public function renewDelegate(Request $request, InspectionDelegate $delegate): RedirectResponse
    {
        abort_unless(FieldCheck::canManage($request->user()), 403);
        $delegate->update(['token' => InspectionDelegate::newToken(), 'is_active' => true]);

        return back()->with('status', "رابط جديد لـ {$delegate->name}. الرابط القديم لم يعد يعمل.")->with('newLink', $delegate->id);
    }

    /** للرئيسية: فحوصات بها عدم مطابقة لم تُراجع */
    public static function pending(\App\Models\User $u): int
    {
        if (! $u->tenant_id || ! FieldCheck::canManage($u)) return 0;

        return FormRecord::query()->where('form_code', FieldCheck::CODE)->where('status', FormRecord::STATUS_DRAFT)
            ->where('data->bad', '>', 0)->count();
    }
}