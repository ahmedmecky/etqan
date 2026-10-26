<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Models\Role;
use App\Models\User;
use App\Support\FormRegistry;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * حفظ وقراءة سجلات النماذج (محاضر الاجتماعات · …) لمخزن المستخدم فقط
 */
class FormRecordController extends Controller
{
    private const MESSAGES = [
        'number.required' => 'رقم المحضر مطلوب.',
        'number.max'      => 'رقم المحضر طويل جداً.',
        'number.unique'   => 'رقم المحضر مستخدم في محضر آخر.',
        'record_date.date' => 'التاريخ غير صحيح.',
        'status.in'       => 'حالة غير صحيحة.',
        'data.required'   => 'لا توجد بيانات للحفظ.',
    ];

    /** صفحة الشاشة */
    public function page(Request $request, string $form): View
    {
        $f      = FormRegistry::get($form);
        $user   = $request->user()->loadMissing('role', 'tenant');
        $tenant = $user->tenant;

        // مستخدمو المخزن (لاقتراح الأسماء في الحضور)
        $users = User::query()
            ->with('role')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->displayName(),
                'role' => $u->role?->name_ar,
            ])
            ->sortBy('name')
            ->values()
            ->all();

        // الأدوار الوظيفية + أدوار إضافية للاجتماعات
        $roles = Role::query()
            ->where('level', '>=', Role::LEVEL_CLIENT_ADMIN)
            ->orderBy('sort')
            ->pluck('name_ar')
            ->all();
        array_unshift($roles, 'رئيس مجلس الإدارة');
        $roles[] = 'أخرى';

        $boot = [
            'form'   => $f['key'],
            'code'   => $f['code'],
            'title'  => $f['title'],
            'urls'   => array_merge([
                'records'   => route('forms.records.index', $f['key']),
                'register'  => isset($f['register']) ? route($f['register']) : null,
                'deviation' => route('forms.page', 'deviation'),
                'devlinks'  => route('deviations.links'),
            ], array_map(fn ($r) => is_array($r) ? route(...$r) : route($r), $f['urls'] ?? [])),
            'tenant' => [
                'code'    => $tenant->code,
                'name_ar' => $tenant->name_ar,
                'name_en' => $tenant->name_en,
                'logo'    => $tenant->logoUrl(),
            ],
            'me'     => [
                'id'   => $user->id,
                'name' => $user->displayName(),
                'role' => $user->role?->name_ar,
            ],
            'can'    => [
                'reopen'   => $this->canReopen($user),
                'finalize' => FormRegistry::canFinalize($f, $user),
                'steps'    => FormRegistry::stepRights($f, $user),
            ],
            'users'    => $users,
            'roles'    => array_values(array_unique($roles)),
            'settings' => Settings::forTenant($tenant->id),
        ];

        return view($f['view'], ['boot' => $boot, 'form' => $f]);
    }

    /** قائمة السجلات */
    public function index(string $form): JsonResponse
    {
        $f = FormRegistry::get($form);

        $list = $this->visible(FormRecord::query()->form($f['code']), $f, request()->user())
            ->with('creator')
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FormRecord $r) => [
                'id'          => $r->id,
                'number'      => $r->number,
                'title'       => $r->title,
                'record_date' => $r->record_date?->format('Y-m-d'),
                'status'      => $r->status,
                'stage'       => is_array($r->data) ? ($r->data['stage'] ?? null) : null,   // مرحلة العمل (للقائمة)
                'created_by'  => $r->creator?->displayName(),
            ]);

        return response()->json(['records' => $list]);
    }

    /** سجل واحد */
    public function show(string $form, int $record): JsonResponse
    {
        return response()->json(['record' => $this->present($this->find($form, $record))]);
    }

    /** إنشاء */
    public function store(Request $request, string $form): JsonResponse
    {
        $f    = FormRegistry::get($form);
        $data = $this->validated($request, $f['code'], null);
        $user = $request->user();
        $final = $data['status'] === FormRecord::STATUS_FINAL;

        if ($final && ! FormRegistry::canFinalize($f, $user)) {
            return response()->json(['message' => 'اعتماد هذا النموذج غير متاح لدورك الوظيفي.'], 403);
        }

        $record = FormRecord::create([
            'tenant_id'    => $user->tenant_id,
            'form_code'    => $f['code'],
            'number'       => $data['number'],
            'title'        => $data['title'] ?? null,
            'record_date'  => $data['record_date'] ?? null,
            'status'       => $data['status'],
            'data'         => $this->rawData($request),
            'created_by'   => $user->id,
            'updated_by'   => $user->id,
            'finalized_by' => $final ? $user->id : null,
            'finalized_at' => $final ? now() : null,
        ]);

        $record->log('إنشاء', 'أُنشئ ' . $f['title'] . ' رقم ' . $record->number);
        if ($final) {
            $record->log('اعتماد', 'اعتُمد وأُقفل للتعديل');
        }

        return response()->json(['record' => $this->present($record->fresh())], 201);
    }

    /** تعديل */
    public function update(Request $request, string $form, int $record): JsonResponse
    {
        $f = FormRegistry::get($form);
        $r = $this->find($form, $record);

        if ($r->isFinal()) {
            return response()->json(['message' => 'هذا السجل معتمد ولا يمكن تعديله. أعد فتحه أولاً.'], 422);
        }

        $data  = $this->validated($request, $f['code'], $r->id);
        $user  = $request->user();
        $final = $data['status'] === FormRecord::STATUS_FINAL;

        if ($final && ! FormRegistry::canFinalize($f, $user)) {
            return response()->json(['message' => 'اعتماد هذا النموذج غير متاح لدورك الوظيفي.'], 403);
        }

        $r->update([
            'number'       => $data['number'],
            'title'        => $data['title'] ?? null,
            'record_date'  => $data['record_date'] ?? null,
            'status'       => $data['status'],
            'data'         => $this->rawData($request),
            'updated_by'   => $user->id,
            'finalized_by' => $final ? $user->id : null,
            'finalized_at' => $final ? now() : null,
        ]);

        $stage = $data['data']['stage'] ?? null;
        $r->log(
            $final ? 'اعتماد' : ($stage === 'submitted' ? 'إرسال' : ($stage === 'evaluated' ? 'تقييم' : 'حفظ')),
            $final ? 'اعتُمد وأُقفل للتعديل' : ($stage === 'submitted' ? 'أُرسل للمراجعة والقرار' : ($stage === 'evaluated' ? 'قُيّم وأُرسل للاعتماد' : 'حُفظ كمسودة'))
        );

        return response()->json(['record' => $this->present($r->fresh())]);
    }

    /** إعادة فتح سجل معتمد */
    public function reopen(Request $request, string $form, int $record): JsonResponse
    {
        $r = $this->find($form, $record);

        if (! $this->canReopen($request->user())) {
            return response()->json(['message' => 'إعادة فتح السجل المعتمد متاحة لمدير المخزن ومدير إدارة الجودة فقط.'], 403);
        }

        $r->update([
            'status'       => FormRecord::STATUS_DRAFT,
            'updated_by'   => $request->user()->id,
            'finalized_by' => null,
            'finalized_at' => null,
        ]);
        $r->log('إعادة فتح', 'أُعيد فتح السجل للتعديل');

        return response()->json(['record' => $this->present($r->fresh())]);
    }

    /** حذف مسودة */
    public function destroy(Request $request, string $form, int $record): JsonResponse
    {
        $r    = $this->find($form, $record);
        $user = $request->user();

        if ($r->isFinal()) {
            return response()->json(['message' => 'لا يمكن حذف سجل معتمد.'], 422);
        }
        if ($r->created_by !== $user->id && ! $user->isClientAdmin()) {
            return response()->json(['message' => 'يحذف المسودة من أنشأها أو مدير المخزن فقط.'], 403);
        }

        $r->log('حذف', 'حُذفت المسودة رقم ' . $r->number);
        // تحرير الرقم ليمكن استخدامه مرة أخرى
        $r->update(['number' => mb_substr($r->number, 0, 30) . '#del' . $r->id]);
        $r->delete();

        return response()->json(['deleted' => true]);
    }

    /* ======================================================= */

    private function find(string $form, int $id): FormRecord
    {
        $f = FormRegistry::get($form);

        return $this->visible(FormRecord::query()->form($f['code']), $f, request()->user())->findOrFail($id);
    }

    /**
     * بيانات الشاشة كما أرسلها المتصفح بالضبط:
     * Laravel يحول النص الفارغ إلى null ويقص المسافات، و PHP يحول الكائن الفارغ {} إلى [] —
     * فتتعطل الشاشة عند فتح السجل (مثل نتائج فحص الاستلام والطباعة). نقرأ الطلب الأصلي ونحفظه كما هو.
     */
    private function rawData(Request $request): mixed
    {
        $raw = json_decode((string) $request->getContent(), false);

        return is_object($raw) && isset($raw->data) && (is_object($raw->data) || is_array($raw->data))
            ? $raw->data
            : $request->input('data');
    }

    private function validated(Request $request, string $code, ?int $ignoreId): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'number'      => ['required', 'string', 'max:40',
                Rule::unique('form_records', 'number')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('form_code', $code))
                    ->ignore($ignoreId)],
            'title'       => ['nullable', 'string', 'max:255'],
            'record_date' => ['nullable', 'date'],
            'status'      => ['required', Rule::in([FormRecord::STATUS_DRAFT, FormRecord::STATUS_FINAL])],
            'data'        => ['required', 'array'],
        ], self::MESSAGES);
    }

    /**
     * النماذج الخاصة (مثل تقييم الأداء): يراها كاتبها والموظف صاحب التقييم
     * ومدير المخزن ومن له حق الاعتماد النهائي (الموارد البشرية) فقط
     */
    private function visible($query, array $f, User $user)
    {
        if (empty($f['private']) || $user->isClientAdmin() || FormRegistry::canFinalize($f, $user)) {
            return $query;
        }

        return $query->where(function ($w) use ($user) {
            $w->where('created_by', $user->id)
              ->orWhere('data->empId', $user->id)
              ->orWhere('data->evaluatorId', $user->id);
        });
    }

    private function canReopen(User $user): bool
    {
        return $user->isClientAdmin() || $user->hasRole('quality_manager');
    }

    private function present(FormRecord $r): array
    {
        $r->loadMissing('creator', 'finalizer', 'logs.user');

        return [
            'id'           => $r->id,
            'number'       => $r->number,
            'title'        => $r->title,
            'record_date'  => $r->record_date?->format('Y-m-d'),
            'status'       => $r->status,
            // كما حُفظت (الكائن الفارغ يبقى {})
            'data'         => json_decode((string) $r->getRawOriginal('data')) ?? $r->data,
            'created_by'   => $r->creator?->displayName(),
            'finalized_by' => $r->finalizer?->displayName(),
            'finalized_at' => $r->finalized_at?->timezone('Africa/Cairo')->format('Y-m-d H:i'),
            'logs'         => $r->logs->map(fn ($l) => [
                't'      => $l->created_at?->timezone('Africa/Cairo')->format('Y-m-d H:i'),
                'user'   => $l->user?->displayName() ?? '—',
                'action' => $l->action,
                'detail' => $l->detail,
            ])->values(),
        ];
    }
}