<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\DailyTaskLog;
use App\Models\Role;
use App\Support\DailyTasks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «مهام اليوم»: تنفيذ المهام اليومية · إعدادها (مدير المخزن ومدير الجودة)
 */
class DailyTaskController extends Controller
{
    private const TZ = 'Africa/Cairo';

    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today(self::TZ);
        $date = $today->copy();
        if (DailyTasks::isManager($user) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date'))) {
            $d = Carbon::parse($request->query('date'), self::TZ)->startOfDay();
            $date = $d->gt($today) ? $today : $d;
        }

        return view('daily.index', [
            'list'     => DailyTasks::forUser($user, $date),
            'date'     => $date,
            'isToday'  => $date->isSameDay($today),
            'manager'  => DailyTasks::isManager($user),
            'setup'    => DailyTasks::canSetup($user),
            'tasks'    => DailyTasks::canSetup($user) ? DailyTask::query()->orderBy('due_time')->orderBy('sort')->get() : collect(),
            'roles'    => Role::query()->assignableByClient()->get(['key', 'name_ar']),
            'days'     => DailyTasks::DAYS,
            'suggest'  => DailyTasks::SUGGESTIONS,
        ]);
    }

    /** تم تنفيذ المهمة اليوم */
    public function done(Request $request, DailyTask $task): RedirectResponse
    {
        $user = $request->user();
        abort_unless(DailyTasks::canDo($user, $task), 403, 'هذه المهمة ليست من مهامك.');
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $today = Carbon::today(self::TZ)->toDateString();

        DailyTaskLog::query()->updateOrCreate(
            ['task_id' => $task->id, 'date' => $today],
            ['tenant_id' => $user->tenant_id, 'done_by' => $user->id, 'done_at' => now(), 'note' => $data['note'] ?? null]
        );

        return back()->with('status', "✓ تم: {$task->title}");
    }

    /** إلغاء التنفيذ (في نفس اليوم: من نفذها أو المدير) */
    public function undo(Request $request, DailyTask $task): RedirectResponse
    {
        $user = $request->user();
        $log = DailyTaskLog::query()->where('task_id', $task->id)->whereDate('date', Carbon::today(self::TZ)->toDateString())->first();
        if ($log) {
            abort_unless((int) $log->done_by === (int) $user->id || DailyTasks::isManager($user), 403);
            $log->delete();
        }

        return back()->with('status', "أُلغي تنفيذ «{$task->title}».");
    }

    /* ===================== الإعداد ===================== */

    public function store(Request $request): RedirectResponse
    {
        abort_unless(DailyTasks::canSetup($request->user()), 403);
        DailyTask::create($this->validated($request) + ['tenant_id' => $request->user()->tenant_id, 'updated_by' => $request->user()->id]);

        return back()->with('status', 'تمت إضافة المهمة اليومية.');
    }

    public function update(Request $request, DailyTask $task): RedirectResponse
    {
        abort_unless(DailyTasks::canSetup($request->user()), 403);
        $task->update($this->validated($request) + ['updated_by' => $request->user()->id]);

        return back()->with('status', "تم حفظ «{$task->title}».");
    }

    public function toggle(Request $request, DailyTask $task): RedirectResponse
    {
        abort_unless(DailyTasks::canSetup($request->user()), 403);
        $task->update(['is_active' => ! $task->is_active, 'updated_by' => $request->user()->id]);

        return back()->with('status', $task->is_active ? "أُعيد تفعيل «{$task->title}»." : "أُوقفت «{$task->title}».");
    }

    /** إضافة المهام المقترحة من الإجراءات (ما لم يُضف منها) */
    public function seed(Request $request): RedirectResponse
    {
        abort_unless(DailyTasks::canSetup($request->user()), 403);
        $have = DailyTask::query()->pluck('title')->all();
        $n = 0;
        foreach (DailyTasks::SUGGESTIONS as $i => $s) {
            if (in_array($s['title'], $have, true)) {
                continue;
            }
            DailyTask::create(['tenant_id' => $request->user()->tenant_id, 'title' => $s['title'], 'sop' => $s['sop'], 'roles' => $s['roles'],
                'due_time' => $s['due'], 'days' => '012346', 'sort' => $i + 1, 'updated_by' => $request->user()->id]);
            $n++;
        }

        return back()->with('status', $n ? "تمت إضافة {$n} مهمة مقترحة. عدّل الأوقات والمسؤولين حسب مخزنك." : 'المهام المقترحة موجودة بالفعل.');
    }

    private function validated(Request $request): array
    {
        $keys = Role::query()->assignableByClient()->pluck('key')->all();
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:160'],
            'sop'      => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
            'due_time' => ['required', 'date_format:H:i'],
            'roles'    => ['nullable', 'array'],
            'roles.*'  => [Rule::in($keys)],
            'days'     => ['required', 'array', 'min:1'],
            'days.*'   => [Rule::in(['0', '1', '2', '3', '4', '5', '6'])],
            'sort'     => ['nullable', 'integer', 'between:0,999'],
        ], [
            'title.required'    => 'اكتب المهمة.',
            'due_time.required' => 'حدد موعد الإنجاز.',
            'days.required'     => 'اختر يوماً واحداً على الأقل.',
            'sop.regex'         => 'كود الإجراء حروف إنجليزية وأرقام فقط (مثل SH-003).',
        ]);

        return [
            'title' => trim($data['title']), 'sop' => isset($data['sop']) ? strtoupper(trim($data['sop'])) : null,
            'due_time' => $data['due_time'], 'roles' => array_values($data['roles'] ?? []),
            'days' => implode('', array_unique($data['days'])), 'sort' => (int) ($data['sort'] ?? 0),
        ];
    }
}