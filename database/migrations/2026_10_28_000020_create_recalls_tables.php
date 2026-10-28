<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الاستدعاء والسحب من السوق (SH-010):
     *   recalls          : منشور السحب (F01) · الصنف والتشغيلة والدرجة والمدة · التسوية والتصرف والإغلاق (F03)
     *   recall_customers : العملاء الذين اشتروا التشغيلة (F02) · الإبلاغ · إقرار الصيدلية الموقّع والمختوم (F05)
     */
    public function up(): void
    {
        Schema::create('recalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('number', 20);                         // RC-YYYY-###
            $table->string('circular_no', 80)->nullable();        // رقم المنشور
            $table->string('source', 20)->default('eda');         // eda · supplier · manufacturer · internal
            $table->date('received_on');
            $table->string('product', 200);
            $table->string('batch', 80);
            $table->date('expiry')->nullable();
            $table->string('class', 4)->default('2');             // درجة الاستدعاء 1 · 2 · 3
            $table->text('reason')->nullable();
            $table->date('deadline')->nullable();                 // آخر موعد لإبلاغ العملاء
            $table->unsignedInteger('qty_received')->nullable();  // (أ) الكمية المستلمة من التشغيلة
            $table->unsignedInteger('qty_in_stock')->nullable();  // (ج) الموجود بالمخزن والمنقول للمسحوبات
            $table->string('status', 10)->default('open');        // open · closed
            $table->string('disposal', 20)->nullable();           // return · destroy
            $table->string('disposal_ref', 80)->nullable();
            $table->date('eda_notified_on')->nullable();
            $table->date('supplier_notified_on')->nullable();
            $table->string('dev_no', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'number']);
        });

        Schema::create('recall_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('recall_id')->constrained('recalls')->cascadeOnDelete();
            $table->string('code', 40)->nullable();
            $table->string('name', 200);
            $table->string('address', 255)->nullable();
            $table->string('phone', 40)->nullable();
            $table->unsignedInteger('qty_sold')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->string('method', 12)->nullable();             // phone · whatsapp · visit · letter
            $table->string('declaration', 10)->default('pending'); // pending · none · has
            $table->unsignedInteger('qty_returned')->nullable();
            $table->date('returned_on')->nullable();
            $table->string('pharmacist', 120)->nullable();         // اسم الصيدلي المقر
            $table->string('file_path')->nullable();               // صورة الإقرار الموقّع والمختوم
            $table->string('file_name')->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['recall_id', 'declaration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recall_customers');
        Schema::dropIfExists('recalls');
    }
};