<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * المراجعة الخارجية (SH-012 · SH-025): استشاري / جهة خارجية / هيئة الدواء / عميل
     *   audits          : المراجعة (النطاق · المعايير · الفريق · التواريخ · الخلاصة · المراحل)
     *   audit_areas     : الإدارات / الأقسام التي تُراجع (لكل إدارة شاشة)
     *   audit_findings  : الملاحظات (حرجة · كبرى · صغرى · ملاحظة · فرصة تحسين · نقطة قوة) + الدليل والمرجع + الرد والإجراء التصحيحي
     *   audit_logs      : سجل ما فتحه المراجع الخارجي من صفحات
     * + دور «مراجع خارجي» وحقول انتهاء الحساب والشركة للمستخدم
     */
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(['key' => 'external_auditor'],
            ['name_ar' => 'مراجع خارجي (مؤقت)', 'name_en' => 'External Auditor', 'level' => 3, 'sort' => 90, 'updated_at' => now(), 'created_at' => now()]);

        Schema::table('users', function (Blueprint $table) {
            $table->date('access_until')->nullable()->after('is_active');   // انتهاء الحساب المؤقت
            $table->string('company', 160)->nullable()->after('phone');
        });

        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('number', 20);                          // EA-YYYY-###
            $table->string('type', 12)->default('external');       // external · authority · client
            $table->string('title', 200);
            $table->string('company', 160)->nullable();             // الجهة / المكتب الاستشاري
            $table->string('lead', 120)->nullable();                // رئيس فريق المراجعة
            $table->string('team', 255)->nullable();
            $table->foreignId('auditor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('scope')->nullable();
            $table->text('criteria')->nullable();                  // المعايير: GDP هيئة الدواء · الإجراءات · ISO …
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 12)->default('plan');         // plan · fieldwork · reported · response · closed
            $table->string('rating', 12)->nullable();               // good · conditional · poor
            $table->text('conclusion')->nullable();
            $table->string('file_path')->nullable();                // تقرير الجهة (PDF) إن وُجد
            $table->string('file_name')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'number']);
        });

        Schema::create('audit_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('sops', 120)->nullable();                // إجراءات الإدارة (SH-001 · SH-005)
            $table->string('auditee', 160)->nullable();             // من قابلهم المراجع
            $table->date('audited_on')->nullable();
            $table->text('notes')->nullable();                      // نص حر: ما تمت مراجعته
            $table->boolean('done')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('audit_areas')->nullOnDelete();
            $table->unsignedSmallInteger('no')->default(0);
            $table->string('grade', 12);                           // critical · major · minor · obs · ofi · good
            $table->text('text');                                   // الملاحظة
            $table->text('evidence')->nullable();                   // الدليل الموضوعي
            $table->string('ref', 160)->nullable();                 // المتطلب: بند GDP / الإجراء
            $table->text('cause')->nullable();                      // رد الجهة: السبب الجذري
            $table->text('action')->nullable();                     // الإجراء التصحيحي
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('dev_no', 30)->nullable();
            $table->string('status', 10)->default('open');          // open · done · verified
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 8);
            $table->string('path', 255);
            $table->string('route', 120)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('at')->useCurrent();
            $table->index(['tenant_id', 'user_id', 'at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('audit_findings');
        Schema::dropIfExists('audit_areas');
        Schema::dropIfExists('audits');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['access_until', 'company']);
        });
        DB::table('roles')->where('key', 'external_auditor')->delete();
    }
};