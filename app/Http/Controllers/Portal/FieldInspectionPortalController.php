<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FormRecord;
use App\Models\InspectionDelegate;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Support\FieldCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * رابط التفتيش الميداني الشخصي (بدون تسجيل دخول)
 * المفوض يسجل فاتورة وأصنافها: التشغيلة والصلاحية على الفاتورة مقابل الفعلي، والزون
 */
class FieldInspectionPortalController extends Controller
{
    public function show(string $token): View
    {
        $d = $this->delegate($token);
        if (! $d) {
            return view('portal.field-closed');
        }

        return view('portal.field', ['d' => $d, 'tenant' => $d->tenant, 'token' => $token,
            'recent' => $this->recent($d)]);
    }

    /** بحث الأصناف (الاسم ← الموقع) */
    public function products(Request $request, string $token): JsonResponse
    {
        $d = $this->delegate($token) ?? abort(404);
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) return response()->json(['list' => []]);
        $list = Product::withoutGlobalScope('tenant')->where('tenant_id', $d->tenant_id)
            ->where('name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%')
            ->orderBy('name')->limit(20)->get(['id', 'name', 'location'])
            ->map(fn ($p) => ['i' => $p->id, 'n' => $p->name, 'l' => $p->location]);

        return response()->json(['list' => $list]);
    }

    /** بحث الصيدليات (الكود أو الاسم) */
    public function pharmacies(Request $request, string $token): JsonResponse
    {
        $d = $this->delegate($token) ?? abort(404);
        $q = trim((string) $request->query('q', ''));
        if ($q === '') return response()->json(['list' => []]);
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $list = Pharmacy::withoutGlobalScope('tenant')->where('tenant_id', $d->tenant_id)
            ->where(fn ($w) => $w->where('code', $q)->orWhere('name', 'like', $like)->orWhere('code', 'like', $q . '%'))
            ->orderBy('name')->limit(15)->get(['code', 'name', 'address'])
            ->map(fn ($p) => ['c' => $p->code, 'n' => $p->name, 'a' => $p->address]);

        return response()->json(['list' => $list]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $d = $this->delegate($token) ?? abort(404);
        if (filled($request->input('website'))) {
            return redirect()->route('field.form', $token);   // حقل مخفي للبرامج الآلية
        }

        // حذف صفوف الأصناف الفارغة تماماً
        $items = collect($request->input('items', []))->filter(fn ($it) => is_array($it) && collect($it)->except('product')->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty())->values()->all();
        $request->merge(['items' => $items]);

        $data = $request->validate([
            'invoice'           => ['required', 'string', 'max:40'],
            'pharmacy'          => ['required', 'string', 'max:160'],
            'pharmacy_code'     => ['nullable', 'string', 'max:40'],
            'items'             => ['required', 'array', 'min:1', 'max:40'],
            'items.*.name'      => ['required', 'string', 'max:200'],
            'items.*.product'   => ['nullable', 'integer'],
            'items.*.batchInv'  => ['required', 'string', 'max:40'],
            'items.*.batchReal' => ['required', 'string', 'max:40'],
            'items.*.expInv'    => ['required', 'date_format:Y-m'],
            'items.*.expReal'   => ['required', 'date_format:Y-m'],
            'items.*.zone'      => ['nullable', 'string', 'max:60'],
            'items.*.note'      => ['nullable', 'string', 'max:300'],
            'notes'             => ['nullable', 'string', 'max:1500'],
        ], [
            'invoice.required'            => 'اكتب رقم الفاتورة.',
            'pharmacy.required'           => 'اختر الصيدلية.',
            'items.required'              => 'أضف صنفاً واحداً على الأقل.',
            'items.*.name.required'       => 'اختر الصنف.',
            'items.*.batchInv.required'   => 'اكتب التشغيلة المكتوبة على الفاتورة.',
            'items.*.batchReal.required'  => 'اكتب التشغيلة الفعلية على العلبة.',
            'items.*.expInv.required'     => 'اختر الصلاحية المكتوبة على الفاتورة.',
            'items.*.expReal.required'    => 'اختر الصلاحية الفعلية على العلبة.',
            'items.*.expInv.date_format'  => 'الصلاحية: الشهر والسنة.',
            'items.*.expReal.date_format' => 'الصلاحية: الشهر والسنة.',
        ]);

        $now = Carbon::now('Africa/Cairo');
        $lines = collect($data['items'])->map(function ($it) {
            $it = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $it);
            $it['issues'] = FieldCheck::issues($it);

            return $it;
        })->values()->all();
        $bad = collect($lines)->filter(fn ($l) => $l['issues'])->count();

        $record = DB::transaction(function () use ($d, $data, $lines, $bad, $now) {
            $prefix = FieldCheck::PREFIX . '-' . $now->year . '-';
            $last = FormRecord::withoutGlobalScope('tenant')->withTrashed()->where('tenant_id', $d->tenant_id)
                ->where('form_code', FieldCheck::CODE)->where('number', 'like', $prefix . '%')->lockForUpdate()
                ->pluck('number')->map(fn ($n) => (int) substr($n, strlen($prefix)))->max() ?? 0;
            $number = $prefix . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);

            $r = FormRecord::create([
                'tenant_id' => $d->tenant_id, 'form_code' => FieldCheck::CODE, 'number' => $number,
                'title' => trim($data['invoice']) . ' · ' . trim($data['pharmacy']) . ($bad ? " · {$bad} عدم مطابقة" : ''),
                'record_date' => $now->toDateString(), 'status' => FormRecord::STATUS_DRAFT, 'created_by' => null,
                'data' => [
                    'no' => $number, 'date' => $now->toDateString(), 'time' => $now->format('H:i'),
                    'delegateId' => $d->id, 'inspector' => $d->name, 'job' => $d->job,
                    'invoice' => trim($data['invoice']), 'pharmacy' => trim($data['pharmacy']), 'pharmacyCode' => trim((string) ($data['pharmacy_code'] ?? '')),
                    'items' => $lines, 'bad' => $bad, 'notes' => trim((string) ($data['notes'] ?? '')),
                    'stage' => 'submitted', 'ip' => request()->ip(),
                ],
            ]);
            $r->logs()->create(['user_id' => null, 'action' => 'إنشاء',
                'detail' => mb_substr("تفتيش ميداني عبر رابط {$d->name}: فاتورة {$data['invoice']} · " . count($lines) . " صنف · {$bad} عدم مطابقة", 0, 500)]);

            return $r;
        });
        $d->forceFill(['last_used_at' => now()])->save();

        return redirect()->route('field.form', $token)->with('saved', ['no' => $record->number, 'n' => count($lines), 'bad' => $bad]);
    }

    /* ======================================================= */

    private function delegate(string $token): ?InspectionDelegate
    {
        $d = InspectionDelegate::withoutGlobalScope('tenant')->with('tenant')->where('token', $token)->first();

        return $d && $d->is_active && $d->tenant && $d->tenant->is_active ? $d : null;
    }

    /** آخر ما سجله نفس المفوض (للتأكد أنه حُفظ) */
    private function recent(InspectionDelegate $d)
    {
        return FormRecord::withoutGlobalScope('tenant')->where('tenant_id', $d->tenant_id)->where('form_code', FieldCheck::CODE)
            ->where('data->delegateId', $d->id)->orderByDesc('id')->limit(5)->get(['number', 'title', 'record_date', 'status']);
    }
}