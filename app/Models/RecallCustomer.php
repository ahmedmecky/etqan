<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Recalls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** عميل اشترى التشغيلة المسحوبة: الإبلاغ والإقرار والاسترجاع */
class RecallCustomer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'recall_id', 'code', 'name', 'address', 'phone', 'qty_sold', 'notified_at', 'method', 'declaration',
        'qty_returned', 'returned_on', 'pharmacist', 'file_path', 'file_name', 'note', 'updated_by'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime', 'returned_on' => 'date', 'qty_sold' => 'integer', 'qty_returned' => 'integer'];
    }

    public function recall(): BelongsTo
    {
        return $this->belongsTo(Recall::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function declarationLabel(): string
    {
        return Recalls::DECLARATIONS[$this->declaration] ?? $this->declaration;
    }

    public function methodLabel(): string
    {
        return Recalls::METHODS[$this->method] ?? '';
    }
}