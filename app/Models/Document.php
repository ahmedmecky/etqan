<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** وثيقة في سجل التحكم في الوثائق */
class Document extends Model
{
    use BelongsToTenant;

    public const TYPES = [
        'sop'    => 'إجراء تشغيل قياسي',
        'form'   => 'نموذج',
        'jd'     => 'وصف وظيفي',
        'kpi'    => 'مؤشرات أداء',
        'policy' => 'سياسة',
        'other'  => 'أخرى',
    ];

    protected $fillable = ['tenant_id', 'code', 'type', 'title', 'dept', 'current_version_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('id');
    }

    public function current(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    /** الإصدار الجاري العمل عليه (غير ساري وغير ملغى) */
    public function working(): ?DocumentVersion
    {
        return $this->versions->first(fn ($v) => ! in_array($v->stage, ['effective', 'superseded'], true));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentLog::class)->orderByDesc('id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}