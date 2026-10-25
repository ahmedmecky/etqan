<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * بيانات إضافية للعميل: المدينة · مناطق التغطية · رقم GLN
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('city', 60)->nullable()->after('address');      // المحافظة
            $table->json('coverage_zones')->nullable()->after('city');     // قائمة مناطق التغطية
            $table->string('gln', 13)->nullable()->unique()->after('coverage_zones'); // Global Location Number (GS1)
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['gln']);
            $table->dropColumn(['city', 'coverage_zones', 'gln']);
        });
    }
};