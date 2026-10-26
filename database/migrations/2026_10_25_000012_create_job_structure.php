<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الهيكل الوظيفي لكل مخزن + الوصف الوظيفي للموظف
     * tenant_jobs : لكل وظيفة من مكتبة الأوصاف الوظيفية (٣٠ وظيفة):
     *               mode (staffed موظف مخصص · covered تُوزع مهامها · na النشاط غير موجود)،
     *               العدد المخطط، قيم الفراغات، توزيع مجموعات المهام عند الشغور
     * users       : الوظيفة الأساسية + وظائف إضافية + تاريخ التعيين + تاريخ إقرار الاستلام
     */
    public function up(): void
    {
        Schema::create('tenant_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('job_code', 12);
            $table->string('mode', 10)->default('staffed');
            $table->unsignedSmallInteger('planned')->default(0);
            $table->json('blanks')->nullable();
            $table->json('reassign')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'job_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('job_code', 12)->nullable()->after('role_id');
            $table->json('extra_jobs')->nullable()->after('job_code');
            $table->date('hire_date')->nullable()->after('extra_jobs');
            $table->date('jd_ack_at')->nullable()->after('hire_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['job_code', 'extra_jobs', 'hire_date', 'jd_ack_at']);
        });
        Schema::dropIfExists('tenant_jobs');
    }
};