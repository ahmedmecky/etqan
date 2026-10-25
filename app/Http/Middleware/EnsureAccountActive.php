<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يُخرج المستخدم فوراً إذا أُوقف حسابه أو أُوقف العميل أثناء الجلسة
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('role', 'tenant');

            if (! $user->is_active || ! $user->role || ($user->tenant && ! $user->tenant->is_active)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'تم إيقاف هذا الحساب. تواصل مع مدير النظام.']);
            }
        }

        return $next($request);
    }
}