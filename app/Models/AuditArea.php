<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** إدارة / قسم داخل المراجعة (شاشة لكل إدارة) */
class AuditArea extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'audit_id', 'name', 'sops', 'auditee', 'audited_on', 'notes', 'done', 'sort'];

    protected function casts(): array
    {
        return ['audited_on' => 'date', 'done' => 'boolean'];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class, 'area_id')->orderBy('no');
    }
}