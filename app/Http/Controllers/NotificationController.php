<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\PushSubscription;
use App\Support\Notifier;
use App\Support\WebPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الإشعارات للمستخدم: الجرس · صندوق الإشعارات · تفعيل الإشعار على الجهاز
 */
class NotificationController extends Controller
{
    /** صندوق الإشعارات */
    public function index(Request $request): View
    {
        $user = $request->user();
        $only = $request->query('only') === 'unread';

        $list = AppNotification::query()->mine($user)
            ->when($only, fn ($q) => $q->unread())
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('notifications.index', [
            'list'    => $list,
            'only'    => $only,
            'unread'  => AppNotification::query()->mine($user)->unread()->count(),
            'devices' => PushSubscription::query()->where('user_id', $user->id)->orderByDesc('updated_at')->get(),
            'ready'   => WebPush::configured(),
        ]);
    }

    /** للجرس: عدد غير المقروء + آخر ١٠ (يُستدعى كل دقيقة) */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = AppNotification::query()->mine($user)->latest('id')->limit(10)->get();

        return response()->json([
            'unread' => AppNotification::query()->mine($user)->unread()->count(),
            'last'   => (int) ($rows->first()?->id ?? 0),
            'items'  => $rows->map(fn (AppNotification $n) => [
                'id'     => $n->id,
                'title'  => $n->title,
                'body'   => $n->body,
                'event'  => $n->event,
                'label'  => Notifier::label($n->event),
                'urgent' => $n->urgent,
                'read'   => (bool) $n->read_at,
                'ago'    => $n->created_at?->locale('ar')->diffForHumans(),
                'url'    => route('notifications.open', $n->id),
            ])->values(),
        ]);
    }

    /** فتح إشعار: يُعلَّم مقروءاً ثم ينتقل للسجل */
    public function open(Request $request, int $id): RedirectResponse
    {
        $n = AppNotification::query()->mine($request->user())->findOrFail($id);
        if (! $n->read_at) {
            $n->forceFill(['read_at' => now()])->save();
        }
        // روابط داخل النظام فقط
        $url = $n->url && str_starts_with($n->url, url('/')) ? $n->url : route('notifications.index');

        return redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        AppNotification::query()->mine($request->user())->unread()->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('status', 'تم تعليم كل الإشعارات كمقروءة.');
    }

    /* ===================== تفعيل الإشعار على الجهاز ===================== */

    public function subscribe(Request $request): JsonResponse
    {
        abort_unless(WebPush::configured(), 503, 'إشعارات الموبايل غير مفعلة على الخادم بعد (مفاتيح VAPID).');

        $data = $request->validate([
            'endpoint'    => ['required', 'url:https', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:120'],
            'keys.auth'   => ['required', 'string', 'max:40'],
        ]);
        if (strlen(WebPush::b64d($data['keys']['p256dh'])) !== 65 || strlen(WebPush::b64d($data['keys']['auth'])) < 16) {
            return response()->json(['message' => 'بيانات الجهاز غير صحيحة. أعد المحاولة.'], 422);
        }

        $user = $request->user();
        // نفس الجهاز لمستخدم آخر (جهاز مشترك): ينتقل للمستخدم الحالي
        $s = PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashOf($data['endpoint'])],
            [
                'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'], 'auth' => $data['keys']['auth'],
                'device' => mb_substr(self::device($request), 0, 120), 'fails' => 0,
            ]
        );

        return response()->json(['ok' => true, 'id' => $s->id, 'devices' => PushSubscription::query()->where('user_id', $user->id)->count()]);
    }

    public function unsubscribe(Request $request): JsonResponse|RedirectResponse
    {
        $q = PushSubscription::query()->where('user_id', $request->user()->id);
        if ($request->filled('endpoint')) {
            $q->where('endpoint_hash', PushSubscription::hashOf((string) $request->input('endpoint')));
        } elseif ($request->filled('id')) {
            $q->whereKey((int) $request->input('id'));
        } else {
            abort(422);
        }
        $q->delete();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('status', 'تم إيقاف الإشعارات على الجهاز.');
    }

    /** إشعار تجريبي لأجهزتي */
    public function test(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        Notifier::toUsers([$user], (int) $user->tenant_id, 'test', null, 'إتقان · إشعار تجريبي',
            'الإشعارات تعمل على هذا الجهاز ✓ ستصلك تنبيهات الجودة هنا.', route('notifications.index'), 'test-' . now()->timestamp, true);
        $s = Notifier::flush();

        $msg = ! $s['devices']
            ? 'لا يوجد جهاز مفعّل عليه الإشعار. اضغط «تفعيل الإشعارات» من الموبايل أولاً.'
            : ($s['delivered'] ? "تم الإرسال إلى {$s['delivered']} من {$s['devices']} جهاز." : 'تعذر الإرسال للأجهزة. أعد تفعيل الإشعارات على الجهاز.');

        return $request->expectsJson()
            ? response()->json(['message' => $msg] + $s, $s['delivered'] || ! $s['devices'] ? 200 : 502)
            : back()->with('status', $msg);
    }

    private static function device(Request $request): string
    {
        $ua = (string) $request->userAgent();
        $os = match (true) {
            str_contains($ua, 'Android')                                  => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')      => 'iPhone / iPad',
            str_contains($ua, 'Windows')                                  => 'Windows',
            str_contains($ua, 'Mac OS')                                   => 'Mac',
            default                                                       => 'جهاز',
        };
        $br = match (true) {
            str_contains($ua, 'Edg/')                                     => 'Edge',
            str_contains($ua, 'SamsungBrowser')                           => 'Samsung Internet',
            str_contains($ua, 'Firefox')                                  => 'Firefox',
            str_contains($ua, 'Chrome')                                   => 'Chrome',
            str_contains($ua, 'Safari')                                   => 'Safari',
            default                                                       => '',
        };

        return trim($os . ' · ' . $br, ' ·');
    }
}