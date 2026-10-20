<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Services\Access\ContentAccessService;

class CheckSubscription
{
    /**
     * Handle an incoming request to verify subscription or content access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user() ?? $request->user();

        // 1. Admin / parent bypass
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }
        if ($user && isset($user->role) && in_array(is_object($user->role) ? $user->role->value : $user->role, ['admin', 'parent'])) {
            return $next($request);
        }

        // 2. Inactive account check
        if ($user && isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'message' => 'الحساب غير مفعّل، يُرجى التواصل مع الإدارة.',
                'error' => 'account_inactive'
            ], 403);
        }

        // 3. Route specific checks
        $lessonId = $request->route('lesson') ?? $request->route('id');
        $path = $request->path();

        if (str_contains($path, 'lessons/')) {
            $lesson = Lesson::find($lessonId);
            if ($lesson && !ContentAccessService::canAccessLesson($lesson, $user)) {
                return response()->json([
                    'message' => 'عفواً، يتطلب الوصول لهذا الدرس اشتراكاً سارياً.',
                    'error' => 'subscription_required',
                    'requires_subscription' => true,
                ], 403);
            }
        } elseif (str_contains($path, 'lesson-files/')) {
            $file = LessonFile::find($lessonId);
            if ($file && !ContentAccessService::canAccessLessonFile($file, $user)) {
                return response()->json([
                    'message' => 'عفواً، يتطلب الوصول لهذا الملف اشتراكاً سارياً.',
                    'error' => 'subscription_required',
                    'requires_subscription' => true,
                ], 403);
            }
        } else {
            // General subscription check
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'unauthenticated'
                ], 401);
            }
            if (method_exists($user, 'hasActiveSubscription') && !$user->hasActiveSubscription()) {
                return response()->json([
                    'message' => 'عفواً، لا يوجد اشتراك سارٍ لهذا الحساب.',
                    'error' => 'subscription_expired'
                ], 403);
            }
        }

        return $next($request);
    }
}
