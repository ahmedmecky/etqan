<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
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
        ]);
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
        ]);

        return redirect()->route('client.users.index')
            ->with('status', "تم إنشاء المستخدم {$user->displayName()} ({$user->email}). أرسل له البريد وكلمة المرور المبدئية.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureManageable($request, $user);

        return view('client.users.form', [
            'user'  => $user,
            'roles' => Role::assignableByClient()->get(),
        ]);
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
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

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
        ];
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