<?php

namespace App\Support;

/**
 * سجل الشاشات (النماذج) المتاحة في النظام
 * المفتاح يظهر في الرابط: /forms/meeting-minutes
 */
class FormRegistry
{
    public const FORMS = [
        'meeting-minutes' => [
            'code'  => 'SH-034-F02',
            'title' => 'محاضر الاجتماعات',
            'view'  => 'forms.meeting-minutes',
        ],
        'inventory-count' => [
            'code'  => 'SH-016-F01 · F03',
            'title' => 'محاضر الجرد',
            'view'  => 'forms.inventory-count',
        ],
        'receiving' => [
            'code'  => 'SH-001-F02 · F03',
            'title' => 'استلام الشحنات',
            'view'  => 'forms.receiving',
            // اعتماد القرار (إفراج / رفض / حجر): هذه الأدوار + مدير المخزن
            'final_roles' => ['pharmacist_manager', 'quality_manager'],
        ],
        'deviation' => [
            'code'  => 'SH-008-F01 · F02 · F03',
            'title' => 'الانحرافات والإجراءات التصحيحية',
            'view'  => 'forms.deviation',
            // التصنيف واعتماد الخطة والإغلاق: هذه الأدوار + مدير المخزن
            'final_roles' => ['quality_manager', 'pharmacist_manager'],
            'register'    => 'deviations.index',   // سجل المتابعة SH-008-F04
        ],
        'complaint' => [
            'code'  => 'SH-007-F01 · F02',
            'title' => 'شكاوى العملاء',
            'view'  => 'forms.complaint',
            // التصنيف والإغلاق: هذه الأدوار + مدير المخزن
            'final_roles' => ['quality_manager', 'pharmacist_manager'],
            'register'    => 'complaints.index',   // سجل الشكاوى SH-007-F03
        ],
        'inspection' => [
            'code'  => 'SH-012-F02 · F03',
            'title' => 'التفتيش الذاتي',
            'view'  => 'forms.inspection',
            // إصدار التقرير: هذه الأدوار + مدير المخزن
            'final_roles' => ['quality_manager', 'pharmacist_manager'],
            'register'    => 'inspections.index',  // سجل التفتيش والتغطية
            'urls'        => ['bank' => 'inspections.bank'],   // بنك بنود الفحص
        ],
        // التفتيش السريع (موبايل): نفس سجل التفتيش الذاتي، شاشة أبسط للمفتش
        'inspection-quick' => [
            'code'  => 'SH-012-F02 · F03',
            'title' => 'تفتيش سريع',
            'view'  => 'forms.inspection-quick',
            'final_roles' => ['quality_manager', 'pharmacist_manager'],
            'urls'        => ['quick' => 'inspections.quick', 'full' => ['forms.page', 'inspection']],
        ],
        // فحص دقة التحضير العشوائي (المدير الصيدلي): ٥–١٠ طلبيات · الدقة الموزونة · الزونات
        'order-check' => [
            'code'  => 'SH-004-F04',
            'title' => 'فحص دقة التحضير',
            'view'  => 'forms.order-check',
            'final_roles' => ['pharmacist_manager', 'quality_manager'],
            'register'    => 'orderchecks.index',
            'urls'        => ['products' => 'orderchecks.products'],
        ],
        // تقييم الأداء الشهري: المدير المباشر ← الموظف بالعلم ← الموارد البشرية (أو الرئيس التنفيذي إن لم يوجد)
        'kpi' => [
            'code'  => 'KPI',
            'title' => 'تقييم الأداء الشهري',
            'view'  => 'forms.kpi',
            'final_roles'    => ['hr_manager'],
            'final_fallback' => ['ceo', 'general_manager', 'managing_director'],
            'register'       => 'kpi.index',
            'urls'           => ['sets' => 'kpi.sets'],
            'private'        => true,     // يراه الكاتب والموظف والموارد البشرية ومدير المخزن فقط
        ],
        // تقرير انحراف الحرارة (SH-030-F03): أمين المخزن ← تقييم المدير الصيدلي ← اعتماد مدير الجودة
        'temp-excursion' => [
            'code'  => 'SH-030-F03',
            'title' => 'تقرير انحراف الحرارة',
            'view'  => 'forms.temp-excursion',
            'final_roles' => ['quality_manager'],
            // مراحل وسيطة: الأدوار التي يحق لها كل مرحلة (+ مدير المخزن)
            'step_roles'  => ['evaluate' => ['pharmacist_manager', 'quality_manager']],
            'register'    => 'temperature.report',
            'urls'        => ['temp' => 'temperature.round', 'points' => 'temperature.points.json', 'products' => 'orderchecks.products'],
        ],
        // محضر التدريب وتقييمه (SH-015-F03): المدرب ← اعتماد المدير الصيدلي ← متابعة الفاعلية بمدير الجودة
        'training' => [
            'code'  => 'SH-015-F03',
            'title' => 'محضر التدريب',
            'view'  => 'forms.training',
            'final_roles' => ['quality_manager'],
            'step_roles'  => ['approve' => ['pharmacist_manager', 'quality_manager']],
            'register'    => 'training.index',
        ],
    ];

    /** نمط المفاتيح المسموح بها في المسار */
    public static function pattern(): string
    {
        return implode('|', array_map('preg_quote', array_keys(self::FORMS)));
    }

    /** هل يحق للمستخدم اعتماد (إقفال) سجلات هذا النموذج؟ */
    public static function canFinalize(array $form, \App\Models\User $user): bool
    {
        if (empty($form['final_roles']) || $user->isClientAdmin()) {
            return true;
        }

        $roles = $form['final_roles'];

        // البديل: إذا لم يوجد في المخزن مستخدم فعال بأدوار الاعتماد (مثل مدير الموارد البشرية)
        if (! empty($form['final_fallback']) && $user->tenant_id) {
            $exists = \App\Models\User::query()
                ->with('role')
                ->where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->get()
                ->contains(fn ($u) => collect($form['final_roles'])->contains(fn ($r) => $u->hasRole($r)));
            if (! $exists) {
                $roles = $form['final_fallback'];
            }
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /** صلاحيات المراحل الوسيطة: ['evaluate' => true|false, ...] */
    public static function stepRights(array $form, \App\Models\User $user): array
    {
        $out = [];
        foreach ($form['step_roles'] ?? [] as $step => $roles) {
            $out[$step] = $user->isClientAdmin() || collect($roles)->contains(fn ($r) => $user->hasRole($r));
        }

        return $out;
    }

    public static function get(string $key): array
    {
        abort_unless(isset(self::FORMS[$key]), 404);

        return self::FORMS[$key] + ['key' => $key];
    }
}