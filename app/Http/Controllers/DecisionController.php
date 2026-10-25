<?php

namespace App\Http\Controllers;

use App\Models\DecisionStatus;
use App\Models\FormRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * سجل متابعة القرارات (SH-034-F03)
 * يجمع القرارات من كل محاضر الاجتماعات المعتمدة للمخزن
 */
class DecisionController extends Controller
{
    private const MINUTES_CODE  = 'SH-034-F02';
    private const ESCALATE_DAYS = 15;   // القرار المتأخر أكثر من ١٥ يوماً يُصعد (SH-034)

    public function index(Request $request): View
    {
        $all = $this->rows();

        $status = $request->query('status', 'open');
        $q      = trim((string) $request->query('q', ''));
        $type   = (string) $request->query('type', '');

        $rows = $all->filter(function ($r) use ($status, $q, $type) {
            if ($status === 'open' && $r['status'] !== DecisionStatus::OPEN) return false;
            if ($status === 'overdue' && ! $r['overdue']) return false;
            if ($status === 'done' && $r['status'] !== DecisionStatus::DONE) return false;
            if ($status === 'cancelled' && $r['status'] !== DecisionStatus::CANCELLED) return false;
            if ($type !== '' && $r['type'] !== $type) return false;
            if ($q !== '' && mb_stripos($r['text'] . ' ' . $r['owner'] . ' ' . $r['number'], $q) === false) return false;
            return true;
        })->values();

        $done       = $all->where('status', DecisionStatus::DONE);
        $doneOnTime = $done->filter(fn ($r) => $r['due'] && $r['done_at'] && $r['done_at'] <= $r['due'])->count();

        $stats = [
            'total'     => $all->where('status', '!=', DecisionStatus::CANCELLED)->count(),
            'open'      => $all->where('status', DecisionStatus::OPEN)->count(),
            'overdue'   => $all->where('overdue', true)->count(),
            'escalate'  => $all->where('escalate', true)->count(),
            'done'      => $done->count(),
            'on_time'   => $done->count() ? round($doneOnTime / $done->count() * 100) : null,
        ];

        return view('decisions.index', [
            'rows'    => $rows,
            'stats'   => $stats,
            'types'   => $all->pluck('type')->filter()->unique()->sort()->values(),
            'status'  => $status,
            'q'       => $q,
            'type'    => $type,
            'canEdit' => $this->canEdit($request->user()),
            'labels'  => DecisionStatus::LABELS,
            'escalateDays' => self::ESCALATE_DAYS,
        ]);
    }

    public function update(Request $request, int $record, string $key): RedirectResponse
    {
        abort_unless($this->canEdit($request->user()), 403, 'تحديث حالة القرارات متاح لمدير المخزن ومدير إدارة الجودة فقط.');

        $r = FormRecord::query()->form(self::MINUTES_CODE)->where('status', FormRecord::STATUS_FINAL)->findOrFail($record);

        $decisions = collect($r->data['decisions'] ?? []);
        $index     = $decisions->search(fn ($d) => ($d['id'] ?? null) === $key);
        abort_if($index === false, 404);

        $data = $request->validate([
            'status'  => ['required', Rule::in(array_keys(DecisionStatus::LABELS))],
            'done_at' => ['nullable', 'date', 'required_if:status,' . DecisionStatus::DONE],
            'note'    => ['nullable', 'string', 'max:500'],
        ], [
            'status.in'           => 'حالة غير صحيحة.',
            'done_at.required_if' => 'اكتب تاريخ التنفيذ للقرار المنفذ.',
            'done_at.date'        => 'تاريخ التنفيذ غير صحيح.',
        ]);

        DecisionStatus::updateOrCreate(
            ['form_record_id' => $r->id, 'decision_key' => $key],
            [
                'tenant_id'  => $r->tenant_id,
                'status'     => $data['status'],
                'done_at'    => $data['status'] === DecisionStatus::DONE ? $data['done_at'] : null,
                'note'       => $data['note'] ?? null,
                'updated_by' => $request->user()->id,
            ]
        );

        $code = $r->number . '-D' . ($index + 1);
        $r->log('متابعة قرار', "القرار {$code}: " . DecisionStatus::LABELS[$data['status']]
            . (! empty($data['note']) ? ' · ' . $data['note'] : ''));

        return back()->with('status', "تم تحديث حالة القرار {$code}.");
    }

    /* ======================================================= */

    /** كل القرارات من المحاضر المعتمدة مع حالتها */
    private function rows(): Collection
    {
        $records = FormRecord::query()
            ->form(self::MINUTES_CODE)
            ->where('status', FormRecord::STATUS_FINAL)
            ->orderBy('record_date')
            ->get();

        $statuses = DecisionStatus::query()
            ->whereIn('form_record_id', $records->pluck('id'))
            ->with('editor')
            ->get()
            ->keyBy(fn ($s) => $s->form_record_id . '|' . $s->decision_key);

        $today = Carbon::today('Africa/Cairo')->toDateString();
        $rows  = collect();

        foreach ($records as $r) {
            $data      = $r->data ?? [];
            $attendees = collect($data['attendees'] ?? [])->keyBy('id');
            $agenda    = collect($data['agenda'] ?? [])->values();

            foreach (array_values($data['decisions'] ?? []) as $i => $d) {
                $text = trim((string) ($d['text'] ?? ''));
                if ($text === '') continue;

                $key   = (string) ($d['id'] ?? $i);
                $st    = $statuses->get($r->id . '|' . $key);
                $state = $st?->status ?? DecisionStatus::OPEN;
                $due   = $d['due'] ?? null;
                $late  = ($state === DecisionStatus::OPEN && $due && $due < $today)
                    ? Carbon::parse($due)->diffInDays(Carbon::parse($today))
                    : 0;

                $itemIndex = $agenda->search(fn ($g) => ($g['id'] ?? null) === ($d['item'] ?? null));

                $rows->push([
                    'record_id' => $r->id,
                    'key'       => $key,
                    'code'      => $r->number . '-D' . ($i + 1),
                    'number'    => $r->number,
                    'date'      => $r->record_date?->format('Y-m-d'),
                    'type'      => $data['type'] ?? $r->title,
                    'text'      => $text,
                    'owner'     => $this->person($attendees->get($d['owner'] ?? '')),
                    'due'       => $due,
                    'item'      => $itemIndex === false ? null : $itemIndex + 1,
                    'status'    => $state,
                    'done_at'   => $st?->done_at?->format('Y-m-d'),
                    'note'      => $st?->note,
                    'by'        => $st?->editor?->displayName(),
                    'overdue'   => $late > 0,
                    'late_days' => (int) $late,
                    'escalate'  => $late > self::ESCALATE_DAYS,
                ]);
            }
        }

        // المتأخر أولاً، ثم المفتوح بالأقرب موعداً، ثم المنفذ والملغى
        $rank = fn ($r) => $r['overdue'] ? 0 : ($r['status'] === DecisionStatus::OPEN ? 1 : 2);

        return $rows->sort(function ($a, $b) use ($rank) {
            return [$rank($a), $a['due'] ?? '9999', $a['code']] <=> [$rank($b), $b['due'] ?? '9999', $b['code']];
        })->values();
    }

    /** نفس صيغة العرض في شاشة المحضر: الوظيفة / الاسم */
    private function person(?array $a): string
    {
        if (! $a) return '—';
        $role = ($a['role'] ?? '') === 'أخرى' ? ($a['other'] ?? 'أخرى') : ($a['role'] ?? '');
        $name = trim((string) ($a['name'] ?? ''));

        return $name !== '' ? "{$role} / {$name}" : $role;
    }

    private function canEdit(User $user): bool
    {
        return $user->isClientAdmin() || $user->hasRole('quality_manager');
    }
}