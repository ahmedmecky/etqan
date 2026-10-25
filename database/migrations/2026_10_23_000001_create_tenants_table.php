<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الشركات / المخازن (العملاء)
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();          // مثال: TGT
            $table->string('name_ar');                      // الاسم بالعربية
            $table->string('name_en');                      // الاسم بالإنجليزية
            $table->string('logo_path')->nullable();        // مسار الشعار PNG
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};