<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** التفتيش الميداني بالتفويض (SH-012-F04): قواعد المقارنة بين الفاتورة والفعلي */
class FieldCheck
{
    public const CODE = 'SH-012-F04';
    public const PREFIX = 'FI';

    /** مقارنة صنف: الفاتورة مقابل الفعلي */
    public static function issues(array $it, ?string $today = null): array
    {
        $today = $today ?? Carbon::today('Africa/Cairo')->format('Y-m');
        $norm = fn ($v) => mb_strtoupper(preg_replace('/\s+/', '', (string) $v));
        $out = [];
        if (($it['batchInv'] ?? '') !== '' && ($it['batchReal'] ?? '') !== '' && $norm($it['batchInv']) !== $norm($it['batchReal'])) {
            $out[] = 'التشغيلة مختلفة';
        }
        if (($it['expInv'] ?? '') !== '' && ($it['expReal'] ?? '') !== '' && $it['expInv'] !== $it['expReal']) {
            $out[] = 'الصلاحية مختلفة';
        }
        if (($it['expReal'] ?? '') !== '' && $it['expReal'] < $today) {
            $out[] = 'منتهي الصلاحية';
        }

        return $out;
    }

    public static function canManage(\App\Models\User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('pharmacist_manager') || $u->hasRole('quality_manager');
    }
}