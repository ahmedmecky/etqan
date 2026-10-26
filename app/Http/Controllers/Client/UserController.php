<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\JobLibrary;
use App\Support\Access;
use App\Models\PermissionOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * مدير العميل: إدارة مستخدمي مخزنه فقط
 */
class UserController extends Controller
{
    private const PASSWORD_RULES = ['string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'];

    private const MESSAGES = [
        'name.required'     => 'اكتب اسم المستخدم (بالإنجليزية).',
        'email.required'    => 'اكتب البريد الإلكتروني.',
        'email.email'       => 'البريد الإلكتروني غير صحيح.',
        'email.unique'      => 'هذا البريد الإلكتروني مستخدم لحساب آخر.',
        'role_id.required'  => 'اختر الدور الوظيفي.',
        'role_id.exists'    => 'الدور الوظيفي غير صحيح.',
        'password.required' => 'اكتب كلمة مرور مبدئية.',
        'password.min'      => 'كلمة المرور يجب ألا تقل عن ٨ أحرف.',
        'password.regex'    => 'كلمة المرور يجب أن تحتوي على حروف إنجليزية وأرقام.',
        'job_code.in'       => 'الوصف الوظيفي غير صحيح.',
        'hire_date.date'    => 'تاريخ التعيين غير صحيح.',
    ];

    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $q      = trim((string) $request->query('q', ''));
        $roleId = $request->query('role');

        $users = User::query()
            ->with('role')
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('name_ar', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($roleId, fn ($query) => $query->where('role_id', $roleId))
            ->get()
            ->sortBy(fn ($u) => [$u->role?->sort ?? 999, $u->displayName()])
            ->values();

        return view('client.users.index', [
            'users'  => $users,
            'roles'  => Role::assignableByClient()->get(),
            'q'      => $q,
            'roleId' => $roleId,
        ]);
    }

    public function create(): View
    {
        return view('client.users.form', [
            'user'  => new User(['is_active' => true]),
            'roles' => Role::assignableByClient()->get(),
        ] + $this->jobData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(null), self::MESSAGES);

        $user = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'role_id'   => $data['role_id'],
            'name'      => $data['name'],
            'name_ar'   => $data['name_ar'] ?? null,
            'email'     => strtolower($data['email']),
            'phone'     => $data['phone'] ?? null,
            'password'  => $data['password'],
            'is_active' => $request->boolean('is_active'),
        ] + $this->jobFields($data));

        $this->savePerms($request, $user);

        return redirect()->route('client.users.index')
            ->with('status', "تم إنشاء المستخدم {$user->displayName()} ({$user->email}). أرسل له البريد وكلمة المرور المبدئية.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureManageable($request, $user);

        return view('client.users.form', [
            'user'  => $user,
            'roles' => Role::assignableByClient()->get(),
        ] + $this->jobData());
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureManageable($request, $user);

        $data = $request->validate($this->rules($user), self::MESSAGES);

        $user->fill([
            'role_id'   => $data['role_id'],
            'name'      => $data['name'],
            'name_ar'   => $data['name_ar'] ?? null,
            'email'     => strtolower($data['email']),
            'phone'     => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ] + $this->jobFields($data));

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $this->savePerms($request, $user);

        return redirect()->route('client.users.index')
            ->with('status', "تم حفظ بيانات {$user->displayName()}" . (! empty($data['password']) ? ' وتغيير كلمة المرور.' : '.'));
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->ensureManageable($request, $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active
            ? "تم تفعيل حساب {$user->displayName()}."
            : "تم إيقاف حساب {$user->displayName()}.");
    }

    private function rules(?User $user): array
    {
        $assignable = Role::assignableByClient()->pluck('id')->all();

        return [
            'name'      => ['required', 'string', 'max:255'],
            'name_ar'   => ['nullable', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role_id'   => ['required', Rule::in($assignable)],
            'password'  => array_merge([$user ? 'nullable' : 'required'], self::PASSWORD_RULES),
            'is_active' => ['nullable', 'boolean'],
            'job_code'     => ['nullable', Rule::in(JobLibrary::all()->keys()->all())],
            'extra_jobs'   => ['nullable', 'array', 'max:6'],
            'extra_jobs.*' => [Rule::in(JobLibrary::all()->keys()->all())],
            'hire_date'    => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /** استثناءات الصلاحيات لهذا الشخص ('' = حسب الدور) */
    private function savePerms(Request $request, User $user): void
    {
        $in = (array) $request->input('perm', []);
        foreach (array_keys(Access::MODULES) as $m) {
            $v = $in[$m] ?? '';
            $q = PermissionOverride::query()->where('tenant_id', $user->tenant_id)->where('user_id', $user->id)->where('module', $m);
            if (! in_array($v, ['none', 'view', 'use'], true)) {
                $q->delete();
                continue;
            }
            PermissionOverride::updateOrCreate(['tenant_id' => $user->tenant_id, 'role_id' => null, 'user_id' => $user->id, 'module' => $m],
                ['level' => $v, 'updated_by' => $request->user()->id]);
        }
        Access::forget();
    }

    /** الوصف الوظيفي: الأساسي + الإضافي (بدون تكرار الأساسي) + تاريخ التعيين */
    private function jobFields(array $data): array
    {
        $main = $data['job_code'] ?? null;

        return [
            'job_code'   => $main,
            'extra_jobs' => array_values(array_diff(array_unique($data['extra_jobs'] ?? []), [$main])) ?: null,
            'hire_date'  => $data['hire_date'] ?? null,
        ];
    }

    /** مكتبة الأوصاف للشاشة: مجمعة حسب الإدارة + الوظيفة المقترحة لكل دور */
    private function jobData(): array
    {
        $groups = [];
        foreach (JobLibrary::all() as $code => $j) {
            $groups[JobLibrary::dept($code)][$code] = $j['title'];
        }
        $defaults = Role::assignableByClient()->get()
            ->mapWithKeys(fn ($r) => [$r->id => JobLibrary::ROLE_DEFAULT[$r->key] ?? ''])->filter()->all();

        return ['jobGroups' => $groups, 'roleJobs' => $defaults];
    }

    /** المستخدم من نفس المخزن، وليس مديراً (المدير لا يعدل نفسه أو مديراً آخر من هنا) */
    private function ensureManageable(Request $request, User $user): void
    {
        $user->loadMissing('role');

        if ($user->tenant_id !== $request->user()->tenant_id || $user->role?->level !== Role::LEVEL_USER) {
            abort(404);
        }
    }
}