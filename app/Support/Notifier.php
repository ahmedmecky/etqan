<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\NotifyRule;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * الإشعارات: صندوق الجرس + Web Push للموبايل
 *   الأحداث التي يحدد مستلميها مدير المخزن: شكوى · حرارة · تفتيش ميداني · ملخص المتأخر اليومي
 *   الاعتمادات: تذهب تلقائياً لصاحب الاعتماد (حسب الدور)
 * الإرسال يتم بعد إرسال الصفحة للمستخدم (terminating) فلا يتأخر الحفظ.
 * عدة إشعارات بنفس الوسم (tag) لنفس الشخص في نفس الطلب تُدمج في إشعار واحد.
 */
class Notifier
{
    /** الأحداث التي يختار مستلميها مدير المخزن */
    public const EVENTS = [
        'complaint' => ['label' => 'شكوى عميل جديدة', 'hint' => 'من رابط الصيدليات أو المسجلة داخلياً عند إرسالها للتصنيف', 'module' => 'complaint', 'icon' => 'alert'],
        'temp'      => ['label' => 'انحراف حرارة', 'hint' => 'أي قراءة خارج الحدود في جولة الحرارة', 'module' => 'temperature', 'icon' => 'thermo'],
        'field'     => ['label' => 'تفتيش ميداني جديد', 'hint' => 'عند إرسال المفوض لنتيجة الفحص من رابطه الشخصي', 'module' => 'inspection', 'icon' => 'search'],
        'daily'     => ['label' => 'مهام يومية لم تُنجز', 'hint' => 'قراءة حرارة أو مهمة يومية فات موعدها ولم تُسجل (تُرسل مرة واحدة لكل مهمة في اليوم)', 'module' => null, 'icon' => 'tasks'],
        'overdue'   => ['label' => 'ملخص الصباح (٨ ص)', 'hint' => 'يومياً: المتأخر وما ينتظر الشخص (انحرافات · قرارات · وثائق · تدريب)', 'module' => null, 'icon' => 'calendar'],
    ];

    /** أنواع إشعارات إضافية (بدون إعداد): للعرض في الجرس */
    public const LABELS = [
        'approval' => 'اعتماد مطلوب',
        'test'     => 'إشعار تجريبي',
    ];

    /** المستلمون الافتراضيون إذا لم يحدد مدير المخزن أحداً */
    public const DEFAULT_ROLES = ['quality_manager', 'pharmacist_manager'];

    private static array $queue = [];
    private static bool $hooked = false;

    /* ===================== الإرسال ===================== */

    /** حدث له مستلمون محددون من الإعدادات */
    public static function event(int $tenantId, string $event, string $title, string $body, ?string $url, string $tag, bool $urgent = false): void
    {
        // من قام بالإجراء لا يُرسل له
        $users = self::recipients($tenantId, $event)->reject(fn ($u) => $u->id === auth()->id());
        self::toUsers($users, $tenantId, $event, self::EVENTS[$event]['module'] ?? null, $title, $body, $url, $tag, $urgent);
    }

    /** إرسال لمستخدمين بعينهم (الاعتمادات · التجربة) */
    public static function toUsers(iterable $users, int $tenantId, string $event, ?string $module, string $title, string $body, ?string $url, string $tag, bool $urgent = false): void
    {
        foreach ($users as $u) {
            if (! $u instanceof User || (int) $u->tenant_id !== $tenantId || ! $u->is_active) {
                continue;
            }
            if ($module && ! Access::can($u, $module, 'view')) {
                continue;
            }
            $k = $u->id . '|' . $tag;
            if (isset(self::$queue[$k])) {
                self::$queue[$k]['lines'][] = $body;
                self::$queue[$k]['urgent'] = self::$queue[$k]['urgent'] || $urgent;
                continue;
            }
            self::$queue[$k] = [
                'tenant_id' => $tenantId, 'user_id' => $u->id, 'event' => $event, 'title' => $title,
                'lines' => [$body], 'url' => $url, 'tag' => $tag, 'urgent' => $urgent,
            ];
        }

        if (self::$queue && ! self::$hooked) {
            self::$hooked = true;
            app()->terminating(fn () => self::flush());
        }
    }

    /**
     * حفظ الإشعارات في الصناديق وإرسالها للأجهزة
     * يرجع: ['notifications' => n, 'devices' => n, 'delivered' => n, 'failed' => n]
     */
    public static function flush(): array
    {
        $stats = ['notifications' => 0, 'devices' => 0, 'delivered' => 0, 'failed' => 0];
        $items = self::$queue;
        self::$queue = [];
        if (! $items) {
            return $stats;
        }

        try {
            $rows = [];
            foreach ($items as $it) {
                $lines = array_values(array_unique(array_filter($it['lines'], fn ($l) => trim($l) !== '')));
                $n = count($it['lines']);
                $body = implode("\n", array_slice($lines, 0, 4)) . (count($lines) > 4 ? "\n+ " . (count($lines) - 4) . ' أخرى' : '');
                $rows[] = AppNotification::withoutGlobalScope('tenant')->create([
                    'tenant_id' => $it['tenant_id'], 'user_id' => $it['user_id'], 'event' => $it['event'],
                    'title'     => mb_substr($it['title'] . ($n > 1 ? " ({$n})" : ''), 0, 160),
                    'body'      => mb_substr($body, 0, 600),
                    'url'       => $it['url'] ? mb_substr($it['url'], 0, 500) : null,
                    'tag'       => mb_substr($it['tag'], 0, 80),
                    'urgent'    => $it['urgent'],
                ]);
            }
            $stats['notifications'] = count($rows);

            if (! WebPush::configured()) {
                return $stats;
            }

            $subs = PushSubscription::query()->whereIn('user_id', collect($rows)->pluck('user_id')->unique())->get()->groupBy('user_id');
            $jobs = [];
            $map = [];
            foreach ($rows as $row) {
                foreach ($subs->get($row->user_id, collect()) as $s) {
                    $jobs[] = [
                        'endpoint' => $s->endpoint, 'p256dh' => $s->p256dh, 'auth' => $s->auth, 'urgent' => $row->urgent,
                        'topic'    => substr(md5($row->tag), 0, 24),
                        'payload'  => json_encode([
                            'id'     => $row->id,
                            'title'  => $row->title,
                            'body'   => mb_substr((string) $row->body, 0, 300),
                            'url'    => route('notifications.open', $row->id),
                            'tag'    => $row->tag,
                            'urgent' => $row->urgent,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];
                    $map[] = $s;
                }
            }
            $stats['devices'] = count($jobs);

            foreach (WebPush::sendMany($jobs) as $i => $code) {
                $s = $map[$i];
                if ($code >= 200 && $code < 300) {
                    $stats['delivered']++;
                    $s->forceFill(['last_ok_at' => now(), 'fails' => 0])->save();
                } elseif (in_array($code, [404, 410], true)) {
                    $stats['failed']++;
                    $s->delete();                                   // ألغى المستخدم الإذن أو أزال التطبيق
                } else {
                    $stats['failed']++;
                    $s->fails >= 20 ? $s->delete() : $s->forceFill(['fails' => $s->fails + 1])->save();
                }
            }
        } catch (\Throwable $e) {
            report($e);                                             // الإشعار لا يوقف العمل أبداً
        }

        return $stats;
    }

    /* ===================== المستلمون ===================== */

    /** مستخدمو المخزن الفعالون */
    public static function users(int $tenantId): Collection
    {
        return User::query()->with('role')->where('tenant_id', $tenantId)->where('is_active', true)->get();
    }

    /** المستلمون المحددون لحدث (أو الافتراضيون) */
    public static function recipients(int $tenantId, string $event): Collection
    {
        $ids = NotifyRule::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('event', $event)->pluck('user_id');
        $all = self::users($tenantId);

        return $ids->isNotEmpty() ? $all->whereIn('id', $ids)->values() : self::defaults($tenantId, $all);
    }

    /** الافتراضي: مدير الجودة والمدير الصيدلي · وإن لم يوجدا فمدير المخزن */
    public static function defaults(int $tenantId, ?Collection $all = null): Collection
    {
        $all ??= self::users($tenantId);
        $d = $all->filter(fn ($u) => $u->hasRole(...self::DEFAULT_ROLES))->values();

        return $d->isNotEmpty() ? $d : $all->filter(fn ($u) => $u->isClientAdmin())->values();
    }

    /**
     * أصحاب الاعتماد لنموذج: مرحلة وسيطة (step) أو الاعتماد النهائي
     * مدير المخزن يُستبعد إذا وُجد صاحب دور، ولا يُرسل للشخص الذي قام بالإجراء
     */
    public static function approvers(int $tenantId, array $form, ?string $step = null): Collection
    {
        $all = self::users($tenantId);
        $ok = $all->filter(fn ($u) => $step
            ? (FormRegistry::stepRights($form, $u)[$step] ?? false)
            : FormRegistry::canFinalize($form, $u));
        $roles = $ok->reject(fn ($u) => $u->isClientAdmin());

        return ($roles->isNotEmpty() ? $roles : $ok)->reject(fn ($u) => $u->id === auth()->id())->values();
    }

    public static function label(string $event): string
    {
        return self::EVENTS[$event]['label'] ?? self::LABELS[$event] ?? $event;
    }
}