<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * المهام المكلف بها: مهمة لمرة واحدة يكلف بها المدير موظفاً (أو يضعها الشخص لنفسه)
     *   لمن · المهمة · ماذا يفعل · النوع (عاجل · مهم · عادي) · الموعد · إشعار عند التكليف والتذكير والتأخير
     * remind_stage: آخر تذكير أُرسل (pre · today · over-YYYYMMDD) حتى لا يتكرر.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('details')->nullable();
            $table->string('priority', 10)->default('important');   // urgent · important · normal
            $table->date('due_date');
            $table->time('due_time')->nullable();
            $table->string('sop', 20)->nullable();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 10)->default('open');          // open · done · cancelled
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('done_note', 500)->nullable();
            $table->string('remind_stage', 20)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'assigned_to', 'status']);
            $table->index(['tenant_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};