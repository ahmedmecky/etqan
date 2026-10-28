<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * المهام المكلف بها (لمرة واحدة): المدير يكلف موظفاً · أو الشخص يضع لنفسه
 *   إشعار عند التكليف · تذكير قبل الموعد بيوم ويوم الموعد · إشعار التأخير يومياً · إشعار للمكلِّف عند الإنجاز
 * تظهر أيضاً في «مهام اليوم» وفي لوحة الرئيسية (المستحقة اليوم والمتأخرة).
 */
class Tasks
{
    public const TZ = 'Africa/Cairo';

    public const PRIORITIES = ['urgent' => 'عاجل', 'important' => 'مهم', 'normal' => 'عادي'];

    /** يكلفون غيرهم: مدير المخزن والمديرون والمشرفون وأمناء المخازن */
    public const ASSIGNER_ROLES = [
        'ceo', 'managing_director', 'general_manager', 'pharmacist_manager', 'quality_manager', 'hr_manager', 'hr_officer',
        'commercial_director', 'accounts_manager', 'wholesale_sales_manager', 'retail_sales_supervisor',
        'distribution_supervisor', 'fleet_supervisor', 'wholesale_storekeeper', 'retail_storekeeper', 'receiving_storekeeper',
        'it_officer', 'safety_officer',
    ];

    public static function canAssign(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole(...self::ASSIGNER_ROLES);
    }

    /** يرى كل مهام المخزن */
    public static function seesAll(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('quality_manager', 'pharmacist_manager', 'general_manager', 'ceo', 'managing_director');
    }

    /** من يمكن تكليفه */
    public static function assignees(User $u): Collection
    {
        if (! self::canAssign($u)) {
            return collect([$u]);
        }

        return User::query()->with('role')->where('tenant_id', $u->tenant_id)->where('is_active', true)->get()
            ->sortBy(fn ($x) => [$x->id === $u->id ? 0 : 1, $x->role?->sort ?? 99, $x->displayName()])->values();
    }

    public static function canSee(User $u, Task $t): bool
    {
        return (int) $t->assigned_to === $u->id || (int) $t->created_by === $u->id || self::seesAll($u);
    }

    /** تعديل وإلغاء: من أنشأها أو مدير المخزن */
    public static function canEdit(User $u, Task $t): bool
    {
        return (int) $t->created_by === $u->id || $u->isClientAdmin();
    }

    /** إنجاز: المكلف بها أو من أنشأها */
    public static function canDo(User $u, Task $t): bool
    {
        return (int) $t->assigned_to === $u->id || (int) $t->created_by === $u->id || $u->isClientAdmin();
    }

    /** المفتوح للمستخدم: [open, late, today] */
    public static function counts(User $u): array
    {
        $open = Task::query()->where('assigned_to', $u->id)->where('status', 'open')->get();

        return ['open' => $open->count(), 'late' => $open->filter(fn ($t) => $t->state() === 'late')->count(),
            'today' => $open->filter(fn ($t) => $t->state() === 'today')->count()];
    }

    /** عناصر «مهام اليوم»: المستحقة اليوم أو المتأخرة (المفتوحة) + ما أُنجز في هذا اليوم */
    public static function dailyItems(User $u, Carbon $date): array
    {
        $d = $date->toDateString();
        $isToday = $date->isSameDay(Carbon::today(self::TZ));
        $rows = Task::query()->with('creator')->where('assigned_to', $u->id)
            ->where(function ($w) use ($d, $isToday) {
                $w->where(fn ($q) => $q->where('status', 'open')->whereDate('due_date', $isToday ? '<=' : '=', $d))
                  ->orWhere(fn ($q) => $q->where('status', 'done')->whereDate('done_at', $d));
            })->get();

        $out = [];
        foreach ($rows as $t) {
            $st = $t->state();
            $out[] = [
                'key' => 'job-' . $t->id, 'kind' => 'job', 'title' => $t->title,
                'sub' => ($t->priority === 'urgent' ? '⚡ عاجل · ' : ($t->priority === 'important' ? 'مهم · ' : ''))
                    . ($t->isSelf() ? 'مهمة شخصية' : 'من ' . ($t->creator?->displayName() ?? '')) . ($t->status === 'done' && $t->done_note ? ' · ' . $t->done_note : ''),
                'due' => $t->due_date->format('Y-m-d') < $d ? $t->due_date->format('m-d') : ($t->time() ?? 'اليوم'),
                'sort' => $t->due_date->format('Y-m-d') < $d ? '00:00' : ($t->time() ?? '23:58'),
                'state' => $st === 'done' ? 'done' : ($st === 'late' ? 'late' : 'soon'),
                'url' => route('tasks.index', ['open' => $t->id]), 'task' => $t, 'mine' => true, 'sop' => $t->sop,
            ];
        }

        return $out;
    }

    /* ===================== الإشعارات ===================== */

    /** عند التكليف (أو تغيير المكلف) */
    public static function notifyAssigned(Task $t): void
    {
        if ($t->isSelf()) {
            return;
        }
        $t->loadMissing('assignee', 'creator');
        Notifier::toUsers([$t->assignee], $t->tenant_id, 'task', null,
            ($t->priority === 'urgent' ? '⚡ مهمة عاجلة: ' : 'مهمة جديدة: ') . $t->title,
            trim(($t->details ? mb_substr($t->details, 0, 160) . "\n" : '') . 'الموعد: ' . $t->dueText() . ' · من ' . ($t->creator?->displayName() ?? '')),
            route('tasks.index', ['open' => $t->id]), 'task-' . $t->id, $t->priority === 'urgent');
    }

    /** عند الإنجاز: للمكلِّف */
    public static function notifyDone(Task $t): void
    {
        if ($t->isSelf() || ! $t->created_by) {
            return;
        }
        $t->loadMissing('creator', 'doer');
        Notifier::toUsers([$t->creator], $t->tenant_id, 'task', null, '✓ تمت المهمة: ' . $t->title,
            ($t->doer?->displayName() ?? '') . ($t->done_note ? ' · ' . $t->done_note : ''),
            route('tasks.index', ['open' => $t->id, 'tab' => 'given']), 'task-done-' . $t->id);
    }

    /**
     * التذكيرات (etqan:daily-check كل ٣٠ دقيقة):
     *   pre   : قبل الموعد بيوم (بعد ٨ ص) · today : يوم الموعد (بعد ٨ ص)
     *   over-YYYYMMDD : بعد فوات الموعد، مرة كل يوم للمكلف (وأول مرة للمكلِّف أيضاً)
     * يرجع عدد الإشعارات
     */
    public static function remind(int $tenantId, bool $dry = false, ?\Closure $line = null): int
    {
        $now = Carbon::now(self::TZ);
        if ($now->format('H:i') < '08:00') {
            return 0;
        }
        $today = $now->toDateString();
        $tomorrow = $now->copy()->addDay()->toDateString();
        $n = 0;

        $tasks = Task::withoutGlobalScope('tenant')->with('assignee', 'creator')->where('tenant_id', $tenantId)->where('status', 'open')
            ->whereDate('due_date', '<=', $tomorrow)->get();
        foreach ($tasks as $t) {
            $due = $t->due_date->format('Y-m-d');
            $created = $t->created_at?->timezone(self::TZ)->toDateString();
            $stage = null;
            if ($t->dueAt()->lt($now)) {
                $stage = 'over-' . $now->format('Ymd');
            } elseif ($due === $today && $created !== $today) {
                $stage = 'today';
            } elseif ($due === $tomorrow && $created !== $today) {
                $stage = 'pre';
            }
            if (! $stage || $stage === $t->remind_stage || ($stage === 'pre' && $t->remind_stage === 'today')) {
                continue;
            }
            $firstOver = str_starts_with($stage, 'over') && ! str_starts_with((string) $t->remind_stage, 'over');
            $prefix = str_starts_with($stage, 'over') ? 'مهمة متأخرة: ' : ($stage === 'today' ? 'مهمة موعدها اليوم: ' : 'تذكير · مهمة موعدها غداً: ');
            $title = $prefix . $t->title;
            $body = 'الموعد: ' . $t->dueText() . ($t->isSelf() ? '' : ' · من ' . ($t->creator?->displayName() ?? ''));
            if ($line) {
                $line("{$t->assignee?->displayName()} · {$title} · {$body}");
            }
            if (! $dry) {
                $users = [$t->assignee];
                Notifier::toUsers($users, $tenantId, 'task', null, $title, $body, route('tasks.index', ['open' => $t->id]), 'task-r-' . $t->id, $t->priority === 'urgent' || str_starts_with($stage, 'over'));
                if ($firstOver && ! $t->isSelf() && $t->creator) {
                    Notifier::toUsers([$t->creator], $tenantId, 'task', null, 'لم تُنجز في موعدها: ' . $t->title,
                        ($t->assignee?->displayName() ?? '') . ' · الموعد ' . $t->due_date->format('Y-m-d') . ($t->time() ? ' ' . $t->time() : ''),
                        route('tasks.index', ['open' => $t->id, 'tab' => 'given']), 'task-o-' . $t->id, true);
                }
                $t->forceFill(['remind_stage' => $stage])->saveQuietly();
            }
            $n++;
        }

        return $n;
    }
}