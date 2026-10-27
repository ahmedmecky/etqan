<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\NotifyRule;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Access;
use App\Support\Notifier;
use App\Support\WebPush;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * إعداد الإشعارات (مدير المخزن): من يستلم كل حدث
 */
class NotifyRuleController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $users = Notifier::users($tenantId)->sortBy(fn ($u) => [$u->role?->sort ?? 99, $u->displayName()])->values();
        $rules = NotifyRule::query()->get()->groupBy('event')->map(fn ($g) => $g->pluck('user_id')->all());
        $devices = PushSubscription::query()->where('tenant_id', $tenantId)->selectRaw('user_id, count(*) n')->groupBy('user_id')->pluck('n', 'user_id');

        $events = collect(Notifier::EVENTS)->map(function ($e, $key) use ($tenantId, $rules) {
            $chosen = $rules->get($key, []);

            return $e + [
                'key'      => $key,
                'chosen'   => $chosen,
                'defaults' => $chosen ? [] : Notifier::defaults($tenantId, null, $key)->map(fn ($u) => $u->displayName())->all(),
            ];
        })->values();

        return view('client.notify', [
            'users'   => $users,
            'events'  => $events,
            'devices' => $devices,
            'ready'   => WebPush::configured(),
            'access'  => fn (User $u, ?string $m) => ! $m || Access::can($u, $m, 'view'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $ids = Notifier::users($tenantId)->pluck('id')->all();
        $data = $request->validate([
            'r'     => ['array'],
            'r.*'   => ['array'],
            'r.*.*' => ['integer', Rule::in($ids)],
        ]);

        foreach (array_keys(Notifier::EVENTS) as $event) {
            $chosen = array_values(array_unique(array_map('intval', $data['r'][$event] ?? [])));
            NotifyRule::query()->where('event', $event)->whereNotIn('user_id', $chosen ?: [0])->delete();
            foreach ($chosen as $uid) {
                NotifyRule::query()->firstOrCreate(['tenant_id' => $tenantId, 'event' => $event, 'user_id' => $uid]);
            }
        }

        return back()->with('status', 'تم حفظ مستلمي الإشعارات.');
    }

    /** إشعار تجريبي لمستخدم (للتأكد أن جهازه يستقبل) */
    public function test(Request $request, User $user): RedirectResponse
    {
        abort_unless((int) $user->tenant_id === (int) $request->user()->tenant_id, 404);

        Notifier::toUsers([$user], (int) $user->tenant_id, 'test', null, 'إتقان · إشعار تجريبي',
            'أرسله ' . $request->user()->displayName() . ' للتأكد من وصول تنبيهات الجودة على جهازك ✓', route('notifications.index'), 'test-' . now()->timestamp, true);
        $s = Notifier::flush();

        return back()->with('status', ! $s['devices']
            ? "{$user->displayName()}: لا يوجد جهاز مفعّل. يفتح النظام من موبايله ويضغط «تفعيل الإشعارات»."
            : "{$user->displayName()}: وصل الإشعار إلى {$s['delivered']} من {$s['devices']} جهاز.");
    }
}