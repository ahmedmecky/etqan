<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سجلات النماذج (محاضر الاجتماعات · محاضر الجرد · مؤشرات الأداء · …)
     * وسجل الحركات على كل سجل
     */
    public function up(): void
    {
        Schema::create('form_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('form_code', 30);                 // مثال: SH-034-F02
            $table->string('number', 40);                    // رقم المحضر: DEP-2026-001
            $table->string('title')->nullable();             // وصف مختصر للقائمة
            $table->date('record_date')->nullable();
            $table->string('status', 20)->default('draft');  // draft · final
            $table->json('data');                            // بيانات النموذج كاملة
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'form_code', 'number']);
            $table->index(['tenant_id', 'form_code', 'record_date']);
        });

        Schema::create('form_record_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_record_id')->constrained('form_records')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);                    // إنشاء · حفظ · اعتماد · إعادة فتح · حذف
            $table->string('detail', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_record_logs');
        Schema::dropIfExists('form_records');
    }
};