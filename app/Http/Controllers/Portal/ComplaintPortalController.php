<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FormRecord;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Support\FormRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * رابط شكاوى الصيدليات (بدون تسجيل دخول)
 * الشكوى تُحفظ في نظام الشكاوى (SH-007) بمرحلة «بانتظار التصنيف»
 * ويتابعها المدير الصيدلي
 */
class ComplaintPortalController extends Controller
{
    public const ISSUES = [
        'نقص في الكمية',
        'صنف تالف أو مكسور',
        'صنف خطأ (غير المطلوب)',
        'صلاحية قريبة أو منتهية',
        'مشكلة في الجودة (لون · شكل · رائحة)',
        'فرق في السعر',
        'أخرى',
    ];

    public const DEPTS = [
        'sales'      => 'المبيعات',
        'accounting' => 'الحسابات',
        'delivery'   => 'التوصيل',
        'other'      => 'أخرى',
    ];

    public function show(string $token): View
    {
        $tenant = $this->tenant($token);
        if (! $tenant->complaint_portal) {
            return view('portal.closed', ['tenant' => $tenant]);
        }

        $products = Product::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'price'])
            ->map(fn ($p) => ['i' => $p->id, 'n' => $p->name, 'p' => $p->price !== null ? (float) $p->price : null])
            ->values();

        return view('portal.complaint', [
            'tenant'   => $tenant,
            'token'    => $token,
            'products' => $products,
            'issues'   => self::ISSUES,
            'depts'    => self::DEPTS,
        ]);
    }

    /** إظهار اسم الصيدلية عند كتابة الكود */
    public function pharmacy(Request $request, string $token): JsonResponse
    {
        $tenant = $this->tenant($token);
        $code = trim((string) $request->query('code', ''));
        $ph = $code === '' ? null : $this->findPharmacy($tenant, $code);

        return response()->json($ph
            ? ['found' => true, 'name' => $ph->name, 'address' => $ph->address]
            : ['found' => false]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $tenant = $this->tenant($token);
        abort_unless($tenant->complaint_portal, 404);

        // حقل مخفي: البرامج الآلية تملؤه
        if (filled($request->input('website'))) {
            return redirect()->route('portal.complaint', $token);
        }

        $request->merge(['mobile' => preg_replace('/\D+/', '', (string) $request->input('mobile'))]);

        // شكوى الخدمة لا تحتاج أصنافاً ولا فاتورة، وشكوى الصنف لا تحتاج قسماً
        if ($request->input('about') === 'service') {
            $request->request->remove('items');
            $request->request->remove('invoice');
        } elseif ($request->input('about') === 'product') {
            $request->request->remove('dept');
        }

        $data = $request->validate([
            'code'            => ['required', 'string', 'max:40'],
            'pharmacist'      => ['required', 'string', 'max:120'],
            'mobile'          => ['required', 'regex:/^01[0125]\d{8}$/'],
            'about'           => ['required', Rule::in(['product', 'service'])],
            'invoice'         => ['required_if:about,product', 'nullable', 'string', 'max:40'],
            'items'           => ['required_if:about,product', 'nullable', 'array', 'max:10'],
            'items.*.product' => ['required', 'integer'],
            'items.*.issue'   => ['required', Rule::in(self::ISSUES)],
            'items.*.qty'     => ['nullable', 'integer', 'min:1', 'max:100000'],
            'dept'            => ['required_if:about,service', 'nullable', Rule::in(array_keys(self::DEPTS))],
            'details'         => ['required_if:about,service', 'nullable', 'string', 'max:2000'],
        ], [
            'code.required'          => 'اكتب كود الصيدلية المكتوب على الفاتورة.',
            'pharmacist.required'    => 'اكتب اسم الصيدلي.',
            'mobile.required'        => 'اكتب رقم الموبايل.',
            'mobile.regex'           => 'رقم الموبايل غير صحيح (١١ رقماً يبدأ بـ 01).',
            'about.required'         => 'اختر: الشكوى عن صنف أم عن خدمة.',
            'invoice.required_if'    => 'اكتب رقم الفاتورة.',
            'items.required_if'      => 'اختر الصنف.',
            'items.*.product.required' => 'اختر الصنف من القائمة.',
            'items.*.issue.required' => 'اختر نوع المشكلة لكل صنف.',
            'dept.required_if'       => 'اختر القسم.',
            'details.required_if'    => 'اكتب تفاصيل الشكوى.',
        ]);

        $ph = $this->findPharmacy($tenant, $data['code']);
        if (! $ph) {
            return back()->withInput()->withErrors(['code' => 'الكود غير مسجل، راجع الكود المكتوب على الفاتورة.']);
        }

        $isProduct = $data['about'] === 'product';
        $lines = [];
        if ($isProduct) {
            $ids = collect($data['items'])->pluck('product')->unique();
            $products = Product::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->whereIn('id', $ids)->get()->keyBy('id');
            foreach ($data['items'] as $it) {
                $p = $products->get((int) $it['product']);
                if (! $p) {
                    return back()->withInput()->withErrors(['items' => 'صنف غير موجود في القائمة. اختر من القائمة.']);
                }
                $lines[] = ['p' => $p, 'issue' => $it['issue'], 'qty' => $it['qty'] ?? null];
            }
        }

        $record = DB::transaction(fn () => $this->createRecord($tenant, $ph, $data, $lines));

        return redirect()->route('portal.thanks', $token)->with('complaint_no', $record->number);
    }

    public function thanks(string $token): View|RedirectResponse
    {
        $tenant = $this->tenant($token);
        $no = session('complaint_no');
        if (! $no) {
            return redirect()->route('portal.complaint', $token);
        }

        return view('portal.thanks', ['tenant' => $tenant, 'token' => $token, 'number' => $no]);
    }

    /* ======================================================= */

    private function tenant(string $token): Tenant
    {
        return Tenant::where('complaint_token', $token)->where('is_active', true)->firstOrFail();
    }

    private function findPharmacy(Tenant $tenant, string $code): ?Pharmacy
    {
        return Pharmacy::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('code', trim($code))
            ->first();
    }

    /** إنشاء سجل الشكوى بنفس بنية شاشة الشكاوى */
    private function createRecord(Tenant $tenant, Pharmacy $ph, array $d, array $lines): FormRecord
    {
        $form  = FormRegistry::get('complaint');
        $now   = Carbon::now('Africa/Cairo');
        $isProduct = $d['about'] === 'product';

        // المدير الصيدلي يتابع شكاوى الرابط
        $owner = User::query()
            ->with('role')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get()
            ->first(fn ($u) => $u->hasRole('pharmacist_manager'));

        // نوع الشكوى المقترح (تراجعه الجودة عند التصنيف)
        $issues = collect($lines)->pluck('issue');
        $type = $isProduct
            ? ($issues->contains('مشكلة في الجودة (لون · شكل · رائحة)') ? 'جودة منتج'
                : ($issues->every(fn ($i) => $i === 'فرق في السعر') ? 'تجارية (سعر · فاتورة · تحصيل)' : 'خدمة توزيع (تأخير · تلف · خطأ تحضير)'))
            : match ($d['dept']) {
                'delivery'   => 'خدمة توزيع (تأخير · تلف · خطأ تحضير)',
                'sales', 'accounting' => 'تجارية (سعر · فاتورة · تحصيل)',
                default      => '',
            };
        $dept = $isProduct ? 'المخزن' : match ($d['dept']) {
            'sales' => 'المبيعات', 'accounting' => 'الحسابات', 'delivery' => 'التوزيع والحركة', default => '',
        };

        $details = $isProduct
            ? "شكوى عن أصناف · فاتورة رقم {$d['invoice']}\n" . collect($lines)->map(fn ($l, $i) => ($i + 1) . '. ' . $l['p']->name
                . ' · ' . $l['issue'] . ($l['qty'] ? ' · الكمية ' . $l['qty'] : ''))->implode("\n")
                . (filled($d['details'] ?? null) ? "\n" . trim($d['details']) : '')
            : 'شكوى عن خدمة · ' . self::DEPTS[$d['dept']] . "\n" . trim((string) $d['details']);

        $data = [
            'no' => '', 'autoNo' => false,
            'date' => $now->toDateString(), 'time' => $now->format('H:i'),
            'channel' => 'رابط الصيدليات', 'receiver' => 'رابط الصيدليات',
            'customer' => $ph->name, 'custCode' => $ph->code, 'custType' => 'قطاعي',
            'phone' => $d['mobile'], 'invoice' => $isProduct ? $d['invoice'] : '',
            'details' => $details,
            'prods' => collect($lines)->map(fn ($l) => [
                'id' => bin2hex(random_bytes(4)), 'name' => $l['p']->name, 'batch' => '', 'exp' => '',
                'qty' => $l['qty'] ? (string) $l['qty'] : '', 'sample' => '', 'note' => $l['issue'],
            ])->values()->all(),
            'type' => $type, 'dept' => $dept, 'owner' => $owner?->displayName() ?? '',
            'prio' => '', 'closeDue' => '', 'closeDueAuto' => true,
            'stage' => 'reported', 'status' => 'draft',
            'source' => 'portal',
            'portal' => [
                'pharmacist' => trim($d['pharmacist']),
                'mobile'     => $d['mobile'],
                'code'       => $ph->code,
                'pharmacy'   => $ph->name,
                'address'    => $ph->address,
                'about'      => $d['about'],
                'dept'       => $isProduct ? null : self::DEPTS[$d['dept']],
                'at'         => $now->format('Y-m-d H:i'),
                'ip'         => request()->ip(),
            ],
            'sign' => ['report' => ['label' => 'أرسلها الصيدلي عبر الرابط', 'by' => trim($d['pharmacist']) . ' · ' . $d['mobile'], 'at' => $now->format('Y-m-d H:i')]],
        ];
        if (! $data['prods']) {
            $data['prods'] = [['id' => bin2hex(random_bytes(4)), 'name' => '', 'batch' => '', 'exp' => '', 'qty' => '', 'sample' => '', 'note' => '']];
        }

        // رقم مسلسل CMP-YYYY-### لهذا المخزن
        $prefix = 'CMP-' . $now->year . '-';
        $last = FormRecord::withoutGlobalScope('tenant')->withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('form_code', $form['code'])
            ->where('number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->pluck('number')
            ->map(fn ($n) => (int) substr($n, strlen($prefix)))
            ->max() ?? 0;
        $number = $prefix . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
        $data['no'] = $number;

        $record = FormRecord::create([
            'tenant_id'   => $tenant->id,
            'form_code'   => $form['code'],
            'number'      => $number,
            'title'       => $ph->name . ' · ' . mb_substr(str_replace("\n", ' ', $details), 0, 60),
            'record_date' => $now->toDateString(),
            'status'      => FormRecord::STATUS_DRAFT,
            'data'        => $data,
            'created_by'  => null,
        ]);

        $record->logs()->create([
            'user_id'        => null,
            'action'         => 'إنشاء',
            'detail'         => mb_substr("شكوى من رابط الصيدليات: {$ph->name} ({$ph->code}) · {$d['pharmacist']} · {$d['mobile']}", 0, 500),
        ]);

        return $record;
    }
}