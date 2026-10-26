<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** مستلم حدث (شكوى · حرارة · تفتيش ميداني · ملخص المتأخر) يحدده مدير المخزن */
class NotifyRule extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'event', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}