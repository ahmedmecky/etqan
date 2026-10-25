<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** قراءة حرارة (الصباح أو المساء) لنقطة مراقبة */
class TempReading extends Model
{
    use BelongsToTenant;

    public const SHIFTS = ['am' => 'الصباح', 'pm' => 'المساء'];

    protected $fillable = ['tenant_id', 'point_id', 'date', 'shift', 'time', 'cur', 't_high', 't_low', 'hum', 'status', 'note', 'user_id', 'updated_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'cur' => 'float', 't_high' => 'float', 't_low' => 'float', 'hum' => 'integer'];
    }

    public function point(): BelongsTo
    {
        return $this->belongsTo(TempPoint::class, 'point_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}