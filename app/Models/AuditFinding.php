<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Audits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ملاحظة مراجعة: الدرجة · الملاحظة · الدليل · المرجع · الرد والإجراء */
class AuditFinding extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'audit_id', 'area_id', 'no', 'grade', 'text', 'evidence', 'ref', 'cause', 'action', 'owner_id', 'due_date',
        'dev_no', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(AuditArea::class, 'area_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function gradeLabel(): string
    {
        return Audits::GRADES[$this->grade][0] ?? $this->grade;
    }

    /** تحتاج إجراء تصحيحي (ليست نقطة قوة) */
    public function needsAction(): bool
    {
        return Audits::GRADES[$this->grade][2] ?? false;
    }
}