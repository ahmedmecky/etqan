<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مدير النظام: إنشاء وإدارة مدير العميل (مدير المخزن)
 */
class TenantAdminController extends Controller
{
    private const PASSWORD_RULES = ['string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'];

    private const MESSAGES = [
        'name.required'     => 'اكتب اسم المدير (بالإنجليزية أو العربية).',
        'email.required'    => 'اكتب البريد الإلكتروني للمدير.',
        'email.email'       => 'البريد الإلكتروني غير صحيح.',
        'email.unique'      => 'هذا البريد الإلكتروني مستخدم لحساب آخر.',
        'password.required' => 'اكتب كلمة مرور مبدئية للمدير.',
        'password.min'      => 'كلمة المرور يجب ألا تقل عن ٨ أحرف.',
        'password.regex'    => 'كلمة المرور يجب أن تحتوي على حروف إنجليزية وأرقام.',
    ];

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validateWithBag('admin', [
            'name'     => ['required', 'string', 'max:255'],
            'name_ar'  => ['nullable', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => array_merge(['required'], self::PASSWORD_RULES),
        ], self::MESSAGES);

        $role = Role::where('key', 'client_admin')->firstOrFail();

        User::create([
            'tenant_id' => $tenant->id,
            'role_id'   => $role->id,
            'name'      => $data['name'],
            'name_ar'   => $data['name_ar'] ?? null,
            'email'     => strtolower($data['email']),
            'phone'     => $data['phone'] ?? null,
            'password'  => $data['password'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.tenants.edit', $tenant)
            ->with('status', "تم إنشاء مدير العميل ({$data['email']}). أرسل له البريد وكلمة المرور المبدئية، واطلب منه تغييرها بعد أول دخول.");
    }

    public function update(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        $this->ensureBelongs($tenant, $user);

        $data = $request->validateWithBag('admin_' . $user->id, [
            'name'     => ['required', 'string', 'max:255'],
            'name_ar'  => ['nullable', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => array_merge(['nullable'], self::PASSWORD_RULES),
        ], self::MESSAGES);

        $user->fill([
            'name'    => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'email'   => strtolower($data['email']),
            'phone'   => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('admin.tenants.edit', $tenant)
            ->with('status', 'تم حفظ بيانات المدير' . (! empty($data['password']) ? ' وتغيير كلمة المرور.' : '.'));
    }

    public function toggle(Tenant $tenant, User $user): RedirectResponse
    {
        $this->ensureBelongs($tenant, $user);

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('admin.tenants.edit', $tenant)
            ->with('status', $user->is_active ? 'تم تفعيل حساب المدير.' : 'تم إيقاف حساب المدير.');
    }

    /** المدير يجب أن يتبع نفس العميل وأن يكون بدور مدير عميل */
    private function ensureBelongs(Tenant $tenant, User $user): void
    {
        $user->loadMissing('role');

        if ($user->tenant_id !== $tenant->id || $user->role?->level !== Role::LEVEL_CLIENT_ADMIN) {
            abort(404);
        }
    }
}