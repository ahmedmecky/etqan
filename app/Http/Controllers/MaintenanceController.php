<?php

namespace App\Http\Controllers;

use App\Models\MaintAsset;
use App\Models\MaintContract;
use App\Models\MaintCount;
use App\Support\Maintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * الصيانة والأجهزة: عقود الصيانة · سجل الأجهزة بالرقم التسلسلي · الجرد الفعلي · طباعة السجل (SH-023-F01)
 */
class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        abort_unless(Maintenance::canView($u), 403, 'شاشة الصيانة والأجهزة للمدير الصيدلي ومدير الجودة ومسؤولي الأجهزة.');
        $tab = in_array($request->query('tab'), ['contracts', 'assets', 'counts'], true) ? $request->query('tab') : 'assets';
        $f = [
            'q'   => trim((string) $request->query('q', '')),
            'cat' => (string) $request->query('cat', ''),
            'ct'  => (string) $request->query('ct', ''),
            'st'  => (string) $request->query('st', ''),
            'loc' => (string) $request->query('loc', ''),
        ];

        $contracts = MaintContract::query()->withCount(['assets' => fn ($q) => $q->whereNotIn('status', ['out', 'disposed'])])
            ->orderByDesc('is_active')->orderBy('end_date')->get();
        $allAssets = MaintAsset::query()->with('contract')->orderBy('location')->orderBy('category')->orderBy('name')->get();

        $assets = $allAssets->filter(function (MaintAsset $a) use ($f) {
            if ($f['cat'] !== '' && $a->category !== $f['cat']) return false;
            if ($f['ct'] !== '' && (string) $a->contract_id !== $f['ct'] && ! ($f['ct'] === 'none' && ! $a->contract_id)) return false;
            if ($f['loc'] !== '' && $a->location !== $f['loc']) return false;
            if ($f['st'] === 'expired' && $a->state() !== 'expired') return false;
            if ($f['st'] === 'soon' && ! in_array($a->state(), ['soon', 'week'], true)) return false;
            if ($f['st'] === 'missing' && $a->count_result !== 'missing') return false;
            if ($f['st'] === 'repair' && $a->status !== 'repair') return false;
            if ($f['st'] === 'cexpired' && ! ($a->contract && $a->contract->state() === 'expired')) return false;
            if ($f['q'] !== '' && mb_stripos($a->serial . ' ' . $a->name . ' ' . $a->tag_no . ' ' . $a->location . ' ' . $a->brand . ' ' . $a->model, $f['q']) === false) return false;
            return true;
        })->values();

        $shownContracts = $contracts->filter(function (MaintContract $c) use ($f) {
            if ($f['st'] === 'expired') return $c->state() === 'expired';
            if ($f['st'] === 'soon') return in_array($c->state(), ['soon', 'week'], true);
            return true;
        })->values();

        return view('maintenance.index', [
            'tab'        => $tab,
            'f'          => $f,
            'contracts'  => $contracts,
            'shown'      => $shownContracts,
            'assets'     => $assets,
            'allCount'   => $allAssets->count(),
            'counts'     => MaintCount::query()->with('counter')->latest('date')->latest('id')->limit(24)->get(),
            'byId'       => $allAssets->keyBy('id'),
            'locations'  => $allAssets->pluck('location')->filter()->unique()->sort()->values(),
            'summary'    => Maintenance::summary(),
            'manage'     => Maintenance::canManage($u),
            'open'       => (int) $request->query('open', 0),
        ]);
    }

    /* ===================== العقود ===================== */

    public function storeContract(Request $request): RedirectResponse
    {
        $this->gate($request);
        $data = $this->contractData($request);
        $c = MaintContract::create($data + ['tenant_id' => $request->user()->tenant_id, 'updated_by' => $request->user()->id]);
        $this->saveFile($request, $c);

        return redirect()->route('maint.index', ['tab' => 'contracts', 'open' => $c->id])->with('status', "تمت إضافة عقد {$c->company}.");
    }

    public function updateContract(Request $request, MaintContract $contract): RedirectResponse
    {
        $this->gate($request);
        $data = $this->contractData($request);
        if ($data['end_date'] !== $contract->end_date?->toDateString() || (int) $data['alert_days'] !== (int) $contract->alert_days) {
            $data['alert_stage'] = null;                    // تاريخ جديد = تنبيه جديد
        }
        $contract->update($data + ['updated_by' => $request->user()->id]);
        $this->saveFile($request, $contract);

        return redirect()->route('maint.index', ['tab' => 'contracts', 'open' => $contract->id])->with('status', "تم حفظ عقد {$contract->company}.");
    }

    /** تجديد: عقد جديد بنفس البيانات وتواريخ جديدة · الأجهزة تنتقل له · القديم يُغلق */
    public function renew(Request $request, MaintContract $contract): RedirectResponse
    {
        $this->gate($request);
        $v = $request->validate([
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['required', 'date'],
            'contract_no' => ['nullable', 'string', 'max:60'],
            'file'        => $this->fileRule(),
        ], ['end_date.required' => 'حدد تاريخ انتهاء العقد الجديد.']);
        $this->checkDates($v);

        $new = MaintContract::create([
            'tenant_id' => $contract->tenant_id, 'kind' => $contract->kind, 'company' => $contract->company,
            'contract_no' => $v['contract_no'] ?? $contract->contract_no, 'contact' => $contract->contact, 'phone' => $contract->phone,
            'start_date' => $v['start_date'] ?? $contract->end_date?->copy()->addDay(), 'end_date' => $v['end_date'],
            'alert_days' => $contract->alert_days, 'visits' => $contract->visits, 'scope' => $contract->scope,
            'renewed_from_id' => $contract->id, 'is_active' => true, 'updated_by' => $request->user()->id,
        ]);
        $this->saveFile($request, $new);
        MaintAsset::query()->where('contract_id', $contract->id)->update(['contract_id' => $new->id]);
        $contract->update(['is_active' => false, 'updated_by' => $request->user()->id]);

        return redirect()->route('maint.index', ['tab' => 'contracts', 'open' => $new->id])
            ->with('status', "تم تجديد عقد {$contract->company} حتى " . $new->end_date->format('Y-m-d') . ' ونُقلت أجهزته للعقد الجديد.');
    }

    public function toggleContract(Request $request, MaintContract $contract): RedirectResponse
    {
        $this->gate($request);
        $contract->update(['is_active' => ! $contract->is_active, 'alert_stage' => null, 'updated_by' => $request->user()->id]);

        return back()->with('status', $contract->is_active ? "أُعيد تفعيل عقد {$contract->company}." : "أُوقف عقد {$contract->company} (لا تصل عنه إشعارات).");
    }

    public function file(Request $request, MaintContract $contract): StreamedResponse
    {
        abort_unless(Maintenance::canView($request->user()), 403);
        abort_unless($contract->file_path && Storage::disk('local')->exists($contract->file_path), 404, 'لا يوجد ملف مرفق لهذا العقد.');

        return Storage::disk('local')->download($contract->file_path, $contract->file_name);
    }

    /* ===================== الأجهزة ===================== */

    public function storeAsset(Request $request): RedirectResponse
    {
        $this->gate($request);
        $data = $this->assetData($request);
        $a = MaintAsset::create($data + ['tenant_id' => $request->user()->tenant_id, 'updated_by' => $request->user()->id]);

        return redirect()->route('maint.index', ['tab' => 'assets', 'q' => $a->serial])->with('status', "تمت إضافة {$a->name} ({$a->serial}).");
    }

    public function updateAsset(Request $request, MaintAsset $asset): RedirectResponse
    {
        $this->gate($request);
        $data = $this->assetData($request, $asset);
        if (($data['due_date'] ?? null) !== $asset->due_date?->toDateString()) {
            $data['alert_stage'] = null;
        }
        $asset->update($data + ['updated_by' => $request->user()->id]);

        return back()->with('status', "تم حفظ {$asset->name} ({$asset->serial}).");
    }

    public function destroyAsset(Request $request, MaintAsset $asset): RedirectResponse
    {
        $this->gate($request);
        $asset->delete();

        return back()->with('status', "حُذف الجهاز {$asset->serial}. للأجهزة المستبعدة فعلياً استخدم الحالة «مُكهّن» بدلاً من الحذف.");
    }

    /* ===================== الجرد الفعلي ===================== */

    public function count(Request $request): View
    {
        abort_unless(Maintenance::canView($request->user()), 403);

        return view('maintenance.count', [
            'assets' => MaintAsset::query()->whereNotIn('status', ['disposed'])->orderBy('location')->orderBy('category')->orderBy('name')->get()
                ->groupBy(fn ($a) => $a->location ?: 'بدون موقع'),
            'manage' => Maintenance::canManage($request->user()),
        ]);
    }

    public function saveCount(Request $request): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Maintenance::canView($u), 403);
        $v = $request->validate([
            'r'     => ['required', 'array'],
            'r.*'   => [Rule::in(['ok', 'missing', 'damaged', ''])],
            'loc'   => ['nullable', 'array'],
            'loc.*' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['r.required' => 'اختر نتيجة جهاز واحد على الأقل.']);

        $assets = MaintAsset::query()->whereIn('id', array_keys($v['r']))->get()->keyBy('id');
        $today = Maintenance::today()->toDateString();
        $results = [];
        $n = ['ok' => 0, 'missing' => 0, 'damaged' => 0];
        foreach ($v['r'] as $id => $r) {
            $a = $assets->get((int) $id);
            if (! $a || $r === '' || $r === null) {
                continue;
            }
            $loc = trim((string) ($v['loc'][$id] ?? ''));
            $row = ['r' => $r];
            $upd = ['count_result' => $r, 'updated_by' => $u->id];
            if ($r !== 'missing') {
                $upd['last_counted'] = $today;
            }
            if ($loc !== '' && $loc !== (string) $a->location) {
                $row['from'] = $a->location;
                $row['loc'] = $loc;
                $upd['location'] = $loc;
            }
            if ($r === 'damaged' && $a->status === 'active') {
                $upd['status'] = 'repair';
            }
            $a->update($upd);
            $results[$a->id] = $row;
            $n[$r]++;
        }
        abort_if(! $results, 422, 'لم تُحدد نتيجة لأي جهاز.');

        MaintCount::create([
            'tenant_id' => $u->tenant_id, 'date' => $today, 'results' => $results, 'found' => $n['ok'], 'missing' => $n['missing'],
            'damaged' => $n['damaged'], 'notes' => $v['notes'] ?? null, 'counted_by' => $u->id,
        ]);

        return redirect()->route('maint.index', ['tab' => 'counts'])
            ->with('status', "تم حفظ الجرد: {$n['ok']} موجود · {$n['missing']} غير موجود · {$n['damaged']} تالف.");
    }

    /* ===================== الطباعة ===================== */

    public function print(Request $request): View
    {
        abort_unless(Maintenance::canView($request->user()), 403);

        return view('maintenance.print', [
            'tenant'    => $request->user()->tenant,
            'contracts' => MaintContract::query()->where('is_active', true)->withCount('assets')->orderBy('end_date')->get(),
            'assets'    => MaintAsset::query()->with('contract')->whereNotIn('status', ['disposed'])->orderBy('category')->orderBy('location')->orderBy('name')->get(),
            'printedAt' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'printedBy' => $request->user()->displayName(),
        ]);
    }

    /* ===================== مساعدات ===================== */

    private function gate(Request $request): void
    {
        abort_unless(Maintenance::canManage($request->user()), 403, 'الإضافة والتعديل لمدير المخزن والجودة والمدير الصيدلي ومسؤولي IT والسلامة.');
    }

    private function fileRule(): array
    {
        return ['nullable', 'file', 'max:20480', 'extensions:pdf,jpg,jpeg,png,docx,doc'];
    }

    private function contractData(Request $request): array
    {
        $v = $request->validate([
            'kind'        => ['required', Rule::in(array_keys(Maintenance::KINDS))],
            'company'     => ['required', 'string', 'max:160'],
            'contract_no' => ['nullable', 'string', 'max:60'],
            'contact'     => ['nullable', 'string', 'max:120'],
            'phone'       => ['nullable', 'string', 'max:40'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['required', 'date'],
            'alert_days'  => ['required', 'integer', 'between:1,180'],
            'visits'      => ['nullable', 'string', 'max:120'],
            'scope'       => ['nullable', 'string', 'max:2000'],
            'file'        => $this->fileRule(),
        ], [
            'company.required'        => 'اكتب اسم الشركة.',
            'end_date.required'       => 'حدد تاريخ انتهاء العقد.',
        ]);
        $this->checkDates($v);
        unset($v['file']);
        $v['company'] = trim($v['company']);

        return $v;
    }

    private function checkDates(array $v): void
    {
        if (! empty($v['start_date']) && $v['end_date'] < $v['start_date']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية.']);
        }
    }

    private function saveFile(Request $request, MaintContract $c): void
    {
        if (! $request->hasFile('file')) {
            return;
        }
        $file = $request->file('file');
        if ($c->file_path) {
            Storage::disk('local')->delete($c->file_path);
        }
        $path = $file->storeAs('maintenance/' . $c->tenant_id, 'contract-' . $c->id . '-' . now()->format('YmdHis') . '.' . strtolower($file->getClientOriginalExtension()), 'local');
        $c->update(['file_path' => $path, 'file_name' => mb_substr($file->getClientOriginalName(), 0, 250)]);
    }

    private function assetData(Request $request, ?MaintAsset $asset = null): array
    {
        $tenantId = $request->user()->tenant_id;
        $v = $request->validate([
            'category'     => ['required', Rule::in(array_keys(Maintenance::CATEGORIES))],
            'name'         => ['required', 'string', 'max:160'],
            'serial'       => ['required', 'string', 'max:80',
                Rule::unique('maint_assets', 'serial')->where('tenant_id', $tenantId)->ignore($asset?->id)],
            'tag_no'       => ['nullable', 'string', 'max:40'],
            'brand'        => ['nullable', 'string', 'max:80'],
            'model'        => ['nullable', 'string', 'max:80'],
            'location'     => ['nullable', 'string', 'max:120'],
            'contract_id'  => ['nullable', 'integer', Rule::exists('maint_contracts', 'id')->where('tenant_id', $tenantId)],
            'due_date'     => ['nullable', 'date'],
            'last_service' => ['nullable', 'date'],
            'status'       => ['required', Rule::in(array_keys(Maintenance::STATUSES))],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required'   => 'اكتب اسم الجهاز.',
            'serial.required' => 'اكتب الرقم التسلسلي (Serial).',
            'serial.unique'   => 'هذا الرقم التسلسلي مسجل لجهاز آخر.',
        ]);
        $v['serial'] = strtoupper(trim($v['serial']));
        $v['name'] = trim($v['name']);
        $v['due_date'] = $v['due_date'] ?? null;

        return $v;
    }
}