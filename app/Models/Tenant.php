<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'logo_path',
        'phone',
        'address',
        'city',
        'coverage_zones',
        'gln',
        'complaint_token',
        'complaint_portal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'complaint_portal' => 'boolean',
            'coverage_zones'   => 'json:unicode',   // يُحفظ بالعربي كما هو (للبحث)
        ];
    }

    /** مستخدمو العميل */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** سجلات النماذج الخاصة بالعميل */
    public function records(): HasMany
    {
        return $this->hasMany(FormRecord::class);
    }

    /** رابط الشعار للعرض في المتصفح */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /** مناطق التغطية كنص واحد */
    public function zonesText(string $sep = '، '): string
    {
        return implode($sep, $this->coverage_zones ?? []);
    }

    /** رابط شكاوى الصيدليات (يُنشأ الرمز عند أول طلب) */
    public function complaintUrl(): string
    {
        if (! $this->complaint_token) {
            $this->regenerateComplaintToken();
        }

        return route('portal.complaint', $this->complaint_token);
    }

    /** رمز جديد للرابط · الرابط القديم يتوقف فوراً */
    public function regenerateComplaintToken(): void
    {
        do {
            $token = Str::random(24);
        } while (static::where('complaint_token', $token)->exists());

        $this->forceFill(['complaint_token' => $token])->save();
    }
}