<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الصلاحيات: الافتراضي من مصفوفة RACI (في الكود) · هنا فقط ما غيّره مدير المخزن
     * role_id = تعديل للدور كله · user_id = استثناء لشخص واحد
     */
    public function up(): void
    {
        Schema::create('permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('module', 20);
            $table->string('level', 5);                 // none · view · use
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'role_id', 'user_id', 'module'], 'perm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_overrides');
    }
};