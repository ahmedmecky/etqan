<?php

use App\Models\Tenant;
use App\Support\HomeDashboard;
use App\Support\Notifier;
use App\Support\WebPush;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| إشعارات الموبايل: إنشاء مفاتيح VAPID (مرة واحدة لكل خادم)
|--------------------------------------------------------------------------
*/
Artisan::command('etqan:push-keys {--force : إنشاء مفاتيح جديدة (يُلغي اشتراك كل الأجهزة)}', function () {
    $path = base_path('.env');
    if (! is_file($path) || ! is_writable($path)) {
        $this->error('ملف .env غير موجود أو غير قابل للكتابة.');

        return 1;
    }
    $env = file_get_contents($path);
    if (preg_match('/^VAPID_PUBLIC_KEY=\S+/m', $env) && ! $this->option('force')) {
        $this->info('Push keys already exist in .env (use --force to replace them).');

        return 0;
    }

    $k = WebPush::createKeys();
    $set = function (string $env, string $key, string $value): string {
        $line = $key . '=' . $value;

        return preg_match('/^' . $key . '=.*$/m', $env)
            ? preg_replace('/^' . $key . '=.*$/m', $line, $env)
            : rtrim($env) . "\n" . $line . "\n";
    };
    $env = $set($env, 'VAPID_PUBLIC_KEY', $k['public']);
    $env = $set($env, 'VAPID_PRIVATE_KEY', $k['private']);
    if (! preg_match('/^VAPID_SUBJECT=/m', $env)) {
        $env = $set($env, 'VAPID_SUBJECT', 'mailto:support@etqanpharma.tech');
    }
    file_put_contents($path, $env);

    if ($this->option('force')) {
        \App\Models\PushSubscription::query()->delete();
        $this->warn('Old device subscriptions were removed. Each user must enable notifications again.');
    }
    $this->info('Push keys saved in .env.');
    $this->line('Now run: php artisan optimize:clear   (on the server: php artisan optimize)');

    return 0;
})->purpose('Create the VAPID keys for mobile push notifications');

/*
|--------------------------------------------------------------------------
| ملخص الصباح: ما ينتظر كل مستلم وما تأخر (نفس «يتطلب انتباهك» في الرئيسية)
|--------------------------------------------------------------------------
*/
Artisan::command('etqan:daily-alerts {--dry : عرض الملخص بدون إرسال}', function () {
    $date = Carbon::today('Africa/Cairo')->toDateString();
    $total = 0;

    foreach (Tenant::query()->where('is_active', true)->get() as $tenant) {
        foreach (Notifier::recipients($tenant->id, 'overdue') as $u) {
            Auth::setUser($u);
            $alerts = collect(HomeDashboard::for($u)['alerts'] ?? []);
            if ($alerts->isEmpty()) {
                continue;
            }
            $n = (int) $alerts->sum('n');
            $title = "ملخص الصباح · {$n} " . ($n === 1 ? 'مهمة تنتظرك' : 'مهام تنتظرك');
            $body = $alerts->take(5)->map(fn ($a) => '• ' . $a['title'] . (! empty($a['sub']) ? ': ' . $a['sub'] : ''))->implode("\n");

            if ($this->option('dry')) {
                $this->line("[{$tenant->code}] {$u->displayName()} · {$title}\n{$body}\n");
                continue;
            }
            Notifier::toUsers([$u], $tenant->id, 'overdue', null, $title, $body, route('home'), 'daily-' . $date,
                $alerts->contains(fn ($a) => ($a['tone'] ?? '') === 'bad'));
            $total++;
        }
        $s = Notifier::flush();
        if (! $this->option('dry') && $s['notifications']) {
            $this->line("[{$tenant->code}] {$s['notifications']} summary · {$s['delivered']}/{$s['devices']} devices");
        }
        Auth::forgetUser();
    }

    $this->info($this->option('dry') ? 'Dry run finished.' : "Sent {$total} morning summaries.");

    return 0;
})->purpose('Send the morning summary of overdue and waiting items');

/*
|--------------------------------------------------------------------------
| مهام اليوم: إشعار بكل قراءة حرارة أو مهمة يومية فات موعدها ولم تُسجل (مرة واحدة لكل مهمة في اليوم)
|   للمسؤولين عن التنفيذ + مستلمي «مهام يومية لم تُنجز» (افتراضياً مدير الجودة والمدير الصيدلي)
|--------------------------------------------------------------------------
*/
Artisan::command('etqan:daily-check {--dry : عرض المتأخر بدون إرسال}', function () {
    $sent = 0;
    foreach (Tenant::query()->where('is_active', true)->get() as $tenant) {
        foreach (\App\Support\DailyTasks::missing($tenant->id) as $m) {
            $already = \App\Models\AppNotification::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('tag', $m['tag'])->exists();
            if ($already) {
                continue;
            }
            if ($this->option('dry')) {
                $this->line("[{$tenant->code}] {$m['title']} · {$m['body']} → " . $m['users']->map->displayName()->implode('، '));
                continue;
            }
            Notifier::toUsers($m['users'], $tenant->id, 'daily', null, $m['title'], $m['body'], $m['url'], $m['tag'], true);
            Notifier::event($tenant->id, 'daily', $m['title'], $m['body'], $m['url'], $m['tag'], true);
            $sent++;
        }
        // السحب: تجاوز موعد إبلاغ العملاء (مرة يومياً لكل سحب بعد ٩ ص)
        if (now('Africa/Cairo')->format('H:i') >= '09:00') {
            foreach (\App\Support\Recalls::overdue($tenant->id) as $rc) {
                $tag = 'recall-late-' . $rc->id . '-' . now('Africa/Cairo')->format('Ymd');
                if (\App\Models\AppNotification::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('tag', $tag)->exists()) {
                    continue;
                }
                $left = $rc->customers->whereNull('notified_at')->count();
                $this->line("[{$tenant->code}] سحب {$rc->number} · {$left} عميل لم يُبلغ");
                if (! $this->option('dry')) {
                    Notifier::event($tenant->id, 'recall', 'سحب تجاوز موعد الإبلاغ: ' . $rc->product,
                        "{$left} عميل لم يُبلغ · تشغيلة {$rc->batch} · الموعد " . $rc->deadline->format('Y-m-d') . ' · ' . $rc->number,
                        route('recalls.show', $rc), $tag, true);
                    $sent++;
                }
            }
        }

        // المهام المكلف بها: تذكير قبل الموعد ويومه · إشعار التأخير
        $sent += \App\Support\Tasks::remind($tenant->id, (bool) $this->option('dry'), fn ($l) => $this->line("[{$tenant->code}] مهمة · {$l}"));
        Notifier::flush();
        \App\Support\TempRounds::forget();
    }
    $this->info($this->option('dry') ? 'Dry run finished.' : "Sent {$sent} missing-task alerts.");

    return 0;
})->purpose('Notify about daily tasks and temperature readings that are past due');

Schedule::command('etqan:daily-check')->everyThirtyMinutes()->between('07:00', '23:30')->timezone('Africa/Cairo')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| الصيانة والأجهزة: إشعار بعقود الصيانة والطفايات ومعايرة الأجهزة التي اقترب موعدها أو انتهى
|   مرة عند دخول فترة التنبيه · ثم قبل الموعد بأسبوع · ثم يوم الانتهاء وكل ٧ أيام بعده
|   للمستلمين في «انتهاء عقود الصيانة والأجهزة» (افتراضياً المدير الصيدلي ومدير الجودة والمدير العام)
|--------------------------------------------------------------------------
*/
Artisan::command('etqan:maint-check {--dry : عرض ما سيُرسل بدون إرسال}', function () {
    $sent = 0;
    $day = now('Africa/Cairo')->format('Ymd');
    foreach (Tenant::query()->where('is_active', true)->get() as $tenant) {
        foreach (\App\Support\Maintenance::due($tenant->id) as $m) {
            if ($this->option('dry')) {
                $this->line("[{$tenant->code}] {$m['title']} · {$m['body']} ({$m['stage']})");
                continue;
            }
            $isContract = $m['model'] instanceof \App\Models\MaintContract;
            // نفس الوسم في نفس التشغيل = إشعار واحد مجمع لكل شخص
            $tag = 'maint-' . ($isContract ? 'c' : 'a') . '-' . $day . '-' . (str_starts_with($m['stage'], 'expired') ? 'x' : 's');
            Notifier::event($tenant->id, 'maint', $m['title'], $m['body'], $isContract ? $m['url'] : route('maint.index', ['tab' => 'assets', 'st' => str_starts_with($m['stage'], 'expired') ? 'expired' : 'soon']), $tag, $m['urgent']);
            $m['model']->forceFill(['alert_stage' => $m['stage']])->save();
            $sent++;
        }
        Notifier::flush();
    }
    $this->info($this->option('dry') ? 'Dry run finished.' : "Sent {$sent} maintenance alerts.");

    return 0;
})->purpose('Notify about maintenance contracts, fire extinguishers and calibrations that are due or expired');

Schedule::command('etqan:maint-check')->dailyAt('08:30')->timezone('Africa/Cairo')->withoutOverlapping();

// يومياً ٨ صباحاً بتوقيت القاهرة (عدا الجمعة) · يتطلب cron: * * * * * php artisan schedule:run
Schedule::command('etqan:daily-alerts')->dailyAt('08:00')->timezone('Africa/Cairo')->days([0, 1, 2, 3, 4, 6])->withoutOverlapping();