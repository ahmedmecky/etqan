<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Maintenance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** جهاز مسجل بالرقم التسلسلي */
class MaintAsset extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'category', 'name', 'serial', 'tag_no', 'brand', 'model', 'location', 'contract_id', 'due_date', 'last_service',
        'status', 'notes', 'last_counted', 'count_result', 'alert_stage', 'updated_by'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'last_service' => 'date', 'last_counted' => 'date'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(MaintContract::class, 'contract_id');
    }

    public function categoryLabel(): string
    {
        return Maintenance::CATEGORIES[$this->category]['label'] ?? $this->category;
    }

    /** اسم تاريخ الجهاز حسب نوعه (انتهاء الطفاية · المعايرة القادمة …) */
    public function dueLabel(): string
    {
        return Maintenance::CATEGORIES[$this->category]['due'] ?? 'الصيانة القادمة';
    }

    /** ok · soon · week · expired · none (بدون تاريخ) · off (خارج الخدمة) */
    public function state(): string
    {
        if (in_array($this->status, ['out', 'disposed'], true)) {
            return 'off';
        }

        return $this->due_date ? Maintenance::stateFor($this->due_date, Maintenance::ASSET_ALERT_DAYS) : 'none';
    }

    public function statusLabel(): string
    {
        return Maintenance::STATUSES[$this->status] ?? $this->status;
    }
}