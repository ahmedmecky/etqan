<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** مهمة يومية ثابتة (تظهر في «مهام اليوم») */
class DailyTask extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'title', 'sop', 'roles', 'due_time', 'days', 'sort', 'is_active', 'updated_by'];

    protected function casts(): array
    {
        return ['roles' => 'array', 'is_active' => 'boolean', 'sort' => 'integer'];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DailyTaskLog::class, 'task_id');
    }

    /** موعد الإنجاز HH:MM */
    public function due(): string
    {
        return substr((string) $this->due_time, 0, 5);
    }

    public function runsOn(int $dayOfWeek): bool
    {
        return str_contains((string) $this->days, (string) $dayOfWeek);
    }
}