<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** مراجعة أسبوعية لبيانات أجهزة التسجيل (review) · اختبار إنذار شهري لثلاجة (alarm) */
class TempCheck extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'point_id', 'kind', 'date', 'result', 'note', 'user_id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function point(): BelongsTo
    {
        return $this->belongsTo(TempPoint::class, 'point_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}