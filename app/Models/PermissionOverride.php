<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** تعديل صلاحية شاشة لدور أو لمستخدم (الافتراضي من RACI) */
class PermissionOverride extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'role_id', 'user_id', 'module', 'level', 'updated_by'];
}