<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const LEVEL_SUPER_ADMIN  = 1;
    public const LEVEL_CLIENT_ADMIN = 2;
    public const LEVEL_USER         = 3;

    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'level',
        'sort',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** الأدوار التي يمكن لمدير العميل منحها لمستخدميه */
    public function scopeAssignableByClient($query)
    {
        return $query->where('level', self::LEVEL_USER)->orderBy('sort');
    }
}