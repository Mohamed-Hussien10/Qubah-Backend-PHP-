<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request to verify subscription or admin access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'unauthenticated'
            ], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'الحساب غير مفعّل، يُرجى التواصل مع الإدارة.',
                'error' => 'account_inactive'
            ], 403);
        }

        if (method_exists($user, 'hasActiveSubscription') && !$user->hasActiveSubscription()) {
            return response()->json([
                'message' => 'عفواً، لا يوجد اشتراك سارٍ لهذا الحساب.',
                'error' => 'subscription_expired'
            ], 403);
        }

        return $next($request);
    }
}
