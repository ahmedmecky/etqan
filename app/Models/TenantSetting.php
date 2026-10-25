<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قيمة إعداد واحد لمخزن واحد
 * القراءة والحفظ تتم من خلال App\Support\Settings
 */
class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'updated_by',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}