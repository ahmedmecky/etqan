<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * رابط شكاوى الصيدليات:
     * - رابط سري لكل مخزن (tenants.complaint_token) مع إمكانية إيقافه
     * - قائمة الأصناف وقائمة الصيدليات (تُرفع من Excel)
     * - الشكوى من الرابط تُحفظ بدون مستخدم مسجل (created_by = null)
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('complaint_token', 40)->nullable()->unique()->after('gln');
            $table->boolean('complaint_portal')->default(true)->after('complaint_token');
        });

        Schema::table('form_records', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('form_records', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 200);
            $table->decimal('price', 12, 2)->nullable();      // سعر الجمهور
            $table->string('location', 100)->nullable();      // المنطقة / الموقع (لا يظهر للصيدلي)
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
        });

        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 40);                       // كود الصيدلية المكتوب على الفاتورة
            $table->string('name', 200);
            $table->string('address', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
        Schema::dropIfExists('products');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['complaint_token']);
            $table->dropColumn(['complaint_token', 'complaint_portal']);
        });
        // created_by يبقى nullable عند التراجع لعدم فقد شكاوى الرابط
    }
};