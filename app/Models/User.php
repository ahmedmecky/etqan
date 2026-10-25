<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'role_id',
        'name',
        'name_ar',
        'email',
        'phone',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /** العميل (المخزن) الذي يتبعه المستخدم · null لمدير النظام */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->level === Role::LEVEL_SUPER_ADMIN;
    }

    public function isClientAdmin(): bool
    {
        return $this->role?->level === Role::LEVEL_CLIENT_ADMIN;
    }

    public function hasRole(string ...$keys): bool
    {
        return $this->role && in_array($this->role->key, $keys, true);
    }

    /** الاسم المعروض: العربي إن وجد */
    public function displayName(): string
    {
        return $this->name_ar ?: $this->name;
    }
}