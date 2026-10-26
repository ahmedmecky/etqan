<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantJob;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * مكتبة الأوصاف الوظيفية (٣٠ وظيفة · resources/data/jobs.json)
 * النص ثابت كما اعتُمد (التحكم في الوثائق)، والمخزن يملأ الفراغات [ … ] فقط.
 * كل وصف: sections[] → blocks[] (p · list · kv · table · group)؛ مجموعات المهام في قسم «الواجبات والمسؤوليات».
 */
class JobLibrary
{
    public const DEPTS = [
        'MG' => 'الإدارة', 'WH' => 'المخازن', 'DS' => 'التوزيع والحركة', 'SL' => 'المبيعات',
        'CM' => 'التجاري والمشتريات', 'FN' => 'الحسابات', 'HR' => 'الموارد البشرية',
    ];

    /** الوظيفة المقترحة لكل دور نظام (عند إنشاء مستخدم) */
    public const ROLE_DEFAULT = [
        'client_admin' => 'JD-MG-01', 'receiving_storekeeper' => 'JD-WH-01', 'receiving_worker' => 'JD-WH-02',
        'wholesale_storekeeper' => 'JD-WH-03', 'retail_storekeeper' => 'JD-WH-04', 'control' => 'JD-WH-05', 'picker' => 'JD-WH-07',
        'driver' => 'JD-DS-01', 'distribution_supervisor' => 'JD-DS-03', 'fleet_supervisor' => 'JD-DS-04',
        'telesales' => 'JD-SL-01', 'wholesale_sales_manager' => 'JD-SL-03', 'retail_sales_supervisor' => 'JD-SL-04', 'sales_rep' => 'JD-SL-05',
        'commercial_director' => 'JD-CM-01', 'data_entry' => 'JD-CM-02', 'purchasing_officer' => 'JD-CM-03',
        'accountant' => 'JD-FN-01', 'accounts_manager' => 'JD-FN-06', 'hr_manager' => 'JD-HR-01', 'hr_officer' => 'JD-HR-02',
    ];

    private static ?array $jobs = null;

    public static function all(): Collection
    {
        if (self::$jobs === null) {
            $list = json_decode(file_get_contents(resource_path('data/jobs.json')), true) ?: [];
            self::$jobs = collect($list)->keyBy('code')->all();
        }

        return collect(self::$jobs);
    }

    public static function get(?string $code): ?array
    {
        return $code ? (self::all()[$code] ?? null) : null;
    }

    public static function dept(string $code): string
    {
        return self::DEPTS[substr($code, 3, 2)] ?? '';
    }

    public static function title(?string $code): string
    {
        return self::get($code)['title'] ?? '';
    }

    /** مجموعات المهام: [key, title, items] */
    public static function groups(array $job): array
    {
        $s = collect($job['sections'])->first(fn ($s) => str_starts_with($s['title'], 'الواجبات'));

        return collect($s['blocks'] ?? [])->where('type', 'group')->values()->all();
    }

    public static function section(array $job, string $startsWith): ?array
    {
        return collect($job['sections'])->first(fn ($s) => str_starts_with($s['title'], $startsWith));
    }

    /* ---------------- الفراغات ---------------- */

    /** كل الفراغات بالترتيب: [i => ['raw' => '[ … ]', 'ctx' => 'النص حولها', 'sec' => 'القسم']] */
    public static function placeholders(array $job): array
    {
        $out = [];
        foreach ($job['sections'] as $s) {
            self::walk($s['blocks'], function (string $str, string $row = '') use (&$out, $s) {
                if (preg_match_all('/\[[^\]]*\]/u', $str, $m, PREG_OFFSET_CAPTURE)) {
                    foreach ($m[0] as [$raw, $pos]) {
                        $before = trim(mb_substr(substr($str, 0, $pos), -60));
                        $out[] = ['raw' => $raw, 'ctx' => $before !== '' ? $before : $row, 'sec' => $s['title']];
                    }
                }

                return $str;
            });
        }

        return $out;
    }

    /** القيمة الافتراضية لفراغ: اسم المخزن، أو النص المقترح بدون الأقواس، أو فراغ للكتابة */
    public static function defaultFor(string $raw, ?Tenant $tenant): string
    {
        $inner = trim(mb_substr($raw, 1, -1));
        if (str_contains($inner, 'اسم المخزن')) return $tenant?->name_ar ?? '';
        if ($inner === '' || preg_match('/^[\s٪%]*$|^\s*(جنيه|عميل|يوم|دقيقة|مرة|ساعة)\s*$/u', $inner)) return '';

        return $inner;
    }

    /** الوصف بعد ملء الفراغات (الفارغ يُطبع خطاً للكتابة اليدوية) */
    public static function fill(array $job, array $values, ?Tenant $tenant): array
    {
        $i = 0;
        foreach ($job['sections'] as &$s) {
            $s['blocks'] = self::walk($s['blocks'], function (string $str, string $row = '') use (&$i, $values, $tenant) {
                return preg_replace_callback('/\[[^\]]*\]/u', function ($m) use (&$i, $values, $tenant) {
                    $v = trim((string) ($values[$i] ?? ''));
                    if ($v === '') $v = self::defaultFor($m[0], $tenant);
                    $unit = trim(preg_replace('/[\[\]\s]+/u', ' ', $m[0]));
                    $i++;
                    if ($v !== '') return $v . (preg_match('/^[\d.]+$/', $v) && $unit !== '' && ! preg_match('/^\d/', $unit) ? ' ' . $unit : '');

                    return '……… ' . (preg_match('/^(٪|%|جنيه|عميل|يوم|دقيقة|مرة|ساعة)$/u', $unit) ? $unit : '');
                }, $str);
            });
        }

        return $job;
    }

    private static function walk(array $blocks, callable $fn): array
    {
        foreach ($blocks as &$b) {
            foreach (['text', 'title'] as $k) if (isset($b[$k])) $b[$k] = $fn($b[$k]);
            if (isset($b['items'])) $b['items'] = array_map($fn, $b['items']);
            if (isset($b['head'])) $b['head'] = array_map($fn, $b['head']);
            if (isset($b['rows'])) $b['rows'] = array_map(fn ($r) => array_map(fn ($c) => $fn($c, (string) ($r[0] ?? '')), $r), $b['rows']);
        }

        return $blocks;
    }

    /* ---------------- الهيكل الوظيفي للمخزن ---------------- */

    /** إعدادات كل وظيفة في المخزن (بدون صف = لها موظف مخصص) */
    public static function settings(): Collection
    {
        return TenantJob::query()->get()->keyBy('job_code');
    }

    /** عدد الموظفين الفعالين في كل وظيفة (أساسية أو إضافية) */
    public static function headcount(int $tenantId): array
    {
        $n = [];
        User::query()->where('tenant_id', $tenantId)->where('is_active', true)->get(['job_code', 'extra_jobs'])
            ->each(function ($u) use (&$n) { foreach ($u->jobCodes() as $c) $n[$c] = ($n[$c] ?? 0) + 1; });

        return $n;
    }

    /** هل مهام الوظيفة تحتاج توزيعاً؟ (بدون موظف مخصص، أو لها موظف مخصص لكنها شاغرة) */
    public static function needsCover(string $code, Collection $settings, array $count): bool
    {
        $mode = $settings[$code]->mode ?? 'staffed';

        return $mode === 'covered' || ($mode === 'staffed' && ($count[$code] ?? 0) === 0);
    }

    /** المهام الإضافية المحولة لوظائف هذا الموظف من الوظائف الشاغرة: [[from, fromTitle, group]] */
    public static function inherited(array $codes, Collection $settings, array $count): array
    {
        $out = [];
        foreach ($settings as $code => $t) {
            if (! self::needsCover($code, $settings, $count) || empty($t->reassign) || in_array($code, $codes, true)) continue;
            $job = self::get($code);
            if (! $job) continue;
            foreach (self::groups($job) as $g) {
                if (in_array($t->reassign[$g['key']] ?? null, $codes, true)) {
                    $out[] = ['from' => $code, 'fromTitle' => $job['title'], 'group' => $g, 'blanks' => $t->blanks ?? []];
                }
            }
        }

        return $out;
    }
}