<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * تسجيل الدخول:
 *   /login        : مستخدمو المخازن (مدير المخزن والعاملون والمراجع الخارجي)
 *   /admin-login  : مدير النظام فقط (رابط منفصل غير ظاهر في أي صفحة)
 * كل صفحة ترفض الحساب الآخر برسالة عامة (لا تكشف وجود حساب مدير النظام).
 */
class LoginController extends Controller
{
    /** عدد المحاولات الخاطئة المسموح بها قبل الإيقاف المؤقت */
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

    private const WRONG = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';

    public function show(): View
    {
        return view('auth.login', ['admin' => false]);
    }

    public function login(Request $request): RedirectResponse
    {
        return $this->attempt($request, false);
    }

    /** صفحة دخول مدير النظام */
    public function showAdmin(): View
    {
        return view('auth.login', ['admin' => true]);
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        return $this->attempt($request, true);
    }

    private function attempt(Request $request, bool $admin): RedirectResponse
    {
        $data = $request->validate(
            [
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ],
            [
                'email.required'    => 'اكتب البريد الإلكتروني.',
                'email.email'       => 'البريد الإلكتروني غير صحيح.',
                'password.required' => 'اكتب كلمة المرور.',
            ]
        );

        $key = ($admin ? 'admin|' : 'user|') . Str::lower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $admin ? 3 : self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "محاولات دخول كثيرة. حاول مرة أخرى بعد {$seconds} ثانية.",
            ]);
        }

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, $admin ? self::LOCK_SECONDS * 5 : self::LOCK_SECONDS);
            throw ValidationException::withMessages(['email' => self::WRONG]);
        }

        $user = Auth::user()->load('role', 'tenant');

        // كل صفحة لحساباتها فقط: مدير النظام من /admin-login · المخازن من /login
        if ($user->isSuperAdmin() !== $admin) {
            $this->out($request);
            RateLimiter::hit($key, $admin ? self::LOCK_SECONDS * 5 : self::LOCK_SECONDS);
            throw ValidationException::withMessages(['email' => self::WRONG]);
        }

        RateLimiter::clear($key);

        // حساب موقوف · عميل موقوف · بدون دور
        if (! $user->is_active || ! $user->role || ($user->tenant && ! $user->tenant->is_active)) {
            $this->out($request);
            throw ValidationException::withMessages([
                'email' => 'هذا الحساب موقوف. تواصل مع مدير النظام.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $admin = (bool) $request->user()?->isSuperAdmin();
        $this->out($request);

        return redirect()->route($admin ? 'admin.login' : 'login')->with('status', 'تم تسجيل الخروج.');
    }

    private function out(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}