<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Maintenance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** عقد صيانة مع شركة خارجية */
class MaintContract extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'kind', 'company', 'contract_no', 'contact', 'phone', 'start_date', 'end_date', 'alert_days', 'visits', 'scope',
        'file_path', 'file_name', 'renewed_from_id', 'is_active', 'alert_stage', 'updated_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean', 'alert_days' => 'integer'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(MaintAsset::class, 'contract_id');
    }

    public function previous(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function kindLabel(): string
    {
        return Maintenance::KINDS[$this->kind] ?? $this->kind;
    }

    /** الأيام المتبقية حتى الانتهاء (سالب = منتهي) */
    public function daysLeft(): int
    {
        return Maintenance::daysLeft($this->end_date);
    }

    /** ok · soon · week · expired · off */
    public function state(): string
    {
        return $this->is_active ? Maintenance::stateFor($this->end_date, (int) $this->alert_days) : 'off';
    }
}