<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Support\Tasks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * المهام: مهامي (المكلف بها + الشخصية) · كلفت بها غيري · كل المهام (للمديرين)
 */
class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        $tabs = ['mine', 'given'];
        if (Tasks::seesAll($u)) {
            $tabs[] = 'all';
        }
        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'mine';
        $show = $request->query('show') === 'closed' ? 'closed' : 'open';

        $q = Task::query()->with('assignee.role', 'creator', 'doer');
        match ($tab) {
            'mine'  => $q->where('assigned_to', $u->id),
            'given' => $q->where('created_by', $u->id)->whereColumn('assigned_to', '!=', 'created_by'),
            default => null,
        };
        $show === 'open'
            ? $q->where('status', 'open')->orderBy('due_date')->orderByRaw('due_time is null')->orderBy('due_time')
            : $q->where('status', '!=', 'open')->where('updated_at', '>=', now()->subDays(60))->latest('updated_at');

        $tasks = $q->limit(300)->get();
        if ($show === 'open') {
            $rank = ['urgent' => 0, 'important' => 1, 'normal' => 2];
            $tasks = $tasks->sortBy(fn ($t) => [$t->state() === 'late' ? 0 : 1, $t->due_date->format('Y-m-d'), $rank[$t->priority] ?? 3, $t->time() ?? '99'])->values();
        }

        return view('tasks.index', [
            'tab'       => $tab,
            'tabs'      => $tabs,
            'show'      => $show,
            'tasks'     => $tasks,
            'assignees' => Tasks::assignees($u),
            'canAssign' => Tasks::canAssign($u),
            'counts'    => Tasks::counts($u),
            'givenOpen' => Task::query()->where('created_by', $u->id)->whereColumn('assigned_to', '!=', 'created_by')->where('status', 'open')->count(),
            'open'      => (int) $request->query('open', 0),
            'today'     => Carbon::today(Tasks::TZ)->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $this->validated($request);
        $t = Task::create($data + ['tenant_id' => $u->tenant_id, 'created_by' => $u->id, 'status' => 'open']);
        Tasks::notifyAssigned($t);

        return redirect()->route('tasks.index', ['tab' => $t->isSelf() ? 'mine' : 'given', 'open' => $t->id])
            ->with('status', $t->isSelf() ? 'تمت إضافة المهمة لمهامك.' : 'تم تكليف ' . $t->assignee->displayName() . ' بالمهمة وأُرسل له إشعار.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Tasks::canEdit($u, $task), 403, 'التعديل لمن أنشأ المهمة.');
        $data = $this->validated($request);
        $changedTo = (int) $data['assigned_to'] !== (int) $task->assigned_to;
        if ($changedTo || $data['due_date'] !== $task->due_date->format('Y-m-d') || ($data['due_time'] ?? null) !== $task->time()) {
            $data['remind_stage'] = null;
        }
        $task->update($data);
        if ($changedTo) {
            Tasks::notifyAssigned($task->fresh());
        }

        return back()->with('status', 'تم حفظ المهمة.');
    }

    public function done(Request $request, Task $task): RedirectResponse
    {
        $u = $request->user();
        abort_unless(Tasks::canDo($u, $task), 403, 'هذه المهمة ليست مكلفاً بها.');
        $v = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $task->update(['status' => 'done', 'done_at' => now(), 'done_by' => $u->id, 'done_note' => $v['note'] ?? null]);
        Tasks::notifyDone($task);

        return back()->with('status', "✓ تمت المهمة: {$task->title}");
    }

    public function reopen(Request $request, Task $task): RedirectResponse
    {
        abort_unless(Tasks::canDo($request->user(), $task), 403);
        $task->update(['status' => 'open', 'done_at' => null, 'done_by' => null, 'done_note' => null, 'remind_stage' => null]);

        return back()->with('status', "أُعيد فتح المهمة: {$task->title}");
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        abort_unless(Tasks::canEdit($request->user(), $task), 403, 'الإلغاء لمن أنشأ المهمة.');
        $task->update(['status' => 'cancelled']);

        return back()->with('status', "أُلغيت المهمة: {$task->title}");
    }

    private function validated(Request $request): array
    {
        $u = $request->user();
        $ids = Tasks::assignees($u)->pluck('id')->all();
        $v = $request->validate([
            'assigned_to' => ['required', 'integer', Rule::in($ids)],
            'title'       => ['required', 'string', 'max:200'],
            'details'     => ['nullable', 'string', 'max:2000'],
            'priority'    => ['required', Rule::in(array_keys(Tasks::PRIORITIES))],
            'due_date'    => ['required', 'date_format:Y-m-d'],
            'due_time'    => ['nullable', 'date_format:H:i'],
            'sop'         => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
        ], [
            'assigned_to.in'    => 'لا يمكنك تكليف هذا الشخص.',
            'title.required'    => 'اكتب المهمة.',
            'due_date.required' => 'حدد موعد المهمة.',
            'sop.regex'         => 'كود الإجراء حروف إنجليزية وأرقام فقط (مثل SH-003).',
        ]);
        $v['title'] = trim($v['title']);
        $v['due_time'] = $v['due_time'] ?? null;
        $v['sop'] = isset($v['sop']) ? strtoupper(trim($v['sop'])) : null;

        return $v;
    }
}