<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * المفوضون بالتفتيش الميداني (SH-012): رابط شخصي لكل مفوض بدون تسجيل دخول
     * الفحوصات نفسها تُحفظ في form_records (SH-012-F04)
     */
    public function up(): void
    {
        Schema::create('inspection_delegates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('job', 120)->nullable();
            $table->string('token', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_delegates');
    }
};