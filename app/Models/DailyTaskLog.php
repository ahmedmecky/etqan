<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** تنفيذ مهمة يومية في يوم معين */
class DailyTaskLog extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'task_id', 'date', 'done_by', 'done_at', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date', 'done_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(DailyTask::class, 'task_id');
    }

    public function doer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}