<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** صفحة فتحها المراجع الخارجي */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'method', 'path', 'route', 'ip', 'at'];

    protected function casts(): array
    {
        return ['at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}