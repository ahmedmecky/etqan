<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** نقطة مراقبة حرارة: منطقة غرفة · ثلاجة · غرفة تبريد · منطقة خاصة */
class TempPoint extends Model
{
    use BelongsToTenant;

    public const TYPES = [
        'room'     => 'منطقة تخزين (غرفة)',
        'fridge'   => 'ثلاجة',
        'coldroom' => 'غرفة تبريد',
        'special'  => 'منطقة خاصة',
    ];

    /** الحدود الافتراضية من SH-030 (تعريف «حدود المراقبة») */
    public const DEFAULTS = [
        'room'     => ['t_min' => 15, 't_max' => 25, 'warn_low' => null, 'warn_high' => 24, 'h_max' => 65],
        'fridge'   => ['t_min' => 2,  't_max' => 8,  'warn_low' => 3,    'warn_high' => 7,  'h_max' => null],
        'coldroom' => ['t_min' => 2,  't_max' => 8,  'warn_low' => 3,    'warn_high' => 7,  'h_max' => null],
        'special'  => ['t_min' => 15, 't_max' => 25, 'warn_low' => null, 'warn_high' => 24, 'h_max' => 65],
    ];

    protected $fillable = ['tenant_id', 'name', 'type', 'device_no', 't_min', 't_max', 'warn_low', 'warn_high', 'h_max', 'sort', 'is_active'];

    protected function casts(): array
    {
        return [
            't_min' => 'float', 't_max' => 'float', 'warn_low' => 'float', 'warn_high' => 'float',
            'h_max' => 'integer', 'sort' => 'integer', 'is_active' => 'boolean',
        ];
    }

    public function readings(): HasMany
    {
        return $this->hasMany(TempReading::class, 'point_id');
    }

    public function isCold(): bool
    {
        return in_array($this->type, ['fridge', 'coldroom'], true);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** نص الحدود للعرض: «٢–٨°م» أو «١٥–٢٥°م · رطوبة ≤ ٦٥٪» */
    public function limitsText(): string
    {
        $t = self::n($this->t_min) . '–' . self::n($this->t_max) . '°م';

        return $this->h_max ? $t . ' · رطوبة ≤ ' . $this->h_max . '٪' : $t;
    }

    public static function n(?float $v): string
    {
        return $v === null ? '' : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    /**
     * تقييم قراءة: out = خارج الحدود (انحراف حرارة حتى لو عاد تلقائياً) · warn = تجاوز حد الإنذار المبكر · ok
     * @return array{0:string,1:array<int,string>}
     */
    public function evaluate(?float $cur, ?float $high, ?float $low, ?int $hum): array
    {
        $out = [];
        $warn = [];
        foreach (['الحالية' => $cur, 'الأعلى' => $high, 'الأدنى' => $low] as $label => $v) {
            if ($v === null) {
                continue;
            }
            if ($v > $this->t_max) {
                $out[] = "{$label} " . self::n($v) . '°م أعلى من ' . self::n($this->t_max);
            } elseif ($v < $this->t_min) {
                $out[] = "{$label} " . self::n($v) . '°م أقل من ' . self::n($this->t_min);
            } elseif ($this->warn_high !== null && $v > $this->warn_high) {
                $warn[] = "{$label} " . self::n($v) . '°م فوق حد الإنذار ' . self::n($this->warn_high);
            } elseif ($this->warn_low !== null && $v < $this->warn_low) {
                $warn[] = "{$label} " . self::n($v) . '°م تحت حد الإنذار ' . self::n($this->warn_low);
            }
        }
        if ($this->h_max && $hum !== null && $hum > $this->h_max) {
            $out[] = "الرطوبة {$hum}٪ أعلى من {$this->h_max}٪";
        }

        return $out ? ['out', $out] : ($warn ? ['warn', $warn] : ['ok', []]);
    }

    /** للشاشات (JSON) */
    public function toClient(): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'type' => $this->type, 'typeLabel' => $this->typeLabel(),
            'device' => $this->device_no, 'min' => $this->t_min, 'max' => $this->t_max,
            'wl' => $this->warn_low, 'wh' => $this->warn_high, 'hmax' => $this->h_max, 'limits' => $this->limitsText(),
        ];
    }
}