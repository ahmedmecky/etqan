<?php

namespace App\Support;

use App\Models\DailyTask;
use App\Models\DailyTaskLog;
use App\Models\TempPoint;
use App\Models\TempReading;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * «مهام اليوم»: قائمة مهام يومية لكل مستخدم
 *   - قراءات الحرارة (من جولات الحرارة في الإعدادات) لمن يسجلها
 *   - المهام اليومية الثابتة التي يحددها مدير المخزن أو الجودة لأدوار معينة
 * المهمة التي فات موعدها ولم تُنجز «متأخرة»، ويُرسل عنها إشعار (etqan:daily-check كل ٣٠ دقيقة).
 */
class DailyTasks
{
    private const TZ = 'Africa/Cairo';

    /** يرون كل المهام ويتابعونها (وينفذون المهام التي لم يُحدد لها دور) */
    public const MANAGER_ROLES = ['quality_manager', 'pharmacist_manager'];

    /** مهام مقترحة من الإجراءات (يضيفها مدير المخزن بضغطة ثم يعدلها) */
    public const SUGGESTIONS = [
        ['title' => 'نظافة مناطق التخزين والاستلام اليومية وتسجيلها', 'sop' => 'SH-003', 'roles' => ['wholesale_storekeeper', 'retail_storekeeper', 'receiving_storekeeper'], 'due' => '11:00'],
        ['title' => 'مراجعة منشورات هيئة الدواء عن الأدوية المغشوشة والمسحوبة', 'sop' => 'SH-011', 'roles' => ['pharmacist_manager'], 'due' => '12:00'],
        ['title' => 'مراجعة منطقة الحجر والمرفوضات والمرتجعات وقرارها', 'sop' => 'SH-002', 'roles' => ['pharmacist_manager'], 'due' => '14:00'],
        ['title' => 'التأكد من إغلاق أبواب الثلاجات وعمل الإنذار قبل نهاية اليوم', 'sop' => 'SH-030', 'roles' => ['receiving_storekeeper', 'wholesale_storekeeper', 'retail_storekeeper'], 'due' => '17:30'],
        ['title' => 'مراجعة الطلبيات المتبقية وأصناف الثلاجة قبل خروج السيارات', 'sop' => 'SH-004', 'roles' => ['distribution_supervisor'], 'due' => '10:00'],
        ['title' => 'جولة الأمن وإغلاق المخزن وتسجيلها', 'sop' => 'SH-029', 'roles' => ['security'], 'due' => '20:00'],
    ];

    public const DAYS = ['6' => 'السبت', '0' => 'الأحد', '1' => 'الاثنين', '2' => 'الثلاثاء', '3' => 'الأربعاء', '4' => 'الخميس', '5' => 'الجمعة'];

    public static function isManager(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole(...self::MANAGER_ROLES);
    }

    /** إعداد المهام: مدير المخزن ومدير الجودة */
    public static function canSetup(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('quality_manager');
    }

    /** هل المستخدم مسؤول عن تنفيذ المهمة؟ */
    public static function assigned(User $u, DailyTask $t): bool
    {
        $roles = $t->roles ?? [];

        return $roles ? ($u->role && in_array($u->role->key, $roles, true)) : self::isManager($u);
    }

    public static function canDo(User $u, DailyTask $t): bool
    {
        return self::assigned($u, $t) || self::isManager($u);
    }

    /**
     * قائمة اليوم للمستخدم
     * ['items' => [[key, kind, title, sub, due, state(done|late|soon), url, task?, log?, mine]], 'done' => n, 'total' => n, 'late' => n]
     */
    public static function forUser(User $u, ?Carbon $date = null): array
    {
        $date ??= Carbon::today(self::TZ);
        $now = Carbon::now(self::TZ);
        $isToday = $date->isSameDay($now);
        $time = $isToday ? $now->format('H:i') : '23:59';
        $manager = self::isManager($u);
        $items = [];

        // قراءات الحرارة
        if ($date->dayOfWeek !== Carbon::FRIDAY && ($manager || Access::can($u, 'temperature', 'use'))) {
            $points = TempPoint::query()->where('is_active', true)->count();
            if ($points) {
                $done = TempReading::query()->whereDate('date', $date->toDateString())->get(['shift'])->groupBy('shift')->map->count();
                $late = $isToday ? Carbon::now(self::TZ)->subHours(TempRounds::GRACE_HOURS)->format('H:i') : '23:59';
                foreach (TempRounds::times($u->tenant_id) as $k => $tm) {
                    $n = (int) ($done[$k] ?? 0);
                    $items[] = [
                        'key' => 'temp-' . $k, 'kind' => 'temp', 'title' => 'قراءة الحرارة ' . TempRounds::label((string) $k, $u->tenant_id, false),
                        'sub' => $n >= $points ? 'كل النقاط ✓' : "{$n} من {$points} نقطة", 'due' => $tm,
                        'state' => $n >= $points ? 'done' : ($tm <= $late ? 'late' : 'soon'),
                        'url' => route('temperature.round', ['date' => $date->toDateString(), 'shift' => $k]), 'mine' => true, 'sop' => 'SH-030',
                    ];
                }
            }
        }

        // المهام الثابتة
        $logs = DailyTaskLog::query()->with('doer')->whereDate('date', $date->toDateString())->get()->keyBy('task_id');
        foreach (self::tasksOn($date) as $t) {
            $mine = self::assigned($u, $t);
            if (! $mine && ! $manager) {
                continue;
            }
            $log = $logs->get($t->id);
            $items[] = [
                'key' => 'task-' . $t->id, 'kind' => 'task', 'title' => $t->title,
                'sub' => $log ? '✓ ' . ($log->doer?->displayName() ?? '') . ' ' . $log->done_at?->timezone(self::TZ)->format('H:i') . ($log->note ? ' · ' . $log->note : '') : self::rolesText($t),
                'due' => $t->due(), 'state' => $log ? 'done' : ($t->due() <= $time ? 'late' : 'soon'),
                'url' => route('daily.index'), 'task' => $t, 'log' => $log, 'mine' => $mine || self::canDo($u, $t), 'sop' => $t->sop,
            ];
        }

        usort($items, fn ($a, $b) => [$a['state'] === 'done' ? 1 : 0, $a['due']] <=> [$b['state'] === 'done' ? 1 : 0, $b['due']]);
        $c = collect($items);

        return ['items' => $items, 'done' => $c->where('state', 'done')->count(), 'total' => $c->count(), 'late' => $c->where('state', 'late')->count()];
    }

    /** المهام الفعالة في هذا اليوم */
    public static function tasksOn(Carbon $date): Collection
    {
        return DailyTask::query()->where('is_active', true)->orderBy('due_time')->orderBy('sort')->get()
            ->filter(fn ($t) => $t->runsOn($date->dayOfWeek))->values();
    }

    public static function rolesText(DailyTask $t): string
    {
        $roles = $t->roles ?? [];
        if (! $roles) {
            return 'مدير الجودة · المدير الصيدلي';
        }
        $names = \App\Models\Role::query()->whereIn('key', $roles)->pluck('name_ar')->all();

        return implode(' · ', $names);
    }

    /**
     * المتأخر اليوم لمخزن (للإشعارات): [['tag', 'title', 'body', 'url', 'users' => Collection]]
     */
    public static function missing(int $tenantId): array
    {
        $now = Carbon::now(self::TZ);
        $date = $now->copy()->startOfDay();
        $users = Notifier::users($tenantId);
        $out = [];

        if ($date->dayOfWeek !== Carbon::FRIDAY) {
            $points = TempPoint::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('is_active', true)->count();
            if ($points) {
                $done = TempReading::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->whereDate('date', $date->toDateString())
                    ->get(['shift'])->groupBy('shift')->map->count();
                foreach (TempRounds::dueOn($date, $tenantId) as $k) {
                    $n = (int) ($done[$k] ?? 0);
                    if ($n < $points) {
                        $out[] = [
                            'tag'   => 'miss-temp-' . $date->format('Ymd') . '-' . $k,
                            'title' => 'لم تُسجل ' . TempRounds::label($k, $tenantId),
                            'body'  => "قراءة الحرارة: سُجلت {$n} من {$points} نقطة",
                            'url'   => route('temperature.round', ['shift' => $k]),
                            'users' => $users->filter(fn ($u) => ! self::isManager($u) && Access::can($u, 'temperature', 'use'))->values(),
                        ];
                    }
                }
            }
        }

        $logs = DailyTaskLog::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->whereDate('date', $date->toDateString())->pluck('task_id')->flip();
        $tasks = DailyTask::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('is_active', true)->get()
            ->filter(fn ($t) => $t->runsOn($date->dayOfWeek) && $t->due() <= $now->format('H:i') && ! $logs->has($t->id));
        foreach ($tasks as $t) {
            $out[] = [
                'tag'   => 'miss-task-' . $date->format('Ymd') . '-' . $t->id,
                'title' => 'مهمة يومية متأخرة',
                'body'  => $t->title . ' · الموعد ' . $t->due() . ($t->sop ? ' · ' . $t->sop : ''),
                'url'   => route('daily.index'),
                'users' => $users->filter(fn ($u) => ($t->roles ?? []) && $u->role && in_array($u->role->key, $t->roles, true))->values(),
            ];
        }

        return $out;
    }
}
