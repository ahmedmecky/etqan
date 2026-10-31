<?php

namespace App\Support;

use App\Models\MaintAsset;
use App\Models\MaintContract;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * الصيانة والأجهزة (SH-023 التأهيل والمعايرة · SH-017 مكافحة الحريق)
 *   عقود الصيانة الخارجية بتاريخ انتهاء · سجل الأجهزة بالرقم التسلسلي · الجرد الفعلي
 *   عند اقتراب الانتهاء أو حدوثه: إشعار للمدير الصيدلي ومدير الجودة والمدير العام (حدث «maint»)
 */
class Maintenance
{
    private const TZ = 'Africa/Cairo';

    /** تنبيه تواريخ الأجهزة (الطفاية · المعايرة) قبل الموعد بعدد أيام */
    public const ASSET_ALERT_DAYS = 30;

    /** أنواع العقود */
    public const KINDS = [
        'fire'        => 'مكافحة الحريق (الطفايات والإنذار)',
        'it'          => 'صيانة الكمبيوتر والشبكات',
        'calibration' => 'معايرة أجهزة القياس (الترمومترات)',
        'cooling'     => 'صيانة الثلاجات والتكييف',
        'power'       => 'المولد والكهرباء وUPS',
        'cctv'        => 'كاميرات المراقبة والإنذار',
        'pest'        => 'مكافحة الآفات',
        'cleaning'    => 'النظافة',
        'other'       => 'أخرى',
    ];

    /** فئات الأجهزة: الاسم · اسم التاريخ الخاص بها · نوع العقد المعتاد */
    public const CATEGORIES = [
        'computer'     => ['label' => 'كمبيوتر', 'due' => 'الصيانة القادمة', 'kind' => 'it'],
        'laptop'       => ['label' => 'لابتوب', 'due' => 'الصيانة القادمة', 'kind' => 'it'],
        'printer'      => ['label' => 'طابعة', 'due' => 'الصيانة القادمة', 'kind' => 'it'],
        'scanner'      => ['label' => 'قارئ باركود', 'due' => 'الصيانة القادمة', 'kind' => 'it'],
        'network'      => ['label' => 'شبكة (راوتر · سويتش)', 'due' => 'الصيانة القادمة', 'kind' => 'it'],
        'ups'          => ['label' => 'UPS', 'due' => 'تغيير البطارية', 'kind' => 'power'],
        'thermometer'  => ['label' => 'ترمومتر / جهاز حرارة ورطوبة', 'due' => 'المعايرة القادمة', 'kind' => 'calibration'],
        'logger'       => ['label' => 'داتا لوجر', 'due' => 'المعايرة القادمة', 'kind' => 'calibration'],
        'scale'        => ['label' => 'ميزان', 'due' => 'المعايرة القادمة', 'kind' => 'calibration'],
        'fridge'       => ['label' => 'ثلاجة / غرفة تبريد', 'due' => 'الصيانة القادمة', 'kind' => 'cooling'],
        'ac'           => ['label' => 'تكييف', 'due' => 'الصيانة القادمة', 'kind' => 'cooling'],
        'extinguisher' => ['label' => 'طفاية حريق', 'due' => 'انتهاء الطفاية / إعادة التعبئة', 'kind' => 'fire'],
        'alarm'        => ['label' => 'إنذار حريق / دخان', 'due' => 'الفحص القادم', 'kind' => 'fire'],
        'camera'       => ['label' => 'كاميرا / مسجل كاميرات', 'due' => 'الصيانة القادمة', 'kind' => 'cctv'],
        'generator'    => ['label' => 'مولد كهرباء', 'due' => 'الصيانة القادمة', 'kind' => 'power'],
        'other'        => ['label' => 'أخرى', 'due' => 'الصيانة القادمة', 'kind' => 'other'],
    ];

    public const STATUSES = ['active' => 'يعمل', 'repair' => 'تحت الإصلاح', 'out' => 'خارج الخدمة', 'disposed' => 'مُكهّن'];

    public const STATE_LABELS = [
        'ok' => 'ساري', 'soon' => 'يقترب', 'week' => 'أقل من أسبوع', 'expired' => 'منتهي', 'none' => 'بدون تاريخ', 'off' => 'موقوف',
    ];

    /** المستلمون الافتراضيون لإشعار الانتهاء */
    public const NOTIFY_ROLES = ['pharmacist_manager', 'quality_manager', 'general_manager', 'ceo', 'managing_director'];

    /** يضيف ويعدل: مدير المخزن · الجودة · المدير الصيدلي · المدير العام · مسؤول IT · مسؤول السلامة */
    public static function canManage(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('quality_manager', 'pharmacist_manager', 'general_manager', 'ceo', 'managing_director', 'it_officer', 'safety_officer');
    }

    /** يرى الشاشة ويجرد الأجهزة */
    public static function canView(User $u): bool
    {
        if ($u->hasRole('external_auditor')) {
            return Access::auditorCan($u, 'maint');
        }

        return self::canManage($u) || $u->hasRole('wholesale_storekeeper', 'retail_storekeeper', 'receiving_storekeeper');
    }

    public static function today(): Carbon
    {
        return Carbon::today(self::TZ);
    }

    public static function daysLeft($date): int
    {
        $ymd = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : substr((string) $date, 0, 10);

        return (int) round(self::today()->diffInDays(Carbon::createFromFormat('Y-m-d', $ymd, self::TZ)->startOfDay(), false));
    }

    /** ok · soon · week · expired */
    public static function stateFor($date, int $alertDays): string
    {
        $d = self::daysLeft($date);

        return $d < 0 ? 'expired' : ($d <= 7 ? 'week' : ($d <= $alertDays ? 'soon' : 'ok'));
    }

    /** مرحلة الإشعار (المنتهي يتكرر كل ٧ أيام) أو null */
    public static function alertStage($date, int $alertDays): ?string
    {
        $d = self::daysLeft($date);
        if ($d < 0) {
            return 'expired-' . intdiv(-$d - 1, 7);
        }

        return $d <= 7 ? 'week' : ($d <= $alertDays ? 'soon' : null);
    }

    public static function leftText(int $d): string
    {
        return $d < 0 ? 'منتهي منذ ' . (-$d) . ' يوم' : ($d === 0 ? 'ينتهي اليوم' : 'متبقي ' . $d . ' يوم');
    }

    /** ملخص للمخزن الحالي: [contracts => [expired, soon], assets => [expired, soon], missing, uncounted] */
    public static function summary(): array
    {
        $c = MaintContract::query()->where('is_active', true)->get();
        $a = MaintAsset::query()->whereNotIn('status', ['out', 'disposed'])->get();
        $cs = $c->map->state();
        $as = $a->map->state();

        return [
            'contracts' => ['all' => $c->count(), 'expired' => $cs->filter(fn ($s) => $s === 'expired')->count(), 'soon' => $cs->filter(fn ($s) => in_array($s, ['soon', 'week'], true))->count()],
            'assets'    => ['all' => $a->count(), 'expired' => $as->filter(fn ($s) => $s === 'expired')->count(), 'soon' => $as->filter(fn ($s) => in_array($s, ['soon', 'week'], true))->count()],
            'missing'   => $a->where('count_result', 'missing')->count(),
            'repair'    => $a->where('status', 'repair')->count(),
            'nocontract'=> $a->whereNull('contract_id')->count(),
            'uncounted' => $a->filter(fn ($x) => ! $x->last_counted || $x->last_counted->lt(self::today()->subMonths(6)))->count(),
        ];
    }

    /** تنبيهات الرئيسية */
    public static function homeAlerts(User $u): array
    {
        if (! self::canView($u)) {
            return [];
        }
        $s = self::summary();
        $out = [];
        if ($s['contracts']['expired']) $out[] = ['n' => $s['contracts']['expired'], 'tone' => 'bad', 'icon' => 'gear', 'title' => 'عقود صيانة منتهية',
            'sub' => $s['contracts']['expired'] . ' عقد يحتاج تجديد', 'url' => route('maint.index', ['tab' => 'contracts', 'st' => 'expired'])];
        if ($s['contracts']['soon']) $out[] = ['n' => $s['contracts']['soon'], 'tone' => 'warn', 'icon' => 'gear', 'title' => 'عقود صيانة تقترب من الانتهاء',
            'sub' => $s['contracts']['soon'] . ' عقد', 'url' => route('maint.index', ['tab' => 'contracts', 'st' => 'soon'])];
        if ($s['assets']['expired']) $out[] = ['n' => $s['assets']['expired'], 'tone' => 'bad', 'icon' => 'gear', 'title' => 'أجهزة تجاوزت موعدها',
            'sub' => 'طفايات منتهية أو معايرة متأخرة', 'url' => route('maint.index', ['tab' => 'assets', 'st' => 'expired'])];
        if ($s['assets']['soon']) $out[] = ['n' => $s['assets']['soon'], 'tone' => 'warn', 'icon' => 'gear', 'title' => 'أجهزة يقترب موعدها',
            'sub' => 'طفايات أو معايرة خلال ' . self::ASSET_ALERT_DAYS . ' يوم', 'url' => route('maint.index', ['tab' => 'assets', 'st' => 'soon'])];
        if ($s['missing']) $out[] = ['n' => $s['missing'], 'tone' => 'bad', 'icon' => 'gear', 'title' => 'أجهزة غير موجودة في الجرد',
            'sub' => $s['missing'] . ' جهاز', 'url' => route('maint.index', ['tab' => 'assets', 'st' => 'missing'])];

        return $out;
    }

    /**
     * فحص الانتهاء لمخزن (للأمر اليومي etqan:maint-check):
     * يرجع العناصر التي دخلت مرحلة جديدة [['type', 'model', 'stage', 'title', 'body', 'url', 'urgent']]
     */
    public static function due(int $tenantId): array
    {
        $out = [];
        $contracts = MaintContract::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('is_active', true)->get();
        foreach ($contracts as $c) {
            $stage = self::alertStage($c->end_date, (int) $c->alert_days);
            if (! $stage || $stage === $c->alert_stage) {
                continue;
            }
            $d = self::daysLeft($c->end_date);
            $n = $c->assets()->withoutGlobalScope('tenant')->whereNotIn('status', ['out', 'disposed'])->count();
            $out[] = [
                'model' => $c, 'stage' => $stage, 'urgent' => $d <= 7,
                'title' => $d < 0 ? 'عقد صيانة منتهي' : 'عقد صيانة يقترب من الانتهاء',
                'body'  => $c->company . ' · ' . $c->kindLabel() . ' · ينتهي ' . $c->end_date->format('Y-m-d') . ' (' . self::leftText($d) . ')' . ($n ? " · {$n} جهاز" : ''),
                'url'   => route('maint.index', ['tab' => 'contracts', 'open' => $c->id]),
            ];
        }

        $assets = MaintAsset::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->whereNotNull('due_date')
            ->whereNotIn('status', ['out', 'disposed'])->get();
        foreach ($assets as $a) {
            $stage = self::alertStage($a->due_date, self::ASSET_ALERT_DAYS);
            if (! $stage || $stage === $a->alert_stage) {
                continue;
            }
            $d = self::daysLeft($a->due_date);
            $out[] = [
                'model' => $a, 'stage' => $stage, 'urgent' => $d <= 7,
                'title' => $d < 0 ? 'جهاز تجاوز موعده' : 'موعد جهاز يقترب',
                'body'  => $a->name . ' · ' . $a->serial . ($a->location ? ' · ' . $a->location : '') . ' · ' . $a->dueLabel() . ' ' . $a->due_date->format('Y-m-d') . ' (' . self::leftText($d) . ')',
                'url'   => route('maint.index', ['tab' => 'assets', 'q' => $a->serial]),
            ];
        }

        return $out;
    }
}