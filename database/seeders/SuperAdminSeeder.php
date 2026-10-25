<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * حساب مدير النظام · يُغير كلمة المرور بعد أول دخول
     */
    public function run(): void
    {
        $role = Role::where('key', 'super_admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@etqan.test'],
            [
                'tenant_id' => null,
                'role_id'   => $role->id,
                'name'      => 'Etqan Super Admin',
                'name_ar'   => 'مدير النظام',
                'password'  => 'Etqan@2026',
                'is_active' => true,
            ]
        );
    }
}