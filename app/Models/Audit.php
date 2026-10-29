<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Audits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** مراجعة خارجية (استشاري · هيئة الدواء · عميل) */
class Audit extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'number', 'type', 'title', 'company', 'lead', 'team', 'auditor_user_id', 'scope', 'criteria', 'start_date', 'end_date',
        'status', 'rating', 'conclusion', 'file_path', 'file_name', 'reported_at', 'closed_at', 'closed_by', 'created_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'reported_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function areas(): HasMany
    {
        return $this->hasMany(AuditArea::class)->orderBy('sort')->orderBy('id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class)->orderBy('no');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return Audits::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return Audits::STATUSES[$this->status] ?? $this->status;
    }

    /** المراجع ما زال يكتب (قبل إرسال التقرير) */
    public function inField(): bool
    {
        return in_array($this->status, ['plan', 'fieldwork'], true);
    }
}