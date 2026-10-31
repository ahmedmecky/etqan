<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditArea;
use App\Models\AuditFinding;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Support\Audits;
use App\Support\Notifier;
use App\Support\SopLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * المراجعة الخارجية: الخطة والإدارات · شاشة لكل إدارة (ما رُوجع + الملاحظات) · التقرير · رد الجهة والإجراءات · الإغلاق
 */
class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        abort_unless(Audits::canManage($u) || $u->hasRole('ceo', 'managing_director', Audits::ROLE), 403);
        $q = Audit::query()->with('findings', 'areas', 'auditor')->latest('start_date')->latest('id');
        if (Audits::isAuditor($u)) {
            $q->where('auditor_user_id', $u->id);
        }
        $list = $q->get();

        return view('audits.index', [
            'audits' => $list, 'stats' => $list->mapWithKeys(fn ($a) => [$a->id => Audits::stats($a)]),
            'manage' => Audits::canManage($u), 'next' => Audits::nextNumber($u->tenant_id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Audits::canManage($u), 403);
        $a = Audit::create($this->planData($request) + ['tenant_id' => $u->tenant_id, 'number' => Audits::nextNumber($u->tenant_id), 'status' => 'plan', 'created_by' => $u->id]);
        $this->saveFile($request, $a);

        return redirect()->route('audits.show', $a)->with('status', "تم فتح المراجعة {$a->number}. أضف الإدارات التي ستُراجع، ثم أنشئ حساب المراجع.");
    }

    public function show(Request $request, Audit $audit): View
    {
        $u = $request->user();
        abort_unless(Audits::canSee($u, $audit), 403);
        $audit->load('areas.findings', 'findings.owner', 'findings.area', 'auditor', 'creator');

        $tab = (string) $request->query('tab', 'plan');
        $area = null;
        if (str_starts_with($tab, 'area-')) {
            $area = $audit->areas->firstWhere('id', (int) substr($tab, 5));
            $tab = $area ? 'area' : 'plan';
        }
        if (! in_array($tab, ['plan', 'area', 'report', 'capa', 'log'], true)) {
            $tab = 'plan';
        }

        return view('audits.show', [
            'a' => $audit, 'tab' => $tab, 'area' => $area, 's' => Audits::stats($audit),
            'manage' => Audits::canManage($u), 'write' => Audits::canWrite($u, $audit), 'isAuditor' => Audits::isAuditor($u),
            'sops' => SopLibrary::all()->map(fn ($s, $c) => $c . ' · ' . $s['title'])->values(),
            'staff' => User::query()->with('role')->where('tenant_id', $u->tenant_id)->where('is_active', true)->get()
                ->reject(fn ($x) => Audits::isAuditor($x))->sortBy(fn ($x) => $x->displayName())->values(),
            'logs' => $tab === 'log' && $audit->auditor_user_id
                ? AuditLog::query()->where('tenant_id', $u->tenant_id)->where('user_id', $audit->auditor_user_id)->latest('at')->limit(300)->get() : collect(),
        ]);
    }

    /* ===================== الخطة ===================== */

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless(Audits::canManage($request->user()), 403);
        $audit->update($this->planData($request));
        $this->saveFile($request, $audit);

        return back()->with('status', 'تم حفظ خطة المراجعة.');
    }

    /** حساب المراجع المؤقت: إنشاء · تمديد · تغيير كلمة المرور · إيقاف */
    public function account(Request $request, Audit $audit): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Audits::canManage($u), 403);
        $acc = $audit->auditor;

        if ($request->input('do') === 'stop' && $acc) {
            $acc->forceFill(['access_until' => Carbon::yesterday(Audits::TZ)->toDateString(), 'is_active' => false])->save();

            return back()->with('status', "أُوقف حساب المراجع {$acc->displayName()}.");
        }

        $v = $request->validate([
            'name'         => [$acc ? 'nullable' : 'required', 'string', 'max:120'],
            'email'        => [$acc ? 'nullable' : 'required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($acc?->id)],
            'password'     => [$acc ? 'nullable' : 'required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'access_until' => ['required', 'date', 'after_or_equal:today'],
            'screens'      => ['nullable', 'array'],
            'screens.*'    => [Rule::in(array_keys(Audits::screens()))],
        ], [
            'name.required' => 'اكتب اسم المراجع.', 'email.required' => 'اكتب البريد الإلكتروني (اسم الدخول).', 'email.unique' => 'هذا البريد مستخدم لحساب آخر.',
            'password.required' => 'اكتب كلمة مرور مبدئية.', 'password.min' => 'كلمة المرور ٨ أحرف على الأقل.', 'password.regex' => 'كلمة المرور حروف إنجليزية وأرقام.',
            'access_until.after_or_equal' => 'تاريخ انتهاء الحساب يجب أن يكون اليوم أو بعده.',
        ]);

        if (! $acc) {
            $role = Role::query()->where('key', Audits::ROLE)->firstOrFail();
            $acc = User::create([
                'tenant_id' => $u->tenant_id, 'role_id' => $role->id, 'name' => $v['name'], 'name_ar' => $v['name'],
                'email' => strtolower($v['email']), 'password' => $v['password'], 'is_active' => true,
                'company' => $audit->company, 'access_until' => $v['access_until'],
            ]);
            $audit->update(['auditor_user_id' => $acc->id]);
            $msg = "تم إنشاء حساب المراجع: {$acc->email} · صالح حتى {$v['access_until']}. أرسل له البريد وكلمة المرور.";
        } else {
            $acc->forceFill(['access_until' => $v['access_until'], 'is_active' => true, 'company' => $audit->company]
                + (! empty($v['password']) ? ['password' => $v['password']] : []))->save();
            $msg = "تم تحديث حساب المراجع حتى {$v['access_until']}" . (! empty($v['password']) ? ' وتغيير كلمة المرور.' : '.');
        }
        // نطاق الاطلاع: الشاشات المحددة فقط (الباقي مخفي)
        $chosen = $v['screens'] ?? [];
        foreach (array_keys(Audits::screens()) as $m) {
            \App\Models\PermissionOverride::updateOrCreate(
                ['tenant_id' => $u->tenant_id, 'role_id' => null, 'user_id' => $acc->id, 'module' => $m],
                ['level' => in_array($m, $chosen, true) ? 'view' : 'none', 'updated_by' => $u->id]
            );
        }
        \App\Support\Access::forget();
        $msg .= ' نطاق الاطلاع: ' . (count($chosen) ? count($chosen) . ' شاشة.' : 'شاشة المراجعة فقط.');

        if ($audit->status === 'plan') {
            $audit->update(['status' => 'fieldwork']);
        }

        return back()->with('status', $msg);
    }

    /* ===================== الإدارات ===================== */

    public function addAreas(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless(Audits::canWrite($request->user(), $audit), 403);
        $v = $request->validate(['preset' => ['nullable', 'array'], 'preset.*' => ['string', 'max:160'], 'name' => ['nullable', 'string', 'max:160'], 'sops' => ['nullable', 'string', 'max:120']]);
        $have = $audit->areas()->pluck('name')->all();
        $sort = (int) $audit->areas()->max('sort');
        $n = 0;
        foreach ($v['preset'] ?? [] as $name) {
            if (! isset(Audits::AREAS[$name]) || in_array($name, $have, true)) {
                continue;
            }
            AuditArea::create(['tenant_id' => $audit->tenant_id, 'audit_id' => $audit->id, 'name' => $name, 'sops' => Audits::AREAS[$name], 'sort' => ++$sort]);
            $n++;
        }
        if (! empty($v['name']) && ! in_array(trim($v['name']), $have, true)) {
            $a = AuditArea::create(['tenant_id' => $audit->tenant_id, 'audit_id' => $audit->id, 'name' => trim($v['name']), 'sops' => $v['sops'] ?? null, 'sort' => ++$sort]);
            $n++;
        }

        return redirect()->route('audits.show', [$audit, 'tab' => isset($a) ? 'area-' . $a->id : 'plan'])->with('status', $n ? "تمت إضافة {$n} إدارة." : 'لم تُضف إدارات (موجودة بالفعل).');
    }

    public function updateArea(Request $request, Audit $audit, AuditArea $area): RedirectResponse
    {
        $this->sameAudit($audit, $area->audit_id);
        abort_unless(Audits::canWrite($request->user(), $audit), 403);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'sops' => ['nullable', 'string', 'max:120'], 'auditee' => ['nullable', 'string', 'max:160'],
            'audited_on' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:10000'], 'done' => ['nullable', 'boolean'],
        ]);
        $area->update($v + ['done' => $request->boolean('done')]);

        return back()->with('status', "تم حفظ «{$area->name}».");
    }

    public function destroyArea(Request $request, Audit $audit, AuditArea $area): RedirectResponse
    {
        $this->sameAudit($audit, $area->audit_id);
        abort_unless(Audits::canWrite($request->user(), $audit), 403);
        abort_if($area->findings()->exists(), 422, 'لا يمكن حذف إدارة بها ملاحظات. احذف الملاحظات أو انقلها أولاً.');
        $area->delete();

        return redirect()->route('audits.show', $audit)->with('status', 'حُذفت الإدارة.');
    }

    /* ===================== الملاحظات ===================== */

    public function addFinding(Request $request, Audit $audit): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Audits::canWrite($u, $audit), 403);
        $v = $request->validate([
            'area_id'  => ['nullable', 'integer', Rule::exists('audit_areas', 'id')->where('audit_id', $audit->id)],
            'grade'    => ['required', Rule::in(array_keys(Audits::GRADES))],
            'text'     => ['required', 'string', 'max:4000'],
            'evidence' => ['nullable', 'string', 'max:4000'],
            'ref'      => ['nullable', 'string', 'max:160'],
        ], ['text.required' => 'اكتب الملاحظة.', 'grade.required' => 'اختر درجة الملاحظة.']);
        $f = AuditFinding::create($v + ['tenant_id' => $audit->tenant_id, 'audit_id' => $audit->id, 'no' => (int) $audit->findings()->max('no') + 1,
            'status' => 'open', 'created_by' => $u->id]);

        return redirect()->to(route('audits.show', [$audit, 'tab' => $f->area_id ? 'area-' . $f->area_id : 'report']) . '#f' . $f->id)
            ->with('status', "سُجلت الملاحظة رقم {$f->no} ({$f->gradeLabel()}).");
    }

    public function updateFinding(Request $request, Audit $audit, AuditFinding $finding): RedirectResponse
    {
        $u = $request->user();
        $this->sameAudit($audit, $finding->audit_id);

        // قبل التقرير: المراجع يعدل نص الملاحظة · بعده: الإدارة ترد بالإجراء
        if ($audit->inField()) {
            abort_unless(Audits::canWrite($u, $audit), 403);
            $finding->update($request->validate([
                'grade' => ['required', Rule::in(array_keys(Audits::GRADES))], 'text' => ['required', 'string', 'max:4000'],
                'evidence' => ['nullable', 'string', 'max:4000'], 'ref' => ['nullable', 'string', 'max:160'],
            ]));

            return redirect()->to(url()->previous() . '#f' . $finding->id)->with('status', "تم حفظ الملاحظة {$finding->no}.");
        }

        abort_unless(Audits::canManage($u) && $audit->status !== 'closed', 403);
        $v = $request->validate([
            'cause' => ['nullable', 'string', 'max:4000'], 'action' => ['nullable', 'string', 'max:4000'],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $u->tenant_id)],
            'due_date' => ['nullable', 'date'], 'dev_no' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['open', 'done', 'verified'])],
        ]);
        if ($v['status'] !== 'open' && $finding->needsAction() && empty($v['action'])) {
            throw ValidationException::withMessages(['action' => 'اكتب الإجراء التصحيحي قبل إغلاق الملاحظة.']);
        }
        $finding->update($v);
        if ($audit->status === 'reported') {
            $audit->update(['status' => 'response']);
        }

        return redirect()->to(route('audits.show', [$audit, 'tab' => 'capa']) . '#f' . $finding->id)->with('status', "تم حفظ الرد على الملاحظة {$finding->no}.");
    }

    public function destroyFinding(Request $request, Audit $audit, AuditFinding $finding): RedirectResponse
    {
        $this->sameAudit($audit, $finding->audit_id);
        abort_unless(Audits::canWrite($request->user(), $audit), 403);
        $finding->delete();

        return back()->with('status', 'حُذفت الملاحظة.');
    }

    /* ===================== التقرير والإغلاق ===================== */

    public function submit(Request $request, Audit $audit): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Audits::canWrite($u, $audit), 403);
        $v = $request->validate([
            'rating' => [$audit->type === 'consult' ? 'nullable' : 'required', Rule::in(array_keys(Audits::RATINGS))], 'conclusion' => ['required', 'string', 'max:10000'],
            'action' => ['required', Rule::in(['save', 'submit'])],
        ], ['rating.required' => 'اختر التقييم العام.', 'conclusion.required' => 'اكتب خلاصة المراجعة.']);
        $submit = $v['action'] === 'submit';
        $audit->update(['rating' => $v['rating'] ?? null, 'conclusion' => $v['conclusion']] + ($submit ? ['status' => 'reported', 'reported_at' => now()] : []));

        if ($submit) {
            $s = Audits::stats($audit->fresh('findings'));
            $managers = Notifier::users($audit->tenant_id)->filter(fn ($x) => Audits::canManage($x))->reject(fn ($x) => $x->id === $u->id);
            Notifier::toUsers($managers, $audit->tenant_id, 'audit', null, 'صدر ' . Audits::reportTitle($audit->type) . ': ' . $audit->title,
                $audit->number . ' · ' . (Audits::RATINGS[$audit->rating] ?? '') . ' · ' . $s['action'] . ' ملاحظة تحتاج إجراء'
                . ($s['grades']['critical'] ? ' · ' . $s['grades']['critical'] . ' حرجة' : ''),
                route('audits.show', [$audit, 'tab' => 'capa']), 'audit-' . $audit->id, $s['grades']['critical'] > 0);
        }

        return back()->with('status', $submit ? 'أُرسل التقرير لإدارة الجودة. لا يمكن تعديل الملاحظات بعد الإرسال.' : 'حُفظت الخلاصة.');
    }

    public function reopen(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless(Audits::canManage($request->user()), 403);
        $audit->update(['status' => 'fieldwork', 'reported_at' => null, 'closed_at' => null, 'closed_by' => null]);

        return back()->with('status', 'أُعيدت المراجعة للعمل الميداني (يمكن تعديل الملاحظات).');
    }

    public function close(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless(Audits::canManage($request->user()) && in_array($audit->status, ['reported', 'response'], true), 403);
        $audit->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $request->user()->id]);
        if ($audit->auditor && $audit->auditor->access_until && $audit->auditor->access_until->isFuture()) {
            $audit->auditor->forceFill(['access_until' => Carbon::today(Audits::TZ)->toDateString()])->save();
        }

        return back()->with('status', "أُغلقت المراجعة {$audit->number}.");
    }

    public function print(Request $request, Audit $audit): View
    {
        abort_unless(Audits::canSee($request->user(), $audit), 403);
        $audit->load('areas.findings.owner', 'findings.owner', 'auditor');

        return view('audits.print', ['a' => $audit, 's' => Audits::stats($audit), 'tenant' => $request->user()->tenant,
            'printedAt' => now(Audits::TZ)->format('Y-m-d H:i'), 'printedBy' => $request->user()->displayName()]);
    }

    public function file(Request $request, Audit $audit): StreamedResponse
    {
        abort_unless(Audits::canSee($request->user(), $audit) && $audit->file_path && Storage::disk('local')->exists($audit->file_path), 404);

        return Storage::disk('local')->response($audit->file_path, $audit->file_name);
    }

    /* ===================== مساعدات ===================== */

    private function sameAudit(Audit $audit, $id): void
    {
        abort_unless((int) $id === (int) $audit->id, 404);
    }

    private function planData(Request $request): array
    {
        $v = $request->validate([
            'type' => ['required', Rule::in(array_keys(Audits::TYPES))], 'title' => ['required', 'string', 'max:200'],
            'company' => ['nullable', 'string', 'max:160'], 'lead' => ['nullable', 'string', 'max:120'], 'team' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:4000'], 'criteria' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'], 'end_date' => ['required', 'date'],
            'file' => ['nullable', 'file', 'max:20480', 'extensions:pdf,docx,doc,jpg,jpeg,png'],
        ], ['title.required' => 'اكتب عنوان المراجعة.', 'start_date.required' => 'حدد تاريخ البداية.', 'end_date.required' => 'حدد تاريخ النهاية.']);
        if ($v['end_date'] < $v['start_date']) {
            throw ValidationException::withMessages(['end_date' => 'تاريخ النهاية يجب أن يكون بعد البداية.']);
        }
        unset($v['file']);

        return $v;
    }

    private function saveFile(Request $request, Audit $audit): void
    {
        if (! $request->hasFile('file')) {
            return;
        }
        $f = $request->file('file');
        if ($audit->file_path) {
            Storage::disk('local')->delete($audit->file_path);
        }
        $path = $f->storeAs('audits/' . $audit->tenant_id, $audit->number . '-' . now()->format('YmdHis') . '.' . strtolower($f->getClientOriginalExtension()), 'local');
        $audit->update(['file_path' => $path, 'file_name' => mb_substr($f->getClientOriginalName(), 0, 250)]);
    }
}