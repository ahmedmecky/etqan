<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * جولات قراءة الحرارة اليومية (SH-030): عددها وأوقاتها من إعدادات المخزن
 *   المفتاح '1' … '6' (القراءات القديمة am / pm نُقلت إلى 1 / 2)
 *   الجولة «مستحقة» بعد وقتها بثلاث ساعات (تظهر ناقصة في الرئيسية والتقرير إذا لم تُسجل)
 */
class TempRounds
{
    public const MAX = 6;
    public const GRACE_HOURS = 3;
    private const TZ = 'Africa/Cairo';

    /** الأوقات المقترحة إذا لم يكتب المخزن أوقاته */
    private const PRESETS = [
        1 => ['10:00'],
        2 => ['09:00', '17:00'],
        3 => ['09:00', '13:00', '17:00'],
        4 => ['09:00', '12:00', '15:00', '18:00'],
        5 => ['08:00', '11:00', '14:00', '17:00', '20:00'],
        6 => ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
    ];

    private const AR = ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'];

    private static array $cache = [];

    private static function tenant(?int $tenantId): int
    {
        return (int) ($tenantId ?? auth()->user()?->tenant_id ?? 0);
    }

    public static function count(?int $tenantId = null): int
    {
        $t = self::tenant($tenantId);

        return self::$cache[$t]['n'] ??= max(1, min(self::MAX, (int) round((float) Settings::get($t, 'temp_rounds') ?: 2)));
    }

    /** الأوقات: ['1' => '09:00', '2' => '13:00', …] */
    public static function times(?int $tenantId = null): array
    {
        $t = self::tenant($tenantId);
        if (isset(self::$cache[$t]['times'])) {
            return self::$cache[$t]['times'];
        }
        $n = self::count($t);
        $typed = collect(preg_split('/[\s,،;]+/u', (string) Settings::get($t, 'temp_times')))
            ->map(fn ($x) => self::time($x))->filter()->unique()->sort()->values()->all();
        $list = count($typed) >= $n ? array_slice($typed, 0, $n) : self::PRESETS[$n];

        $out = [];
        foreach ($list as $i => $tm) {
            $out[(string) ($i + 1)] = $tm;
        }

        return self::$cache[$t]['times'] = $out;
    }

    public static function keys(?int $tenantId = null): array
    {
        return array_map('strval', array_keys(self::times($tenantId)));
    }

    /** «القراءة ٢ · 13:00» */
    public static function label(?string $key, ?int $tenantId = null, bool $withTime = true): string
    {
        $key = self::normal($key);
        $tm = self::times($tenantId)[$key] ?? null;

        return 'القراءة ' . strtr($key, self::AR) . ($withTime && $tm ? ' · ' . $tm : '');
    }

    /** اختصار للجداول: «ق٢» */
    public static function short(?string $key): string
    {
        return 'ق' . strtr(self::normal($key), self::AR);
    }

    /** القراءات القديمة (الصباح / المساء) */
    public static function normal(?string $key): string
    {
        return match ($key) { 'am' => '1', 'pm' => '2', null, '' => '1', default => (string) $key };
    }

    /** الجولة الحالية: آخر جولة بدأ وقتها (أو الأولى قبل أول وقت) */
    public static function current(?int $tenantId = null): string
    {
        $now = Carbon::now(self::TZ)->format('H:i');
        $cur = '1';
        foreach (self::times($tenantId) as $k => $tm) {
            if ($tm <= $now) {
                $cur = (string) $k;
            }
        }

        return $cur;
    }

    /**
     * الجولات المستحقة في يوم: كل الجولات للأيام السابقة · اليوم: ما مر على وقته ٣ ساعات · الجمعة: لا شيء
     */
    public static function dueOn(Carbon $date, ?int $tenantId = null): array
    {
        $today = Carbon::today(self::TZ);
        if ($date->dayOfWeek === Carbon::FRIDAY || $date->gt($today)) {
            return [];
        }
        if (! $date->isSameDay($today)) {
            return self::keys($tenantId);
        }
        $limit = Carbon::now(self::TZ)->subHours(self::GRACE_HOURS)->format('H:i');

        return array_values(array_filter(self::keys($tenantId), fn ($k) => self::times($tenantId)[$k] <= $limit));
    }

    private static function time(string $x): ?string
    {
        $x = strtr(trim($x), array_flip(self::AR) + ['٫' => ':', '.' => ':']);
        if (! preg_match('/^(\d{1,2})(?::(\d{2}))?$/', $x, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) ($m[2] ?? 0);

        return $h <= 23 && $i <= 59 ? sprintf('%02d:%02d', $h, $i) : null;
    }

    public static function forget(): void
    {
        self::$cache = [];
    }
}