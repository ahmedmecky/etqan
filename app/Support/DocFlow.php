<?php

namespace App\Support;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * مسار مراجعة واعتماد الوثائق (المتفق عليه مع أحمد)
 *   المُعد يرفع الملف ويرسل ← مدير إدارة الجودة يراجع (يعدل أو يعتمد أو يعيد)
 *   ← الموارد البشرية (للأوصاف الوظيفية ومؤشرات الأداء فقط، إن وجد مدير موارد بشرية)
 *   ← الرئيس التنفيذي / العضو المنتدب: الاعتماد النهائي والسريان
 *   الإعادة من أي مرحلة ترجع للمُعد.
 * إذا لم يوجد في المخزن مستخدم فعال بدور المرحلة، يقوم بها مدير المخزن (مدير العميل).
 */
class DocFlow
{
    public const ROLES = [
        'qa'  => ['quality_manager'],
        'hr'  => ['hr_manager'],
        'ceo' => ['ceo', 'general_manager', 'managing_director'],
    ];

    public const STEP_LABEL = [
        'qa'  => 'مدير إدارة الجودة',
        'hr'  => 'مدير الموارد البشرية',
        'ceo' => 'الرئيس التنفيذي / العضو المنتدب',
    ];

    public const HR_TYPES = ['jd', 'kpi'];
    public const REVIEW_YEARS = 2;

    private static array $cache = [];

    /** مستخدمو المخزن الفعالون */
    private static function users(int $tenantId): Collection
    {
        return self::$cache[$tenantId] ??= User::query()->with('role')
            ->where('tenant_id', $tenantId)->where('is_active', true)->get();
    }

    /** هل يوجد مستخدم فعال بأحد أدوار المرحلة؟ */
    public static function hasStepUser(int $tenantId, string $step): bool
    {
        return self::users($tenantId)->contains(fn ($u) => collect(self::ROLES[$step])->contains(fn ($r) => $u->hasRole($r)));
    }

    /** مراحل الاعتماد لهذا النوع في هذا المخزن */
    public static function steps(string $type, int $tenantId): array
    {
        $steps = ['qa'];
        if (in_array($type, self::HR_TYPES, true) && self::hasStepUser($tenantId, 'hr')) {
            $steps[] = 'hr';
        }
        $steps[] = 'ceo';

        return $steps;
    }

    /** المرحلة التالية بعد مرحلة معينة (null = يصبح سارياً) */
    public static function next(string $stage, string $type, int $tenantId): ?string
    {
        $steps = self::steps($type, $tenantId);
        $i = array_search($stage, $steps, true);

        return $i === false ? null : ($steps[$i + 1] ?? null);
    }

    /** هل يحق للمستخدم العمل على هذه المرحلة؟ */
    public static function canAct(User $user, string $stage): bool
    {
        if (! isset(self::ROLES[$stage]) || ! $user->tenant_id) {
            return false;
        }
        foreach (self::ROLES[$stage] as $r) {
            if ($user->hasRole($r)) {
                return true;
            }
        }

        // لا يوجد من يقوم بالمرحلة: مدير المخزن بديلاً
        return $user->isClientAdmin() && ! self::hasStepUser($user->tenant_id, $stage);
    }

    /** المراحل التي يعمل عليها المستخدم */
    public static function myStages(User $user): array
    {
        return array_values(array_filter(array_keys(self::ROLES), fn ($s) => self::canAct($user, $s)));
    }

    /** هل يدير المستخدم سجل الوثائق (إضافة وتعديل البيانات)؟ */
    public static function canManage(User $user): bool
    {
        return $user->isClientAdmin() || $user->hasRole('quality_manager');
    }

    /** هل يحق للمستخدم رفع ملف وإرسال الإصدار (المُعد)؟ */
    public static function canPrepare(User $user, ?DocumentVersion $v): bool
    {
        if (! $v) {
            return true;
        }
        if (! in_array($v->stage, ['draft', 'returned'], true)) {
            return false;
        }

        return ! $v->prepared_by || (int) $v->prepared_by === (int) $user->id || self::canManage($user);
    }

    /** عدد ما ينتظر المستخدم */
    public static function waiting(User $user): int
    {
        if (! $user->tenant_id) {
            return 0;
        }
        $stages = self::myStages($user);
        $n = $stages ? DocumentVersion::query()->whereIn('stage', $stages)->count() : 0;

        return $n + DocumentVersion::query()->where('stage', 'returned')->where('prepared_by', $user->id)->count();
    }

    public static function nextVersion(Document $doc): string
    {
        $max = $doc->versions()->pluck('version')->map(fn ($v) => (int) $v)->max() ?? 0;

        return str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }
}