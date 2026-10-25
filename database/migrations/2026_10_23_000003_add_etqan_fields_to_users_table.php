<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط المستخدم بالعميل والدور
     * tenant_id = null يعني مدير النظام (Super Admin)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')
                  ->constrained('tenants')->restrictOnDelete();
            $table->foreignId('role_id')->nullable()->after('tenant_id')
                  ->constrained('roles')->restrictOnDelete();
            $table->string('name_ar')->nullable()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['name_ar', 'phone', 'is_active', 'last_login_at']);
        });
    }
};