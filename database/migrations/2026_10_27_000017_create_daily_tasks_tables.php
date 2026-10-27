<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مهام اليوم (قائمة مهام يومية):
     *   daily_tasks     : المهام اليومية الثابتة التي يحددها مدير المخزن أو الجودة (نظافة · جولة أمن · مراجعة منشورات …)
     *   daily_task_logs : تنفيذ المهمة في يوم معين (من نفذها ومتى وملاحظة)
     * قراءات الحرارة تظهر في نفس القائمة تلقائياً من جولات الحرارة.
     */
    public function up(): void
    {
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('sop', 20)->nullable();
            $table->json('roles')->nullable();               // أدوار المسؤولين عن التنفيذ (فارغ = مدير المخزن والجودة والمدير الصيدلي)
            $table->time('due_time');                        // موعد الإنجاز: بعده تظهر «متأخرة» ويُرسل إشعار
            $table->string('days', 7)->default('012346');    // أيام العمل: 0 الأحد … 6 السبت (الجمعة 5 غير مضمنة)
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('daily_task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('daily_tasks')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_task_logs');
        Schema::dropIfExists('daily_tasks');
    }
};