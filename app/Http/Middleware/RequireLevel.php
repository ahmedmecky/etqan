<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يسمح فقط لمستوى معين: level:1 (مدير النظام) · level:2 (مدير العميل) · level:1,2
 */
class RequireLevel
{
    public function handle(Request $request, Closure $next, string ...$levels): Response
    {
        $level = (string) $request->user()?->role?->level;

        if (! in_array($level, $levels, true)) {
            abort(403, 'غير مصرح لك بفتح هذه الصفحة.');
        }

        return $next($request);
    }
}