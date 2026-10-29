<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * الأدوار الوظيفية في إتقان
     * level: 1 = مدير النظام · 2 = مدير العميل · 3 = مستخدم
     */
    public function run(): void
    {
        $roles = [
            // key                        الاسم بالعربية                         English                           level sort
            ['super_admin',               'مدير النظام',                         'Super Admin',                    1, 1],
            ['client_admin',              'مدير العميل (مدير المخزن)',            'Client Admin (Warehouse Manager)', 2, 2],

            ['ceo',                       'الرئيس التنفيذي',                      'CEO',                            3, 10],
            ['managing_director',         'العضو المنتدب / المدير العام',          'Managing Director',              3, 11],
            ['pharmacist_manager',        'المدير الصيدلي',                       'Managing Pharmacist',            3, 12],
            ['quality_manager',           'مدير إدارة الجودة',                    'Quality Manager',                3, 13],
            ['hr_manager',                'مدير الموارد البشرية',                 'HR Manager',                     3, 14],
            ['commercial_director',       'المدير التجاري',                       'Commercial Director',            3, 15],
            ['accounts_manager',          'مدير الحسابات',                        'Accounts Manager',               3, 16],

            ['purchasing_officer',        'مسؤول المشتريات',                      'Purchasing Officer',             3, 20],
            ['wholesale_sales_manager',   'مدير بيع الجملة',                      'Wholesale Sales Manager',        3, 21],
            ['retail_sales_supervisor',   'مشرف بيع القطاعي',                     'Retail Sales Supervisor',        3, 22],
            ['distribution_supervisor',   'مشرف التوزيع',                         'Distribution Supervisor',        3, 23],
            ['fleet_supervisor',          'مشرف الحركة',                          'Fleet Supervisor',               3, 24],

            ['receiving_storekeeper',     'أمين مخزن الاستلامات',                 'Receiving Storekeeper',          3, 30],
            ['wholesale_storekeeper',     'أمين مخزن الجملة',                     'Wholesale Storekeeper',          3, 31],
            ['retail_storekeeper',        'أمين مخزن القطاعي',                    'Retail Storekeeper',             3, 32],
            ['control',                   'مراجع أدوية (كنترول)',                 'Control (Order Checker)',        3, 33],
            ['picker',                    'محضر أدوية',                           'Picker',                         3, 34],
            ['receiving_worker',          'عامل استلامات',                        'Receiving Worker',               3, 35],

            ['data_entry',                'مدخل بيانات',                          'Data Entry',                     3, 40],
            ['telesales',                 'تلي سيلز',                             'Telesales',                      3, 41],
            ['sales_rep',                 'مندوب بيع',                            'Sales Representative',           3, 42],
            ['driver',                    'سائق',                                 'Driver',                         3, 43],
            ['accountant',                'محاسب',                                'Accountant',                     3, 44],
            ['hr_officer',                'مسؤول الموارد البشرية',                'HR Officer',                     3, 45],
            ['it_officer',                'مسؤول الحاسب والأنظمة',                'IT Officer',                     3, 46],
            ['safety_officer',            'مسؤول السلامة',                        'Safety Officer',                 3, 47],
            ['security',                  'فرد أمن',                              'Security',                       3, 48],
            ['complaints_officer',        'مسؤول شكاوى العملاء',                  'Customer Complaints Officer',    3, 17],
            ['external_auditor',          'مراجع خارجي (مؤقت)',                   'External Auditor',               3, 90],
        ];

        foreach ($roles as [$key, $ar, $en, $level, $sort]) {
            Role::updateOrCreate(
                ['key' => $key],
                ['name_ar' => $ar, 'name_en' => $en, 'level' => $level, 'sort' => $sort]
            );
        }
    }
}