<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tasks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** مهمة مكلف بها (أو مهمة شخصية) لمرة واحدة */
class Task extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'title', 'details', 'priority', 'due_date', 'due_time', 'sop', 'assigned_to', 'created_by',
        'status', 'done_at', 'done_by', 'done_note', 'remind_stage'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'done_at' => 'datetime'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function doer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /** مهمة وضعها الشخص لنفسه */
    public function isSelf(): bool
    {
        return (int) $this->created_by === (int) $this->assigned_to;
    }

    public function time(): ?string
    {
        return $this->due_time ? substr((string) $this->due_time, 0, 5) : null;
    }

    /** لحظة الموعد (نهاية اليوم إن لم يُحدد وقت) بتوقيت القاهرة */
    public function dueAt(): Carbon
    {
        return Carbon::parse($this->due_date->format('Y-m-d') . ' ' . ($this->time() ?? '23:59'), Tasks::TZ);
    }

    /** done · cancelled · late · today · soon */
    public function state(): string
    {
        if ($this->status !== 'open') {
            return $this->status;
        }
        $now = Carbon::now(Tasks::TZ);
        if ($this->dueAt()->lt($now)) {
            return 'late';
        }

        return $this->due_date->format('Y-m-d') === $now->toDateString() ? 'today' : 'soon';
    }

    public function priorityLabel(): string
    {
        return Tasks::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /** نص الموعد: اليوم 14:00 · غداً · 2026-10-30 · متأخرة منذ ٣ أيام */
    public function dueText(): string
    {
        $today = Carbon::today(Tasks::TZ);
        $d = (int) round($today->diffInDays(Carbon::parse($this->due_date->format('Y-m-d'), Tasks::TZ), false));
        $t = $this->time() ? ' ' . $this->time() : '';
        if ($this->status === 'open' && $d < 0) {
            return 'متأخرة منذ ' . (-$d) . ' يوم';
        }

        return ($d === 0 ? 'اليوم' : ($d === 1 ? 'غداً' : $this->due_date->format('Y-m-d'))) . $t;
    }
}