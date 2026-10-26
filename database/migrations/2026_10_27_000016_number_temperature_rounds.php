<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * قراءات الحرارة: الجولات أصبحت مرقمة (١ … ٦ حسب إعدادات المخزن) بدلاً من الصباح / المساء
     * القراءات السابقة: الصباح ← ١ · المساء ← ٢
     */
    public function up(): void
    {
        DB::table('temp_readings')->where('shift', 'am')->update(['shift' => '1']);
        DB::table('temp_readings')->where('shift', 'pm')->update(['shift' => '2']);
    }

    public function down(): void
    {
        DB::table('temp_readings')->where('shift', '1')->update(['shift' => 'am']);
        DB::table('temp_readings')->where('shift', '2')->update(['shift' => 'pm']);
    }
};