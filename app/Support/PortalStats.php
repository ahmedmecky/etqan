<?php

namespace App\Support;

use App\Models\FormRecord;
use App\Models\User;

/**
 * عدّاد شكاوى رابط الصيدليات الجديدة (بانتظار التصنيف) للصفحة الرئيسية
 */
class PortalStats
{
    /** يظهر للمدير الصيدلي ومدير الجودة ومدير المخزن */
    public static function canSee(User $user): bool
    {
        return $user->isClientAdmin() || $user->hasRole('pharmacist_manager') || $user->hasRole('quality_manager');
    }

    public static function newComplaints(User $user): int
    {
        if (! $user->tenant_id || ! self::canSee($user)) {
            return 0;
        }

        return FormRecord::query()
            ->form(FormRegistry::get('complaint')['code'])
            ->where('status', FormRecord::STATUS_DRAFT)
            ->where('data->source', 'portal')
            ->where('data->stage', 'reported')
            ->count();
    }
}