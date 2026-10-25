<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** مفوض بالتفتيش الميداني: له رابط شخصي يمكن إيقافه أو تجديده */
class InspectionDelegate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'job', 'token', 'is_active', 'created_by', 'last_used_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_used_at' => 'datetime'];
    }

    public static function newToken(): string
    {
        do {
            $t = Str::random(32);
        } while (static::withoutGlobalScope('tenant')->where('token', $t)->exists());

        return $t;
    }

    public function url(): string
    {
        return route('field.form', $this->token);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}