<?php

namespace App\Support;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * المراجعة الخارجية (SH-012 التفتيش بجهة مستقلة · SH-025 الرقابة على الجهات الخارجية)
 * تسلسل التقارير المعتاد (ISO 19011 / تفتيش هيئة الدواء):
 *   الخطة (النطاق · المعايير · الفريق · الإدارات) ← العمل الميداني (شاشة لكل إدارة: ما رُوجع + الملاحظات مصنفة بالدليل والمرجع)
 *   ← التقرير (الملخص · الخلاصة · التقييم) ← رد الجهة (السبب · الإجراء · المسؤول · الموعد · DEV) ← الإغلاق
 */
class Audits
{
    public const TZ = 'Africa/Cairo';

    public const ROLE = 'external_auditor';

    public const TYPES = ['external' => 'استشاري / جهة خارجية مستقلة', 'authority' => 'تفتيش هيئة الدواء', 'client' => 'مراجعة من عميل أو شركة أم'];

    public const STATUSES = ['plan' => 'تخطيط', 'fieldwork' => 'العمل الميداني', 'reported' => 'صدر التقرير', 'response' => 'الرد والإجراءات', 'closed' => 'مغلقة'];

    /** الدرجة ← [الاسم، اللون، تحتاج إجراء؟، الوصف] */
    public const GRADES = [
        'critical' => ['حرجة', '#b3261e', true, 'خطر مباشر على المريض أو مخالفة جسيمة للترخيص · تُعالج فوراً'],
        'major'    => ['كبرى', '#d9480f', true, 'مخالفة مهمة لمتطلب GDP أو لإجراء قد تؤثر على الجودة'],
        'minor'    => ['صغرى', '#b7791f', true, 'قصور محدود لا يؤثر مباشرة على الجودة'],
        'obs'      => ['ملاحظة', '#0e7490', true, 'ملاحظة أو خطر محتمل يحتاج متابعة'],
        'ofi'      => ['فرصة تحسين', '#4d7c0f', false, 'اقتراح لتحسين الأداء (ليس مخالفة)'],
        'good'     => ['نقطة قوة', '#1e7a3c', false, 'ممارسة جيدة تُذكر في التقرير'],
    ];

    public const RATINGS = ['good' => 'مطابق', 'conditional' => 'مطابق بشرط معالجة الملاحظات', 'poor' => 'غير مطابق'];

    /** الإدارات المقترحة ← إجراءاتها */
    public const AREAS = [
        'الاستلام' => 'SH-001',
        'التخزين' => 'SH-005 · SH-016',
        'الحرارة وسلسلة التبريد' => 'SH-030 · SH-023',
        'التحضير والصرف' => 'SH-004 · SH-028',
        'النقل والتوزيع' => 'SH-006',
        'المرتجعات والتالف' => 'SH-002 · SH-020',
        'الشكاوى والاستدعاء والمغشوش' => 'SH-007 · SH-010 · SH-011',
        'إدارة الجودة والتوثيق' => 'SH-008 · SH-009 · SH-012 · SH-013 · SH-014 · SH-027',
        'التدريب والموارد البشرية' => 'SH-015 · SH-021 · SH-022',
        'المشتريات والموردون والعملاء' => 'SH-031 · SH-025',
        'المبنى والنظافة ومكافحة الآفات' => 'SH-003 · SH-026',
        'السلامة والحريق والأمن والطوارئ' => 'SH-017 · SH-018 · SH-029 · SH-033',
        'الأنظمة الإلكترونية' => 'SH-024',
        'المستحضرات الخاضعة للرقابة' => 'SH-032',
    ];

    /** فتح المراجعة وتعديلها والرد عليها وإغلاقها */
    public static function canManage(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('quality_manager', 'pharmacist_manager');
    }

    public static function isAuditor(?User $u): bool
    {
        return $u && $u->hasRole(self::ROLE);
    }

    /** يرى المراجعة: المديرون · المدير العام · المراجع المكلف بها */
    public static function canSee(User $u, Audit $a): bool
    {
        return self::canManage($u) || $u->hasRole('ceo', 'managing_director') || (int) $a->auditor_user_id === $u->id;
    }

    /** يكتب الملاحظات والإدارات: المراجع المكلف أو المديرون (قبل إصدار التقرير) */
    public static function canWrite(User $u, Audit $a): bool
    {
        return $a->inField() && (self::canManage($u) || (int) $a->auditor_user_id === $u->id);
    }

    public static function nextNumber(int $tenantId): string
    {
        $y = Carbon::now(self::TZ)->format('Y');
        $last = Audit::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('number', 'like', "EA-{$y}-%")->orderByDesc('id')->value('number');

        return sprintf('EA-%s-%03d', $y, $last ? ((int) substr($last, -3)) + 1 : 1);
    }

    /** عدد الملاحظات لكل درجة · المفتوح · المتأخر */
    public static function stats(Audit $a): array
    {
        $f = $a->findings;
        $today = Carbon::today(self::TZ)->toDateString();
        $act = $f->filter->needsAction();

        return [
            'grades' => collect(self::GRADES)->map(fn ($g, $k) => $f->where('grade', $k)->count())->all(),
            'total'  => $f->count(),
            'action' => $act->count(),
            'open'   => $act->where('status', 'open')->count(),
            'late'   => $act->filter(fn ($x) => $x->status === 'open' && $x->due_date && $x->due_date->format('Y-m-d') < $today)->count(),
            'noPlan' => $act->filter(fn ($x) => ! $x->action)->count(),
            'areas'  => $a->areas->count(), 'areasDone' => $a->areas->where('done', true)->count(),
        ];
    }

    public static function homeAlerts(User $u): array
    {
        if (! self::canManage($u)) {
            return [];
        }
        $list = Audit::query()->whereIn('status', ['reported', 'response'])->with('findings')->get();
        $open = $list->sum(fn ($a) => self::stats($a)['open']);

        return $open ? [[
            'n' => $open, 'tone' => 'warn', 'icon' => 'search', 'title' => 'ملاحظات مراجعة خارجية مفتوحة',
            'sub' => $open . ' ملاحظة في ' . $list->count() . ' مراجعة', 'url' => route('audits.index'),
        ]] : [];
    }
}