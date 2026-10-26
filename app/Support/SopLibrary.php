<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * مكتبة إجراءات التشغيل القياسية (٣٤ إجراء + نماذجها · resources/data/sops.json)
 * النص ثابت كما اعتُمد في إتقان، والمخزن يملأ الفراغات [ … ] فقط (مدد الحفظ · الحدود · الأوقات · الأسماء).
 * أي تعديل آخر: إصدار جديد بملف مرفوع من خلال التحكم في التغيير SH-027.
 * كل إجراء: sections[] → blocks[] (p · list · kv · table · steps) + forms[] (fields · parts[grid|checklist|lines] · signs)
 */
class SopLibrary
{
    /** مفاتيح لا تحتوي نصاً للطباعة */
    private const SKIP = ['type', 'code', 'no', 'key', 'widths', 'rows_n', 'extra', 'version', 'approvers', 'approvalNo', 'muted', 'history'];

    private static ?array $sops = null;

    public static function all(): Collection
    {
        if (self::$sops === null) {
            $list = json_decode(file_get_contents(resource_path('data/sops.json')), true) ?: [];
            self::$sops = collect($list)->keyBy('code')->all();
        }

        return collect(self::$sops);
    }

    public static function get(?string $code): ?array
    {
        return $code ? (self::all()[$code] ?? null) : null;
    }

    /** نموذج واحد من نماذج الإجراء */
    public static function form(array $sop, string $code): ?array
    {
        return collect($sop['forms'] ?? [])->firstWhere('code', $code);
    }

    /* ---------------- الفراغات ---------------- */

    /**
     * كل الفراغات بالترتيب: [i => ['raw' => '[ … ]', 'ctx' => 'النص قبلها', 'sec' => 'القسم']]
     * سجل التعديلات مستثنى (تواريخ إصدارات المكتبة)
     */
    public static function placeholders(array $sop): array
    {
        $out = [];
        $visit = function (string $str, string $sec, string $row) use (&$out) {
            if (preg_match_all('/\[[^\]]*\]/u', $str, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$raw, $pos]) {
                    $before = trim(mb_substr(substr($str, 0, $pos), -70));
                    $after = trim(mb_substr(substr($str, $pos + strlen($raw)), 0, 30));
                    $out[] = ['raw' => $raw, 'ctx' => $before !== '' ? $before : $row, 'after' => $after, 'sec' => $sec];
                }
            }

            return $str;
        };
        self::walkSop($sop, $visit);

        return $out;
    }

    /** الإجراء بعد ملء الفراغات (الفارغ يُطبع خطاً للكتابة اليدوية) */
    public static function fill(array $sop, array $values, ?Tenant $tenant): array
    {
        $i = 0;
        $fn = function (string $str) use (&$i, $values, $tenant) {
            return preg_replace_callback('/\[[^\]]*\]/u', function ($m) use (&$i, $values, $tenant) {
                $v = trim((string) ($values[$i] ?? ''));
                $i++;
                if ($v === '') {
                    $v = self::defaultFor($m[0], $tenant);
                }
                $u = self::unit($m[0]);
                if ($v === '') {
                    return '…………' . ($u !== '' ? ' ' . $u : '');
                }

                return $u !== '' && ! str_contains($v, $u) ? $v . ' ' . $u : $v;
            }, $str);
        };

        return self::walkSop($sop, fn ($s) => $fn($s));
    }

    /** القيمة الافتراضية لفراغ: اسم المخزن، أو النص المقترح بدون الأقواس، أو فراغ للكتابة */
    public static function defaultFor(string $raw, ?Tenant $tenant): string
    {
        $inner = trim(mb_substr($raw, 1, -1));
        if (str_contains($inner, 'اسم المخزن') || str_contains($inner, 'اسم الشركة')) {
            return $tenant?->name_ar ?? '';
        }
        if ($inner === '' || preg_match('/^[\s_٪%.\-…]*$/u', $inner) || in_array($inner, ['يُضاف', 'يضاف'], true)) {
            return '';
        }
        // [____ شهر] · [   يوم]: الرقم فارغ والوحدة معروفة
        if (preg_match('/^[_\s.…]+[^_\s.…]/u', $inner)) {
            return '';
        }

        return $inner;
    }

    /** الوحدة المكتوبة داخل الفراغ إن وجدت (شهر · يوم · سنوات …) لعرضها بجوار خانة الإدخال */
    public static function unit(string $raw): string
    {
        $inner = trim(mb_substr($raw, 1, -1));

        return preg_match('/^[_\s.…]+([^_\s.…].*)$/u', $inner, $m) ? trim($m[1]) : '';
    }

    /**
     * المرور على كل نص في الإجراء بالترتيب الثابت: الأقسام ثم النماذج
     * $fn(string $text, string $section, string $rowContext): string
     */
    private static function walkSop(array $sop, callable $fn): array
    {
        foreach ($sop['sections'] as &$s) {
            if (str_starts_with($s['title'], 'سجل التعديلات')) {
                continue;
            }
            $sec = $s['title'];
            $s['blocks'] = array_map(fn ($b) => self::walk($b, $fn, $sec, ''), $s['blocks']);
        }
        unset($s);
        foreach ($sop['forms'] as &$f) {
            $sec = 'نموذج ' . $f['code'] . ' ' . $f['title'];
            foreach (['note', 'fields', 'parts', 'signs'] as $k) {
                if (isset($f[$k])) {
                    $f[$k] = self::walk($f[$k], $fn, $sec, '');
                }
            }
        }

        return $sop;
    }

    private static function walk($node, callable $fn, string $sec, string $row)
    {
        if (is_string($node)) {
            return $fn($node, $sec, $row);
        }
        if (! is_array($node)) {
            return $node;
        }
        // صف جدول: أول خلية سياق لباقي الخلايا
        $isRow = array_is_list($node) && $node && collect($node)->every(fn ($c) => is_string($c));
        foreach ($node as $k => $v) {
            if (is_string($k) && in_array($k, self::SKIP, true)) {
                continue;
            }
            $ctx = $isRow ? trim(implode(' · ', array_filter([(string) ($node[0] ?? ''), $k > 0 ? (string) ($node[1] ?? '') : ''])), ' ·') : $row;
            $node[$k] = self::walk($v, $fn, $sec, $ctx !== '' ? $ctx : $row);
        }

        return $node;
    }
}