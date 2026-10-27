<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الصيانة والأجهزة (SH-023 · SH-017):
     *   maint_contracts : عقود الصيانة مع الشركات الخارجية (الطفايات · الكمبيوتر · معايرة الترمومترات …) بتاريخ انتهاء
     *   maint_assets    : سجل الأجهزة بالرقم التسلسلي، مرتبط بعقد الصيانة، وله تاريخه الخاص (انتهاء الطفاية · المعايرة القادمة)
     *   maint_counts    : الجرد الفعلي للأجهزة (موجود · غير موجود · تالف)
     * alert_stage: آخر مرحلة أُرسل عنها إشعار (soon · week · expired-N) حتى لا يتكرر الإشعار.
     */
    public function up(): void
    {
        Schema::create('maint_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('kind', 20);                          // fire · it · calibration · cooling · power · cctv · pest · cleaning · other
            $table->string('company', 160);
            $table->string('contract_no', 60)->nullable();
            $table->string('contact', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date');
            $table->unsignedSmallInteger('alert_days')->default(30);
            $table->string('visits', 120)->nullable();            // عدد الزيارات / الدورية (مثال: زيارة شهرية)
            $table->text('scope')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->foreignId('renewed_from_id')->nullable()->constrained('maint_contracts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('alert_stage', 20)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'end_date']);
        });

        Schema::create('maint_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('name', 160);
            $table->string('serial', 80);
            $table->string('tag_no', 40)->nullable();             // الكود الداخلي (الملصق)
            $table->string('brand', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('location', 120)->nullable();
            $table->foreignId('contract_id')->nullable()->constrained('maint_contracts')->nullOnDelete();
            $table->date('due_date')->nullable();                  // انتهاء الطفاية / المعايرة القادمة / الصيانة القادمة
            $table->date('last_service')->nullable();
            $table->string('status', 12)->default('active');       // active · repair · out · disposed
            $table->text('notes')->nullable();
            $table->date('last_counted')->nullable();
            $table->string('count_result', 10)->nullable();        // ok · missing · damaged
            $table->string('alert_stage', 20)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'serial']);
        });

        Schema::create('maint_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('date');
            $table->json('results');                               // {asset_id: {r: ok|missing|damaged, loc?: ''}}
            $table->unsignedSmallInteger('found')->default(0);
            $table->unsignedSmallInteger('missing')->default(0);
            $table->unsignedSmallInteger('damaged')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_counts');
        Schema::dropIfExists('maint_assets');
        Schema::dropIfExists('maint_contracts');
    }
};