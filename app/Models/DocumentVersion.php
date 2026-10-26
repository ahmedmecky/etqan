<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** إصدار وثيقة بملفه ومرحلة اعتماده */
class DocumentVersion extends Model
{
    use BelongsToTenant;

    public const STAGES = [
        'draft'      => 'مسودة',
        'qa'         => 'مراجعة الجودة',
        'hr'         => 'اعتماد الموارد البشرية',
        'ceo'        => 'اعتماد الرئيس التنفيذي',
        'returned'   => 'أُعيدت للمُعد',
        'effective'  => 'سارية',
        'superseded' => 'ملغاة بإصدار أحدث',
    ];

    protected $fillable = [
        'tenant_id', 'document_id', 'version', 'stage', 'source', 'lib_version', 'fills', 'file_path', 'file_name', 'file_size',
        'change_summary', 'return_note', 'prepared_by', 'submitted_at', 'effective_date', 'review_due', 'approvals',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at'   => 'datetime',
            'effective_date' => 'date',
            'review_due'     => 'date',
            'approvals'      => 'array',
            'fills'          => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /** نص مدمج من مكتبة إتقان (بدون ملف) */
    public function isLibrary(): bool
    {
        return $this->source === 'library';
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }
}