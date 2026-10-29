<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Support\Audits;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يُخرج المستخدم فوراً إذا أُوقف حسابه أو أُوقف العميل أثناء الجلسة أو انتهت مدة حسابه المؤقت
 * المراجع الخارجي: قراءة فقط في كل النظام (عدا شاشة المراجعة المكلف بها) · كل صفحة يفتحها تُسجل
 */
class EnsureAccountActive
{
    /** ما يُسمح للمراجع الخارجي بالكتابة فيه */
    private const AUDITOR_WRITES = ['audits.', 'logout', 'password.update', 'notifications.'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('role', 'tenant');

            $expired = $user->access_until && $user->access_until->format('Y-m-d') < now('Africa/Cairo')->toDateString();
            $auditor = Audits::isAuditor($user);
            if (! $user->is_active || ! $user->role || ($user->tenant && ! $user->tenant->is_active) || $expired || ($auditor && ! $user->access_until)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => $expired || $auditor ? 'انتهت مدة هذا الحساب المؤقت. تواصل مع إدارة الجودة بالمخزن.' : 'تم إيقاف هذا الحساب. تواصل مع مدير النظام.']);
            }

            if ($auditor) {
                $name = (string) $request->route()?->getName();
                $allowed = collect(self::AUDITOR_WRITES)->contains(fn ($p) => $name === $p || (str_ends_with($p, '.') && str_starts_with($name, $p)));
                if (! $request->isMethod('GET') && ! $request->isMethod('HEAD') && ! $allowed) {
                    abort(403, 'حساب المراجع الخارجي للاطلاع فقط. الملاحظات تُكتب في شاشة المراجعة.');
                }
                if (! $request->ajax() && ! $request->expectsJson() && ! str_starts_with($name, 'notifications.')) {
                    try {
                        AuditLog::create(['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'method' => $request->method(),
                            'path' => mb_substr('/' . ltrim($request->path(), '/') . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 0, 255),
                            'route' => $name ?: null, 'ip' => $request->ip(), 'at' => now()]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        return $next($request);
    }
}