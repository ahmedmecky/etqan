<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الأدوار الوظيفية (مشتركة لكل العملاء)
     * level: 1 = مدير النظام · 2 = مدير العميل · 3 = مستخدم
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();            // مثال: quality_manager
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedTinyInteger('level')->default(3);
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};