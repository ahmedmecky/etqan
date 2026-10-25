<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionStatus extends Model
{
    use BelongsToTenant;

    public const OPEN      = 'open';
    public const DONE      = 'done';
    public const CANCELLED = 'cancelled';

    public const LABELS = [
        self::OPEN      => 'مفتوح',
        self::DONE      => 'منفذ',
        self::CANCELLED => 'ملغى',
    ];

    protected $fillable = [
        'tenant_id',
        'form_record_id',
        'decision_key',
        'status',
        'done_at',
        'note',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'done_at' => 'date',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(FormRecord::class, 'form_record_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}