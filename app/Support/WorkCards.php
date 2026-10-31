<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * بطاقات العمل المبسطة: «ماذا أفعل بالضبط» لكل وظيفة
 * تُبنى تلقائياً من خطوات الإجراءات (SOP) التي يُذكر فيها اسم الوظيفة في عمود «المسؤول»،
 * فتبقى مطابقة للإجراءات دائماً. لغة قصيرة، خط كبير، مربع ☐ لكل خطوة، بدون مصطلحات.
 */
class WorkCards
{
    /** عبارات تخص الجميع (تُضاف لكل بطاقة) */
    private const ALL = ['كل العاملين', 'أي عامل', 'الحضور'];

    /** عبارات تخص كل مدير / مشرف قسم (تُضاف لبطاقات المديرين) */
    private const HEADS = ['مديرو الأقسام', 'مدير القسم', 'مشرف القسم', 'المشرف', 'رئيس القسم المعني', 'مديرو الإدارات'];

    /**
     * البطاقات: المفتاح ← [العنوان، كود الوصف الوظيفي، أسماء الوظيفة في الإجراءات، مدير/مشرف؟، أدوار المستخدمين]
     */
    public const CARDS = [
        'JD-MG-01' => ['مدير المخزن', 'JD-MG-01', ['مدير المخزن', 'صاحب النشاط'], true, []],
        'JD-PH-01' => ['المدير الصيدلي', 'JD-PH-01', ['المدير الصيدلي', 'المدير الصيدلي (شهرياً)'], true, ['pharmacist_manager']],
        'JD-QA-01' => ['مدير إدارة الجودة', 'JD-QA-01', ['مدير إدارة الجودة', 'المفتشون', 'فريق التقييم'], true, ['quality_manager']],
        'JD-QA-02' => ['مسؤول شكاوى العملاء', 'JD-QA-02', ['متلقي الشكوى', 'مسؤول شكاوى العملاء'], false, ['complaints_officer']],
        'JD-WH-01' => ['أمين مخزن الاستلامات', 'JD-WH-01', ['أمين مخزن الاستلام', 'أمين الاستلام'], false, ['receiving_storekeeper']],
        'JD-WH-02' => ['عامل الاستلامات', 'JD-WH-02', ['عامل الاستلام', 'عمال الاستلام'], false, ['receiving_worker']],
        'JD-WH-03' => ['أمين مخزن الجملة', 'JD-WH-03', ['أمين المخزن', 'أمين المخزن (أسبوعياً)', 'المسؤول عن العهدة', 'مسؤول مخزن التوالف'], false, ['wholesale_storekeeper']],
        'JD-WH-04' => ['أمين مخزن القطاعي', 'JD-WH-04', ['أمين المخزن', 'أمين المخزن (أسبوعياً)', 'المسؤول عن العهدة', 'مسؤول مخزن التوالف'], false, ['retail_storekeeper']],
        'JD-WH-05' => ['مراجع أدوية الجملة (كنترول)', 'JD-WH-05', ['الكنترول'], false, ['control']],
        'JD-WH-06' => ['مراجع أدوية القطاعي (كنترول)', 'JD-WH-06', ['الكنترول'], false, ['control']],
        'JD-WH-07' => ['محضر أدوية الجملة', 'JD-WH-07', ['المحضر', 'المحضرون'], false, ['picker']],
        'JD-WH-08' => ['محضر أدوية القطاعي', 'JD-WH-08', ['المحضر', 'المحضرون'], false, ['picker']],
        'JD-DS-01' => ['سائق الجملة', 'JD-DS-01', ['السائق', 'السائقون'], false, ['driver']],
        'JD-DS-02' => ['سائق القطاعي', 'JD-DS-02', ['السائق', 'السائقون'], false, ['driver']],
        'JD-DS-03' => ['مشرف التوزيع', 'JD-DS-03', ['مشرف التوزيع'], true, ['distribution_supervisor']],
        'JD-DS-04' => ['مشرف الحركة', 'JD-DS-04', ['مشرف الحركة'], true, ['fleet_supervisor']],
        'JD-SL-01' => ['تلي سيلز القطاعي', 'JD-SL-01', ['التلي سيلز', 'متلقي الشكوى'], false, ['telesales']],
        'JD-SL-02' => ['تلي سيلز الجملة', 'JD-SL-02', ['التلي سيلز', 'متلقي الشكوى'], false, ['telesales']],
        'JD-SL-03' => ['مدير بيع الجملة', 'JD-SL-03', ['مدير المبيعات', 'مدير بيع الجملة', 'مشرفو البيع'], true, ['wholesale_sales_manager']],
        'JD-SL-04' => ['مشرف بيع القطاعي', 'JD-SL-04', ['مشرف البيع', 'مشرفو البيع'], true, ['retail_sales_supervisor']],
        'JD-SL-05' => ['مندوب البيع الميداني', 'JD-SL-05', ['المندوب', 'المندوبون'], false, ['sales_rep']],
        'JD-SL-06' => ['مندوب الجملة (تسليم وتحصيل)', 'JD-SL-06', ['المندوب', 'المندوبون'], false, ['sales_rep']],
        'JD-CM-01' => ['المدير التجاري', 'JD-CM-01', ['المدير التجاري'], true, ['commercial_director']],
        'JD-CM-02' => ['مدخل بيانات', 'JD-CM-02', ['إدخال البيانات', 'مراقبة المخزون'], false, ['data_entry']],
        'JD-CM-03' => ['مسؤول المشتريات', 'JD-CM-03', ['مسؤول المشتريات', 'المشتريات'], false, ['purchasing_officer']],
        'JD-FN-01' => ['محاسب عملاء القطاعي', 'JD-FN-01', ['محاسب العملاء', 'الحسابات'], false, ['accountant']],
        'JD-FN-02' => ['محاسب عملاء الجملة', 'JD-FN-02', ['محاسب العملاء', 'الحسابات'], false, ['accountant']],
        'JD-FN-03' => ['محاسب الموردين', 'JD-FN-03', ['الحسابات'], false, ['accountant']],
        'JD-FN-04' => ['محاسب البنوك', 'JD-FN-04', ['الحسابات'], false, ['accountant']],
        'JD-FN-05' => ['أمين الخزينة', 'JD-FN-05', ['الحسابات'], false, ['accountant']],
        'JD-FN-06' => ['مدير الحسابات', 'JD-FN-06', ['مدير الحسابات', 'الحسابات'], true, ['accounts_manager']],
        'JD-HR-01' => ['مدير الموارد البشرية', 'JD-HR-01', ['مدير الموارد البشرية', 'الموارد البشرية'], true, ['hr_manager']],
        'JD-HR-02' => ['مسؤول الموارد البشرية', 'JD-HR-02', ['مسؤول الموارد البشرية', 'الموارد البشرية'], false, ['hr_officer']],
        // وظائف بلا وصف وظيفي بعد: البطاقة وحدها
        'CL' => ['عامل النظافة', null, ['عامل النظافة', 'عمال النظافة'], false, []],
        'JD-SF-01' => ['فرد الأمن', 'JD-SF-01', ['أفراد الأمن', 'موظف الأمن'], false, ['security']],
        'IT' => ['مسؤول الحاسب', null, ['مسؤول الحاسب', 'إدارة الحاسب', 'إدارة الحاسب الآلي', 'صاحب النظام'], false, ['it_officer']],
        'SF' => ['مسؤول السلامة', null, ['مسؤول السلامة', 'فريق مكافحة الحريق', 'عضو الكهرباء', 'عضو الاتصال', 'فني الكهرباء'], false, ['safety_officer']],
        'RT' => ['مسؤول المرتجعات', null, ['مسؤول المرتجعات'], false, []],
    ];

    /** قواعد ذهبية لكل العاملين (تُطبع في كل بطاقة) */
    public const GOLDEN = [
        'لا تحرك أي صنف من منطقة الحجر أو المرفوضات أو المرتجعات أو المسحوبات إلا بقرار المدير الصيدلي.',
        'أصناف الثلاجة لا تبقى خارج الثلاجة أبداً، وأي باب ثلاجة مفتوح أو إنذار: أبلغ فوراً.',
        'أي عبوة مكسورة أو مبللة أو منتهية الصلاحية أو مشكوك فيها: افصلها ولا تصرفها، وأبلغ مديرك.',
        'لا تأكل ولا تشرب ولا تدخن داخل مناطق التخزين، وحافظ على نظافة مكانك.',
        'إذا أخطأت: أبلغ فوراً. الإبلاغ عن الخطأ لا يُعاقب عليه، إخفاؤه هو المخالفة.',
        'لا توقّع على شيء لم تره بنفسك، ولا تسجّل قراءة أو رقماً لم تقِسه.',
    ];

    private static ?array $index = null;

    public static function all(): Collection
    {
        return collect(self::CARDS)->map(fn ($c, $k) => ['key' => $k, 'title' => $c[0], 'jd' => $c[1], 'aliases' => $c[2], 'head' => $c[3], 'roles' => $c[4]]);
    }

    public static function get(string $key): ?array
    {
        return self::all()->get($key);
    }

    /** بطاقة المستخدم: من وظيفته (job_code) ثم من دوره */
    public static function forUser(User $u): ?array
    {
        $code = $u->job_code ?: ($u->isClientAdmin() ? 'JD-MG-01' : null);
        if ($code && isset(self::CARDS[$code])) {
            return self::get($code);
        }
        $role = $u->role?->key;

        return $role ? self::all()->first(fn ($c) => in_array($role, $c['roles'], true)) : null;
    }

    /** تقسيم خانة «المسؤول» إلى أسماء */
    private static function owners(string $s): array
    {
        $parts = preg_split('/\s*[·،,\/]\s*|\s+و(?=(?:ال|م))/u', trim($s));

        return array_values(array_filter(array_map('trim', $parts)));
    }

    /** نص الخطوة: إزالة أقواس الفراغات [ … ] */
    public static function clean(string $t): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['[', ']'], '', $t)));
    }

    /**
     * فهرس الخطوات من كل الإجراءات: [['sop', 'sopTitle', 'phase', 'text', 'record', 'owners' => []]]
     */
    private static function index(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }
        $out = [];
        foreach (SopLibrary::all() as $code => $sop) {
            foreach ($sop['sections'] ?? [] as $sec) {
                foreach ($sec['blocks'] ?? [] as $b) {
                    if (($b['type'] ?? '') !== 'steps') {
                        continue;
                    }
                    foreach ($b['groups'] ?? [] as $g) {
                        foreach ($g['rows'] ?? [] as $r) {
                            $out[] = [
                                'sop' => $code, 'sopTitle' => $sop['title'],
                                'phase' => trim(preg_replace('/^[\p{L}]{1,2}\.\s*/u', '', (string) ($g['title'] ?? ''))),
                                'text' => self::clean((string) ($r[0] ?? '')), 'record' => trim((string) ($r[2] ?? '')),
                                'owners' => self::owners((string) ($r[1] ?? '')),
                            ];
                        }
                    }
                }
            }
        }

        return self::$index = $out;
    }

    /**
     * خطوات البطاقة مجمعة: [sop => ['title', 'phases' => [phase => [['text', 'record', 'shared']]]]]
     * $sop لحصر البطاقة في إجراء واحد
     */
    public static function steps(array $card, ?string $sop = null): array
    {
        $mine = $card['aliases'];
        $all = array_merge(self::ALL, $card['head'] ? self::HEADS : []);
        $out = [];
        foreach (self::index() as $s) {
            if ($sop && $s['sop'] !== $sop) {
                continue;
            }
            $isMine = (bool) array_intersect($s['owners'], $mine);
            $isAll = ! $isMine && array_intersect($s['owners'], $all);
            if (! $isMine && ! $isAll) {
                continue;
            }
            $out[$s['sop']]['title'] ??= $s['sopTitle'];
            $out[$s['sop']]['phases'][$s['phase']][] = [
                'text' => $s['text'], 'record' => in_array($s['record'], ['—', '-', ''], true) ? null : $s['record'],
                'shared' => $isAll, 'with' => array_values(array_diff($s['owners'], $mine)),
            ];
        }

        return $out;
    }

    /** عدد الخطوات (للفهرس) */
    public static function count(array $card): int
    {
        return collect(self::steps($card))->sum(fn ($s) => collect($s['phases'])->sum(fn ($p) => count($p)));
    }

    /** الوظائف التي لها خطوات في إجراء معين */
    public static function forSop(string $sop): Collection
    {
        // بطاقة واحدة لكل مجموعة أسماء (الجملة والقطاعي لهما نفس الخطوات: تكفي واحدة)
        $seen = [];

        return self::all()->filter(function ($c) use ($sop, &$seen) {
            $sig = implode('|', $c['aliases']);
            if (isset($seen[$sig])) {
                return false;
            }
            $has = collect(self::index())->contains(fn ($s) => $s['sop'] === $sop && array_intersect($s['owners'], $c['aliases']));
            if ($has) {
                $seen[$sig] = true;
            }

            return $has;
        })->values();
    }

    /** المدير المباشر من الوصف الوظيفي */
    public static function manager(array $card): ?string
    {
        $job = $card['jd'] ? JobLibrary::get($card['jd']) : null;

        if (! empty($job['manager'])) {
            return trim(preg_split('/[،(]/u', $job['manager'])[0]);
        }

        return match ($card['key']) {
            'CL', 'RT' => 'أمين المخزن', 'SF' => 'مدير المخزن', 'IT' => 'مدير المخزن', default => null,
        };
    }
}