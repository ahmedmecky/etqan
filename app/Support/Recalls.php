<?php

namespace App\Support;

use App\Models\Recall;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * الاستدعاء والسحب من السوق (SH-010)
 *   F01 سجل منشورات السحب · F02 متابعة الاتصال بالعملاء والاسترجاع · F03 تسوية وإغلاق السحب
 *   F05 إقرار الصيدلية (مطبوع باسمها وبيانات الصنف): لا يوجد لدي الصنف · أو يوجد وتم تسليم الكمية ← توقيع وختم ← صورة في النظام
 */
class Recalls
{
    public const TZ = 'Africa/Cairo';

    public const SOURCES = ['eda' => 'هيئة الدواء المصرية', 'supplier' => 'المورد', 'manufacturer' => 'المصنع', 'internal' => 'قرار داخلي'];

    public const CLASSES = ['1' => 'الدرجة الأولى (خطر جسيم)', '2' => 'الدرجة الثانية', '3' => 'الدرجة الثالثة'];

    public const METHODS = ['phone' => 'تليفون', 'whatsapp' => 'واتساب', 'visit' => 'زيارة مندوب', 'letter' => 'خطاب'];

    public const DECLARATIONS = ['pending' => 'لم يصل الإقرار', 'none' => 'لا يوجد لديه', 'has' => 'يوجد · تم الاسترجاع'];

    public const DISPOSALS = ['return' => 'إرجاع للمورد', 'destroy' => 'إعدام بمحضر'];

    /** إنشاء السحب والتسوية والإغلاق */
    public static function canManage(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('pharmacist_manager', 'quality_manager');
    }

    /** تسجيل الإبلاغ والإقرارات والاسترجاع للعملاء */
    public static function canWork(User $u): bool
    {
        if ($u->hasRole('external_auditor')) {
            return Access::auditorCan($u, 'recall');
        }
        return self::canManage($u) || $u->hasRole('retail_sales_supervisor', 'wholesale_sales_manager', 'telesales', 'sales_rep',
            'distribution_supervisor', 'purchasing_officer', 'commercial_director', 'wholesale_storekeeper', 'retail_storekeeper', 'general_manager', 'ceo', 'managing_director');
    }

    public static function nextNumber(int $tenantId): string
    {
        $y = Carbon::now(self::TZ)->format('Y');
        $last = Recall::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('number', 'like', "RC-{$y}-%")->orderByDesc('id')->value('number');
        $n = $last ? ((int) substr($last, -3)) + 1 : 1;

        return sprintf('RC-%s-%03d', $y, $n);
    }

    /**
     * أرقام السحب (F03): أ المستلم · ب المبيع · ج بالمخزن · د المسترجع · هـ غير المسترد · نسبة النجاح · نسبة الإبلاغ في المدة
     */
    public static function stats(Recall $r, ?Collection $customers = null): array
    {
        $c = $customers ?? $r->customers;
        $sold = (int) $c->sum('qty_sold');
        $returned = (int) $c->sum('qty_returned');
        $notified = $c->whereNotNull('notified_at')->count();
        $inTime = $r->deadline
            ? $c->filter(fn ($x) => $x->notified_at && $x->notified_at->timezone(self::TZ)->toDateString() <= $r->deadline->format('Y-m-d'))->count()
            : $notified;
        $a = $r->qty_received;
        $unrec = $a !== null ? max(0, $a - (int) $r->qty_in_stock - $returned) : null;

        return [
            'customers' => $c->count(), 'notified' => $notified, 'inTime' => $inTime,
            'declared'  => $c->where('declaration', '!=', 'pending')->count(),
            'has'       => $c->where('declaration', 'has')->count(), 'none' => $c->where('declaration', 'none')->count(),
            'photos'    => $c->whereNotNull('file_path')->count(),
            'sold' => $sold, 'returned' => $returned, 'received' => $a, 'inStock' => $r->qty_in_stock, 'unrecovered' => $unrec,
            'success'   => $sold ? round($returned / $sold * 100, 1) : null,
            'notifiedPct' => $c->count() ? round($inTime / $c->count() * 100) : null,
            'late'      => $r->isOpen() && $r->deadline && $r->deadline->format('Y-m-d') < Carbon::today(self::TZ)->toDateString() && $notified < $c->count(),
        ];
    }

    public static function homeAlerts(User $u): array
    {
        if (! self::canWork($u)) {
            return [];
        }
        $open = Recall::query()->where('status', 'open')->with('customers')->get();
        if ($open->isEmpty()) {
            return [];
        }
        $pending = $open->sum(fn ($r) => $r->customers->where('declaration', 'pending')->count());
        $late = $open->filter(fn ($r) => self::stats($r)['late'])->count();

        return [[
            'n' => $open->count(), 'tone' => $late ? 'bad' : 'warn', 'icon' => 'alert', 'title' => 'سحب أدوية مفتوح',
            'sub' => $open->count() . ' سحب · ' . $pending . ' إقرار لم يصل' . ($late ? ' · ' . $late . ' تجاوز موعد الإبلاغ' : ''), 'url' => route('recalls.index'),
        ]];
    }

    /** للإشعار اليومي: سحوبات تجاوزت موعد الإبلاغ وبها عملاء لم يُبلغوا */
    public static function overdue(int $tenantId): Collection
    {
        return Recall::withoutGlobalScope('tenant')->with(['customers' => fn ($q) => $q->withoutGlobalScope('tenant')])
            ->where('tenant_id', $tenantId)->where('status', 'open')->whereNotNull('deadline')
            ->whereDate('deadline', '<', Carbon::today(self::TZ)->toDateString())->get()
            ->filter(fn ($r) => $r->customers->whereNull('notified_at')->isNotEmpty())->values();
    }
}