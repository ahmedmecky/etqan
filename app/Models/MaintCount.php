<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** جرد فعلي للأجهزة */
class MaintCount extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'date', 'results', 'found', 'missing', 'damaged', 'notes', 'counted_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'results' => 'array'];
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}