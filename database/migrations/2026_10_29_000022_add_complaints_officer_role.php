<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** دور «مسؤول شكاوى العملاء» (الوصف الوظيفي JD-QA-02 · إجراء الشكاوى SH-007) */
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(['key' => 'complaints_officer'],
            ['name_ar' => 'مسؤول شكاوى العملاء', 'name_en' => 'Customer Complaints Officer', 'level' => 3, 'sort' => 17, 'updated_at' => now(), 'created_at' => now()]);
    }

    public function down(): void
    {
        DB::table('roles')->where('key', 'complaints_officer')->whereNotExists(fn ($q) => $q->from('users')->whereColumn('users.role_id', 'roles.id'))->delete();
    }
};