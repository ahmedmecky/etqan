<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** جهاز مفعّل عليه الإشعار لمستخدم (اشتراك Web Push) */
class PushSubscription extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'device', 'last_ok_at', 'fails'];

    protected $hidden = ['endpoint', 'p256dh', 'auth'];

    protected function casts(): array
    {
        return ['last_ok_at' => 'datetime', 'fails' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashOf(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }
}