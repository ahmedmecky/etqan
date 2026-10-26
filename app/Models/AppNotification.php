<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** إشعار في صندوق المستخدم (الجرس) */
class AppNotification extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'event', 'title', 'body', 'url', 'tag', 'urgent', 'read_at'];

    protected function casts(): array
    {
        return ['urgent' => 'boolean', 'read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMine($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}