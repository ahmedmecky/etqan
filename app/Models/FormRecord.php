<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormRecord extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'tenant_id',
        'form_code',
        'number',
        'title',
        'record_date',
        'status',
        'data',
        'created_by',
        'updated_by',
        'finalized_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'data'         => 'array',
            'record_date'  => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function scopeForm($query, string $formCode)
    {
        return $query->where('form_code', $formCode);
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FormRecordLog::class)->latest('id');
    }

    /** تسجيل حركة باسم المستخدم الحالي */
    public function log(string $action, ?string $detail = null): void
    {
        $this->logs()->create([
            'user_id' => auth()->id(),
            'action'  => $action,
            'detail'  => $detail ? mb_substr($detail, 0, 500) : null,
        ]);
    }
}