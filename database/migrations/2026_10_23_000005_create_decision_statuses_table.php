<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * متابعة تنفيذ قرارات محاضر الاجتماعات (SH-034-F03)
     * كل صف = حالة قرار واحد داخل محضر معتمد
     */
    public function up(): void
    {
        Schema::create('decision_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('form_record_id')->constrained('form_records')->cascadeOnDelete();
            $table->string('decision_key', 40);              // معرف القرار داخل المحضر
            $table->string('status', 20)->default('open');   // open · done · cancelled
            $table->date('done_at')->nullable();             // تاريخ التنفيذ
            $table->string('note', 500)->nullable();         // الدليل / الملاحظة
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['form_record_id', 'decision_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_statuses');
    }
};