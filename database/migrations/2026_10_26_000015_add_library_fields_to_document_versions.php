<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إصدارات من مكتبة إتقان (بدون رفع ملف):
     *   source      : file = ملف مرفوع · library = النص المدمج في النظام
     *   lib_version : رقم إصدار النص في المكتبة وقت إنشاء الإصدار
     *   fills       : قيم الفراغات [ … ] التي ملأها المخزن (بترتيبها في النص)
     */
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->string('source', 10)->default('file')->after('stage');
            $table->string('lib_version', 10)->nullable()->after('source');
            $table->json('fills')->nullable()->after('lib_version');
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropColumn(['source', 'lib_version', 'fills']);
        });
    }
};