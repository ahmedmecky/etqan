<?php

namespace App\Support;

use App\Models\TenantSetting;

/**
 * إعدادات المخزن
 * لإضافة إعداد جديد: أضفه في DEFINITIONS فقط، وسيظهر في شاشة الإعدادات تلقائياً
 */
class Settings
{
    /** مجموعات الإعدادات كما تظهر في الشاشة */
    public const GROUPS = [
        'receiving' => ['title' => 'استلام الشحنات', 'code' => 'SH-001'],
        'inventory' => ['title' => 'محاضر الجرد', 'code' => 'SH-016'],
        'complaints' => ['title' => 'شكاوى العملاء', 'code' => 'SH-007'],
        'capa'      => ['title' => 'الانحرافات والإجراءات التصحيحية', 'code' => 'SH-008'],
        'ordercheck' => ['title' => 'فحص دقة التحضير', 'code' => 'SH-004-F04'],
    ];

    public const DEFINITIONS = [
        'receiving_items' => [
            'group'   => 'receiving',
            'type'    => 'bool',
            'default' => true,
            'label'   => 'تسجيل أصناف الشحنة على إتقان',
            'help'    => 'مفعّل: يُسجل كل صنف بالتشغيلة والصلاحية والكمية في نموذج الاستلام، وتُحسب الفروقات تلقائياً. '
                       . 'غير مفعّل: الأصناف تُسجل على نظام ERP، ويُسجل النموذج رقم إذن الاستلام وعدد الأصناف والفروقات فقط.',
        ],
        'receiving_min_shelf' => [
            'group'   => 'receiving',
            'type'    => 'number',
            'default' => 6,
            'min'     => 0,
            'max'     => 36,
            'step'    => 1,
            'suffix'  => 'شهر',
            'label'   => 'الحد الأدنى للصلاحية المتبقية عند الاستلام',
            'help'    => 'الصنف الأقل من هذا الحد يظهر «صلاحية قصيرة» ويحتاج إجراءً مع المورد أو موافقة المدير التجاري (SH-001).',
        ],
        'inventory_items' => [
            'group'   => 'inventory',
            'type'    => 'bool',
            'default' => true,
            'label'   => 'تسجيل الأصناف المجرودة على إتقان',
            'help'    => 'مفعّل: يُسجل العد صنفاً بصنف في محضر الجرد (الجزء «الأصناف المجرودة»). '
                       . 'غير مفعّل: العد يتم على نظام ERP، ويُسجل المحضر الإجماليات من تقرير الجرد على ERP فقط، '
                       . 'ويُخفى جدول الأصناف وجدول فروق الأصناف.',
        ],
        'inventory_tolerance' => [
            'group'   => 'inventory',
            'type'    => 'number',
            'default' => 0.5,
            'min'     => 0,
            'max'     => 10,
            'step'    => 0.1,
            'suffix'  => '%',
            'label'   => 'الحد المقبول لنسبة الاختلاف في القيمة',
            'help'    => 'القيمة الافتراضية في كل محضر جرد جديد. يمكن تعديلها داخل المحضر.',
        ],
        'capa_days_critical' => [
            'group'   => 'capa',
            'type'    => 'number',
            'default' => 5,
            'min'     => 1,
            'max'     => 30,
            'step'    => 1,
            'suffix'  => 'يوم عمل',
            'label'   => 'مهلة تقديم الخطة: انحراف حرج',
            'help'    => 'أيام العمل بدون يوم الجمعة. يُحسب موعد الخطة تلقائياً من تاريخ الاكتشاف، ويمكن تعديله داخل الانحراف.',
        ],
        'capa_days_major' => [
            'group'   => 'capa',
            'type'    => 'number',
            'default' => 10,
            'min'     => 1,
            'max'     => 60,
            'step'    => 1,
            'suffix'  => 'يوم عمل',
            'label'   => 'مهلة تقديم الخطة: انحراف رئيسي',
            'help'    => 'أيام العمل بدون يوم الجمعة. يُحسب موعد الخطة تلقائياً من تاريخ الاكتشاف، ويمكن تعديله داخل الانحراف.',
        ],
        'capa_days_minor' => [
            'group'   => 'capa',
            'type'    => 'number',
            'default' => 30,
            'min'     => 1,
            'max'     => 120,
            'step'    => 1,
            'suffix'  => 'يوم',
            'label'   => 'مهلة تقديم الخطة: انحراف ثانوي',
            'help'    => 'أيام تقويمية. يُحسب موعد الخطة تلقائياً من تاريخ الاكتشاف، ويمكن تعديله داخل الانحراف.',
        ],
        'capa_verify_months' => [
            'group'   => 'capa',
            'type'    => 'number',
            'default' => 2,
            'min'     => 1,
            'max'     => 12,
            'step'    => 1,
            'suffix'  => 'شهر',
            'label'   => 'التحقق من الفاعلية بعد تنفيذ آخر إجراء',
            'help'    => 'يُحسب موعد التحقق تلقائياً، ويمكن تعديله داخل الانحراف (SH-008: من ١ إلى ٣ أشهر).',
        ],
        'cmp_days_critical' => [
            'group'   => 'complaints',
            'type'    => 'number',
            'default' => 5,
            'min'     => 1,
            'max'     => 30,
            'step'    => 1,
            'suffix'  => 'يوم عمل',
            'label'   => 'مدة إغلاق الشكوى الحرجة',
            'help'    => 'من تاريخ الاستلام، بأيام العمل بدون يوم الجمعة. يُحسب موعد الإغلاق تلقائياً ويمكن تعديله داخل الشكوى.',
        ],
        'cmp_days_major' => [
            'group'   => 'complaints',
            'type'    => 'number',
            'default' => 3,
            'min'     => 1,
            'max'     => 30,
            'step'    => 1,
            'suffix'  => 'يوم عمل',
            'label'   => 'مدة إغلاق الشكوى الرئيسية',
            'help'    => 'من تاريخ الاستلام، بأيام العمل بدون يوم الجمعة. يُحسب موعد الإغلاق تلقائياً ويمكن تعديله داخل الشكوى.',
        ],
        'cmp_days_minor' => [
            'group'   => 'complaints',
            'type'    => 'number',
            'default' => 7,
            'min'     => 1,
            'max'     => 60,
            'step'    => 1,
            'suffix'  => 'يوم عمل',
            'label'   => 'مدة إغلاق الشكوى الثانوية',
            'help'    => 'من تاريخ الاستلام، بأيام العمل بدون يوم الجمعة. يُحسب موعد الإغلاق تلقائياً ويمكن تعديله داخل الشكوى.',
        ],
        'oc_w_item' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 5,
            'min'     => 0,
            'max'     => 20,
            'step'    => 1,
            'suffix'  => 'نقطة',
            'label'   => 'وزن خطأ: صنف خطأ (اسم · تركيز · شكل)',
            'help'    => 'النقاط لكل صنف به هذا الخطأ.',
        ],
        'oc_w_short' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 4,
            'min'     => 0,
            'max'     => 20,
            'step'    => 1,
            'suffix'  => 'نقطة',
            'label'   => 'وزن خطأ: صلاحية منتهية أو قصيرة',
            'help'    => 'أعلى وزن هو مقياس الدقة الموزونة.',
        ],
        'oc_w_qty' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 3,
            'min'     => 0,
            'max'     => 20,
            'step'    => 1,
            'suffix'  => 'نقطة',
            'label'   => 'وزن خطأ: كمية خطأ',
            'help'    => 'أعلى وزن هو مقياس الدقة الموزونة.',
        ],
        'oc_w_batch' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 2,
            'min'     => 0,
            'max'     => 20,
            'step'    => 1,
            'suffix'  => 'نقطة',
            'label'   => 'وزن خطأ: تشغيلة خطأ',
            'help'    => 'أعلى وزن هو مقياس الدقة الموزونة.',
        ],
        'oc_w_exp' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 2,
            'min'     => 0,
            'max'     => 20,
            'step'    => 1,
            'suffix'  => 'نقطة',
            'label'   => 'وزن خطأ: صلاحية مختلفة عن الفاتورة',
            'help'    => 'أعلى وزن هو مقياس الدقة الموزونة.',
        ],
        'oc_w_damage' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 2,
            'min'     => 0,
            'max'     => 20,
            'step'    => 1,
            'suffix'  => 'نقطة',
            'label'   => 'وزن خطأ: عبوة تالفة',
            'help'    => 'أعلى وزن هو مقياس الدقة الموزونة.',
        ],
        'oc_target' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 98,
            'min'     => 50,
            'max'     => 100,
            'step'    => 0.5,
            'suffix'  => '%',
            'label'   => 'الدقة المستهدفة',
            'help'    => 'أقل من المستهدف يظهر باللون الأحمر في الفحص والتقرير.',
        ],
        'oc_train' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 6,
            'min'     => 1,
            'max'     => 200,
            'step'    => 1,
            'suffix'  => 'نقطة / شهر',
            'label'   => 'حد التدريب للزون أو المحضر',
            'help'    => 'عند الوصول لهذه النقاط في الشهر يظهر «تدريب» في التقرير.',
        ],
        'oc_disc' => [
            'group'   => 'ordercheck',
            'type'    => 'number',
            'default' => 15,
            'min'     => 1,
            'max'     => 500,
            'step'    => 1,
            'suffix'  => 'نقطة / شهر',
            'label'   => 'حد الإجراء التأديبي للزون أو المحضر',
            'help'    => 'عند الوصول لهذه النقاط في الشهر يظهر «إجراء تأديبي» في التقرير.',
        ],
        'oc_zone_first' => [
            'group'   => 'ordercheck',
            'type'    => 'bool',
            'default' => false,
            'label'   => 'الزون = الجزء الأول من موقع الصنف',
            'help'    => 'مفعّل: الموقع A-01-03 يُحسب زون A. غير مفعّل: الموقع المكتوب في قائمة الأصناف هو الزون كما هو.',
        ],
    ];

    /** كل الإعدادات لمخزن معين (القيم المحفوظة + الافتراضي لما لم يُحفظ) */
    public static function forTenant(int $tenantId): array
    {
        $saved = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key');

        $out = [];
        foreach (self::DEFINITIONS as $key => $def) {
            $out[$key] = $saved->has($key) ? self::cast($def, $saved[$key]) : $def['default'];
        }

        return $out;
    }

    /** قيمة إعداد واحد */
    public static function get(int $tenantId, string $key): mixed
    {
        return self::forTenant($tenantId)[$key] ?? null;
    }

    /** حفظ مجموعة قيم · يرجع أسماء الإعدادات التي تغيرت */
    public static function save(int $tenantId, array $values, ?int $userId = null): array
    {
        $current = self::forTenant($tenantId);
        $changed = [];

        foreach (self::DEFINITIONS as $key => $def) {
            if (! array_key_exists($key, $values)) continue;

            $new = self::cast($def, $values[$key]);
            if ($new === $current[$key]) continue;

            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => self::store($def, $new), 'updated_by' => $userId]
            );
            $changed[] = $def['label'];
        }

        return $changed;
    }

    /** قواعد التحقق لشاشة الإعدادات */
    public static function rules(): array
    {
        $rules = [];
        foreach (self::DEFINITIONS as $key => $def) {
            $rules[$key] = match ($def['type']) {
                'bool'   => ['required', 'boolean'],
                'number' => ['required', 'numeric', 'min:' . $def['min'], 'max:' . $def['max']],
                default  => ['nullable', 'string', 'max:500'],
            };
        }

        return $rules;
    }

    private static function cast(array $def, mixed $v): mixed
    {
        return match ($def['type']) {
            'bool'   => in_array($v, [true, 1, '1', 'true', 'on'], true),
            'number' => (float) $v,
            default  => (string) $v,
        };
    }

    private static function store(array $def, mixed $v): string
    {
        return match ($def['type']) {
            'bool'  => $v ? '1' : '0',
            default => (string) $v,
        };
    }
}