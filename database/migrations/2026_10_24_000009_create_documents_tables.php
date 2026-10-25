<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * التحكم في الوثائق ومراجعتها واعتمادها (SH-014)
     * documents          : سجل الوثائق (إجراء · نموذج · وصف وظيفي · مؤشرات أداء · سياسة)
     * document_versions  : إصدارات كل وثيقة بملفها ومرحلة اعتمادها
     * document_logs      : سجل كل حركة (رفع · إرسال · اعتماد · إعادة · تعديل)
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('type', 20);                       // sop · form · jd · kpi · policy · other
            $table->string('title', 255);
            $table->string('dept', 100)->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();   // الإصدار الساري
            $table->boolean('is_active')->default(true);      // false = ملغاة
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('version', 10);                    // 01 · 02 …
            $table->string('stage', 20)->default('draft');    // draft · qa · hr · ceo · returned · effective · superseded
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('change_summary')->nullable();        // ملخص التعديل
            $table->text('return_note')->nullable();           // سبب الإعادة الأخير
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('review_due')->nullable();            // موعد المراجعة الدورية (سنتان)
            $table->json('approvals')->nullable();             // [{step, user_id, name, role, at}]
            $table->timestamps();

            $table->unique(['document_id', 'version']);
            $table->index(['tenant_id', 'stage']);
        });

        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 100)->nullable();
            $table->string('action', 40);
            $table->string('comment', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_logs');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};