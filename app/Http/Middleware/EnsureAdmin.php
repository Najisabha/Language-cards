<?php

namespace App\Http\Middleware;

use App\Support\AdminGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AdminGate::isAuthenticated()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        return redirect()
            ->guest(route('login'))
            ->with('status', 'سجّل دخول المدير للوصول إلى هذه الصفحة.');
    }
}
