<?php

namespace App\Support;

use App\Models\PermissionOverride;
use App\Models\User;

/**
 * الصلاحيات حسب الشاشة (module):
 *   none = مخفية · view = عرض فقط · use = تسجيل وتعديل
 * الترتيب: استثناء المستخدم ← تعديل الدور في المخزن ← الافتراضي من مصفوفة RACI (قسم الدور × إجراء الشاشة)
 * مدير المخزن ومدير إدارة الجودة: استخدام كامل دائماً. صلاحيات الاعتماد تبقى كما هي في كل شاشة.
 */
class Access
{
    public const LEVELS = ['none' => 'مخفية', 'view' => 'عرض', 'use' => 'استخدام'];
    private const RANK = ['none' => 0, 'view' => 1, 'use' => 2];

    /** الشاشات: العنوان · الإجراء · وصف قصير */
    public const MODULES = [
        'receiving'   => ['استلام الشحنات', 'SH-001', 'فحص الشحنات الواردة وقرار الإفراج'],
        'temperature' => ['الحرارة والرطوبة', 'SH-030', 'القراءات · تقارير الانحراف · نقاط المراقبة'],
        'inventory'   => ['محاضر الجرد', 'SH-016', 'الجرد الدوري والعشوائي'],
        'ordercheck'  => ['فحص دقة التحضير', 'SH-004', 'فحص الطلبيات قبل الخروج · تقرير الزونات'],
        'deviation'   => ['الانحرافات والإجراءات التصحيحية', 'SH-008', 'تسجيل الانحراف · السجل'],
        'complaint'   => ['شكاوى العملاء', 'SH-007', 'الشكاوى · السجل'],
        'inspection'  => ['التفتيش الذاتي', 'SH-012', 'التفتيش · السريع · الميداني · التغطية'],
        'documents'   => ['إعداد ومراجعة الوثائق', 'SH-014', 'إضافة الوثائق ورفع الإصدارات (قراءة الساري متاحة للجميع)'],
        'training'    => ['التدريب', 'SH-015', 'محاضر التدريب · تقرير الدورات · سجل الموظفين'],
        'kpi'         => ['تقييم الأداء', 'KPI', 'إنشاء التقييمات والسجل (الموظف يرى تقييمه دائماً)'],
        'meetings'    => ['الاجتماعات والقرارات', 'SH-034', 'المحاضر · متابعة القرارات'],
    ];

    /** مصفوفة RACI · الترتيب: CEO PH QA WH RC DS SL CM FN HR IT SF (من خريطة نظام الجودة) */
    private const DEPTS = ['CEO', 'PH', 'QA', 'WH', 'RC', 'DS', 'SL', 'CM', 'FN', 'HR', 'IT', 'SF'];
    private const RACI = [
        'SH-001' => 'I A C R R - - C I - - R',
        'SH-030' => '- A C R R R - - - - I -',
        'SH-016' => 'I R C R C - - - A - R -',
        'SH-004' => '- A C R - R R R - - I -',
        'SH-008' => 'I C A R R R R R C C C C',
        'SH-007' => 'I R A C - C R C - C - -',
        'SH-012' => 'I R A C C C C C C C C C',
        'SH-014' => 'C R A I I I I I I I R I',
        'SH-015' => 'I R R R R R R I I A I I',
        'SH-034' => 'R C A R I R R R C R I I',
    ];

    /** قسم كل دور نظام في مصفوفة RACI */
    public const ROLE_DEPT = [
        'ceo' => 'CEO', 'managing_director' => 'CEO', 'pharmacist_manager' => 'PH', 'quality_manager' => 'QA',
        'hr_manager' => 'HR', 'hr_officer' => 'HR', 'commercial_director' => 'CM', 'purchasing_officer' => 'CM', 'data_entry' => 'CM',
        'accounts_manager' => 'FN', 'accountant' => 'FN',
        'wholesale_sales_manager' => 'SL', 'retail_sales_supervisor' => 'SL', 'telesales' => 'SL', 'sales_rep' => 'SL',
        'distribution_supervisor' => 'DS', 'fleet_supervisor' => 'DS', 'driver' => 'DS',
        'receiving_storekeeper' => 'RC', 'receiving_worker' => 'RC',
        'wholesale_storekeeper' => 'WH', 'retail_storekeeper' => 'WH', 'control' => 'WH', 'picker' => 'WH',
        'it_officer' => 'IT', 'safety_officer' => 'SF', 'security' => 'SF',
    ];

    /** تقييم الأداء: المديرون والمشرفون ينشئون التقييمات */
    private const KPI_USE = ['ceo', 'managing_director', 'pharmacist_manager', 'hr_manager', 'hr_officer', 'commercial_director', 'accounts_manager',
        'wholesale_sales_manager', 'retail_sales_supervisor', 'distribution_supervisor', 'fleet_supervisor'];

    /** وظائف تنفيذية (غير إشرافية): الشاشات الإشرافية لها عرض فقط حتى لو كان قسمها ينفذ (R) */
    private const STAFF = ['picker', 'control', 'receiving_worker', 'driver', 'telesales', 'sales_rep', 'data_entry', 'accountant', 'security'];
    private const SUPERVISORY = ['training', 'meetings', 'documents', 'inspection'];

    /** تدقيق داخل القسم: RACI على مستوى القسم، وهذه الأدوار تختلف عن باقي قسمها */
    private const ROLE_TWEAK = [
        'picker'           => ['receiving' => 'none', 'inventory' => 'view', 'ordercheck' => 'view', 'temperature' => 'view'],
        'control'          => ['receiving' => 'none', 'temperature' => 'view'],
        'receiving_worker' => ['receiving' => 'view', 'temperature' => 'view', 'inventory' => 'none'],
        'driver'           => ['temperature' => 'view', 'ordercheck' => 'none'],
        'telesales'        => ['ordercheck' => 'none'],
        'sales_rep'        => ['ordercheck' => 'none'],
        'data_entry'       => ['ordercheck' => 'none'],
    ];

    /** الشاشة لكل نموذج */
    public const FORM_MODULE = [
        'receiving' => 'receiving', 'inventory-count' => 'inventory', 'order-check' => 'ordercheck', 'deviation' => 'deviation',
        'complaint' => 'complaint', 'inspection' => 'inspection', 'inspection-quick' => 'inspection', 'temp-excursion' => 'temperature',
        'training' => 'training', 'kpi' => 'kpi', 'meeting-minutes' => 'meetings',
    ];

    private static array $cache = [];

    /** الافتراضي من RACI لدور (بمفتاحه) */
    public static function default(?string $roleKey, string $module): string
    {
        if ($module === 'kpi') {
            return in_array($roleKey, self::KPI_USE, true) ? 'use' : 'none';
        }
        $sop = self::MODULES[$module][1] ?? null;
        $dept = self::ROLE_DEPT[$roleKey] ?? null;
        if (! $sop || ! $dept || ! isset(self::RACI[$sop])) {
            return 'none';
        }
        $letter = preg_split('/\s+/', self::RACI[$sop])[array_search($dept, self::DEPTS, true)] ?? '-';
        $level = in_array($letter, ['R', 'A'], true) ? 'use' : (in_array($letter, ['C', 'I'], true) ? 'view' : 'none');

        if (isset(self::ROLE_TWEAK[$roleKey][$module])) {
            return self::ROLE_TWEAK[$roleKey][$module];
        }
        if ($level === 'use' && in_array($roleKey, self::STAFF, true) && in_array($module, self::SUPERVISORY, true)) {
            return 'view';
        }

        return $level;
    }

    public static function letter(?string $roleKey, string $module): string
    {
        $sop = self::MODULES[$module][1] ?? null;
        $dept = self::ROLE_DEPT[$roleKey] ?? null;
        if (! $sop || ! $dept || ! isset(self::RACI[$sop])) return '';
        $l = preg_split('/\s+/', self::RACI[$sop])[array_search($dept, self::DEPTS, true)] ?? '-';

        return $l === '-' ? '' : $l;
    }

    /** مستخدم بصلاحيات كاملة دائماً */
    public static function full(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('quality_manager');
    }

    private static function overrides(int $tenantId): array
    {
        return self::$cache[$tenantId] ??= PermissionOverride::query()->where('tenant_id', $tenantId)->get()
            ->reduce(function ($c, $o) {
                if ($o->user_id) $c['u'][$o->user_id][$o->module] = $o->level;
                elseif ($o->role_id) $c['r'][$o->role_id][$o->module] = $o->level;

                return $c;
            }, ['u' => [], 'r' => []]);
    }

    /** مستوى الدور في المخزن (تعديل المدير أو الافتراضي) */
    public static function roleLevel(int $tenantId, ?int $roleId, ?string $roleKey, string $module): string
    {
        return self::overrides($tenantId)['r'][$roleId][$module] ?? self::default($roleKey, $module);
    }

    public static function level(?User $u, string $module): string
    {
        if (! $u || ! $u->tenant_id) return 'none';
        if (self::full($u)) return 'use';
        $u->loadMissing('role');

        return self::overrides($u->tenant_id)['u'][$u->id][$module]
            ?? self::roleLevel($u->tenant_id, $u->role_id, $u->role?->key, $module);
    }

    public static function userOverride(User $u, string $module): ?string
    {
        return self::overrides($u->tenant_id)['u'][$u->id][$module] ?? null;
    }

    public static function can(?User $u, string $module, string $need = 'view'): bool
    {
        return self::RANK[self::level($u, $module)] >= self::RANK[$need];
    }

    public static function forget(): void
    {
        self::$cache = [];
    }
}