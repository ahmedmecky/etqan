<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Recalls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** عملية سحب / استدعاء لتشغيلة (SH-010) */
class Recall extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'number', 'circular_no', 'source', 'received_on', 'product', 'batch', 'expiry', 'class', 'reason', 'deadline',
        'qty_received', 'qty_in_stock', 'status', 'disposal', 'disposal_ref', 'eda_notified_on', 'supplier_notified_on', 'dev_no', 'notes',
        'closed_at', 'closed_by', 'created_by'];

    protected function casts(): array
    {
        return [
            'received_on' => 'date', 'expiry' => 'date', 'deadline' => 'date', 'eda_notified_on' => 'date', 'supplier_notified_on' => 'date',
            'closed_at' => 'datetime', 'qty_received' => 'integer', 'qty_in_stock' => 'integer',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(RecallCustomer::class)->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function sourceLabel(): string
    {
        return Recalls::SOURCES[$this->source] ?? $this->source;
    }

    public function classLabel(): string
    {
        return Recalls::CLASSES[$this->class] ?? $this->class;
    }
}