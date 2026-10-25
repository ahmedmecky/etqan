<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * أي جدول يستخدم هذا الـ Trait:
 * - تُعرض فقط سجلات مخزن المستخدم المسجل
 * - يُحفظ tenant_id تلقائياً عند الإنشاء
 * مدير النظام (بدون مخزن) لا يُطبق عليه الفلتر.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->tenant_id) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
            }
        });

        static::creating(function ($model) {
            $user = auth()->user();
            if (empty($model->tenant_id) && $user && $user->tenant_id) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}