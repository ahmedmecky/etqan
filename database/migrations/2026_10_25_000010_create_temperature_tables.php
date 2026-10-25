<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * التحكم في الحرارة والرطوبة وسلسلة التبريد (SH-030)
     * temp_points   : نقاط المراقبة (مناطق الغرفة · الثلاجات · غرف التبريد · مناطق خاصة) بحدود كل منها
     * temp_readings : قراءة الصباح والمساء لكل نقطة (الحالية · الأعلى · الأدنى · الرطوبة) = SH-030-F01 · F02
     * temp_checks   : المراجعة الأسبوعية لبيانات أجهزة التسجيل · اختبار الإنذار الشهري
     * تقرير انحراف الحرارة SH-030-F03 يُحفظ في form_records (الشاشة temp-excursion)
     */
    public function up(): void
    {
        Schema::create('temp_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('type', 20);                        // room · fridge · coldroom · special
            $table->string('device_no', 60)->nullable();
            $table->decimal('t_min', 5, 1);
            $table->decimal('t_max', 5, 1);
            $table->decimal('warn_low', 5, 1)->nullable();     // حد الإنذار المبكر الأدنى
            $table->decimal('warn_high', 5, 1)->nullable();    // حد الإنذار المبكر الأعلى
            $table->unsignedTinyInteger('h_max')->nullable();  // أقصى رطوبة نسبية ٪ (null = لا تُقاس)
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('temp_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('point_id')->constrained('temp_points')->cascadeOnDelete();
            $table->date('date');
            $table->string('shift', 2);                        // am · pm
            $table->time('time')->nullable();
            $table->decimal('cur', 5, 1)->nullable();
            $table->decimal('t_high', 5, 1)->nullable();       // الأعلى منذ آخر ضبط
            $table->decimal('t_low', 5, 1)->nullable();        // الأدنى منذ آخر ضبط
            $table->unsignedTinyInteger('hum')->nullable();
            $table->string('status', 10)->default('ok');       // ok · warn · out
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['point_id', 'date', 'shift']);
            $table->index(['tenant_id', 'date']);
        });

        Schema::create('temp_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('point_id')->nullable()->constrained('temp_points')->cascadeOnDelete();
            $table->string('kind', 10);                        // review (أسبوعية لكل المخزن) · alarm (لكل ثلاجة)
            $table->date('date');                              // review: أول يوم في الأسبوع (السبت) · alarm: تاريخ الاختبار
            $table->string('result', 10)->nullable();          // alarm: ok · fail
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'kind', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_checks');
        Schema::dropIfExists('temp_readings');
        Schema::dropIfExists('temp_points');
    }
};