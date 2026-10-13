<?php

use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\ThumbnailController;
use App\Http\Controllers\Api\v1\EducationalStageController;
use App\Http\Controllers\Api\v1\GradeController;
use App\Http\Controllers\Api\v1\SectionController;
use App\Http\Controllers\Api\v1\SubjectController;
use App\Http\Controllers\Api\v1\UnitController;
use App\Http\Controllers\Api\v1\LessonController;
use App\Http\Controllers\Api\v1\LessonFileController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\SettingsController;
use App\Http\Controllers\Api\v1\FreeTrialController;
use App\Http\Controllers\Api\v1\PackageController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckSubscription;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
        Route::get('/login', fn () => response()->json(['message' => 'Unauthenticated.', 'error' => 'unauthenticated'], 401))->name('login');
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // User Routes (auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/user/profile/update', [AuthController::class, 'updateProfile']);
        Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    });

    // Public Proxy for Thumbnails to bypass CORS with path-traversal protection
    Route::get('/thumbnails/{path}', function ($path) {
        if (str_contains($path, '..')) {
            abort(403);
        }
        $baseDir = realpath(storage_path('app/public/thumbnails'));
        $targetPath = storage_path('app/public/thumbnails/' . $path);
        $fullPath = realpath($targetPath);
        if (!$fullPath || !$baseDir || !str_starts_with($fullPath, $baseDir) || !file_exists($fullPath)) {
            abort(404);
        }
        $mime = \Illuminate\Support\Facades\File::mimeType($fullPath);
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Access-Control-Allow-Origin' => '*',
        ]);
    })->where('path', '.*');

    // Secure Proxy for Files (PDFs, Videos, Audios) - require signed URL or active Free Trial lesson file
    Route::get('/files/{path}', function (\Illuminate\Http\Request $request, $path) {
        if (str_contains($path, '..')) {
            abort(403);
        }
        $baseDir = realpath(storage_path('app/public'));
        $targetPath = storage_path('app/public/' . $path);
        $fullPath = realpath($targetPath);
        if (!$fullPath || !$baseDir || !str_starts_with($fullPath, $baseDir) || !file_exists($fullPath)) {
            abort(404);
        }

        $isValidSignature = $request->hasValidSignature();
        $isFreeTrialFile = false;
        if (!$isValidSignature) {
            $cleanPath = preg_replace('/^storage\//', '', $path);
            $isFreeTrialFile = \App\Models\FreeTrialLessonFile::where('file_path', $path)
                ->orWhere('file_path', $cleanPath)
                ->orWhere('file_path', 'storage/' . $cleanPath)
                ->exists();
        }

        if (!$isValidSignature && !$isFreeTrialFile) {
            abort(401);
        }

        $mime = \Illuminate\Support\Facades\File::mimeType($fullPath);
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization, X-Request-With',
            'Access-Control-Expose-Headers' => 'Accept-Ranges, Content-Encoding, Content-Length, Content-Range',
        ]);
    })->name('api.files.show')->where('path', '.*');

    // Public Settings Config
    Route::get('/settings/config', [SettingsController::class, 'getPublicSettings']);

    // Packages (Public)
    Route::get('/packages', [PackageController::class, 'index']);
    Route::get('/packages/{id}', [PackageController::class, 'show']);

    // Public Free Trial Navigation
    Route::get('/free-trial/stages', [FreeTrialController::class, 'getStages']);
    Route::get('/free-trial/stages/{id}/grades', [FreeTrialController::class, 'getGradesByStage']);
    Route::get('/free-trial/grades/{id}/subjects', [FreeTrialController::class, 'getSubjectsByGrade']);
    Route::get('/free-trial/subjects/{id}', [FreeTrialController::class, 'showSubject']);
    Route::get('/free-trial/subjects/{id}/lesson-files', [FreeTrialController::class, 'getLessonFilesBySubject']);
    Route::get('/free-trial/lesson-files/{id}', [FreeTrialController::class, 'showLessonFile']);

    // Protected Content Navigation (Requires auth:sanctum and active subscription)
    Route::middleware(['auth:sanctum', CheckSubscription::class])->group(function () {
        Route::get('/educational-stages', [EducationalStageController::class, 'index']);
        Route::get('/educational-stages/{id}', [EducationalStageController::class, 'show']);
        Route::get('/grades/{id}', [GradeController::class, 'show']);
        Route::get('/sections/{id}', [SectionController::class, 'show']);
        Route::get('/subjects/{id}', [SubjectController::class, 'show']);
        Route::get('/units/{id}', [UnitController::class, 'show']);
        Route::get('/lessons/{id}', [LessonController::class, 'show']);
        Route::get('/lesson-files/{id}', [LessonFileController::class, 'show']);
        Route::get('/stages/{id}/file-thumbnails', [ThumbnailController::class, 'getStageDefaultThumbnails']);
        Route::get('/educational-stages/{id}/file-thumbnails', [ThumbnailController::class, 'getStageDefaultThumbnails']);
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Thumbnail Uploads & Retrieval (with CORS)
        Route::post('/thumbnails/upload', [ThumbnailController::class, 'upload']);
        Route::delete('/thumbnails', [ThumbnailController::class, 'delete']);

        // Admin Content & Management (Requires Admin Role)
        Route::middleware(CheckRole::class.':admin')->group(function () {
            // Educational Hierarchy Management
            Route::post('/educational-stages', [EducationalStageController::class, 'store']);
            Route::put('/educational-stages/{id}', [EducationalStageController::class, 'update']);
            Route::delete('/educational-stages/{id}', [EducationalStageController::class, 'destroy']);

            Route::post('/grades', [GradeController::class, 'store']);
            Route::put('/grades/{id}', [GradeController::class, 'update']);
            Route::delete('/grades/{id}', [GradeController::class, 'destroy']);

            Route::post('/sections', [SectionController::class, 'store']);
            Route::put('/sections/{id}', [SectionController::class, 'update']);
            Route::delete('/sections/{id}', [SectionController::class, 'destroy']);

            Route::post('/subjects', [SubjectController::class, 'store']);
            Route::put('/subjects/{id}', [SubjectController::class, 'update']);
            Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

            Route::post('/units', [UnitController::class, 'store']);
            Route::put('/units/{id}', [UnitController::class, 'update']);
            Route::delete('/units/{id}', [UnitController::class, 'destroy']);

            Route::post('/lessons', [LessonController::class, 'store']);
            Route::put('/lessons/{id}', [LessonController::class, 'update']);
            Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);

            Route::post('/lesson-files/upload', [LessonFileController::class, 'upload']);
            Route::post('/lesson-files', [LessonFileController::class, 'store']);
            Route::put('/lesson-files/{id}', [LessonFileController::class, 'update']);
            Route::delete('/lesson-files/{id}', [LessonFileController::class, 'destroy']);

            // Packages Management
            Route::post('/packages', [PackageController::class, 'store']);
            Route::put('/packages/{id}', [PackageController::class, 'update']);
            Route::delete('/packages/{id}', [PackageController::class, 'destroy']);

            // Free Trial Management
            Route::post('/free-trial/stages', [FreeTrialController::class, 'storeStage']);
            Route::put('/free-trial/stages/{id}', [FreeTrialController::class, 'updateStage']);
            Route::delete('/free-trial/stages/{id}', [FreeTrialController::class, 'destroyStage']);
            Route::post('/free-trial/grades', [FreeTrialController::class, 'storeGrade']);
            Route::put('/free-trial/grades/{id}', [FreeTrialController::class, 'updateGrade']);
            Route::delete('/free-trial/grades/{id}', [FreeTrialController::class, 'destroyGrade']);
            Route::post('/free-trial/subjects', [FreeTrialController::class, 'storeSubject']);
            Route::put('/free-trial/subjects/{id}', [FreeTrialController::class, 'updateSubject']);
            Route::delete('/free-trial/subjects/{id}', [FreeTrialController::class, 'destroySubject']);
            Route::post('/free-trial/lesson-files/upload', [FreeTrialController::class, 'uploadLessonFile']);
            Route::post('/free-trial/lesson-files', [FreeTrialController::class, 'storeLessonFile']);
            Route::put('/free-trial/lesson-files/{id}', [FreeTrialController::class, 'updateLessonFile']);
            Route::delete('/free-trial/lesson-files/{id}', [FreeTrialController::class, 'destroyLessonFile']);

            // User Management
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

            // Settings Management
            Route::get('/settings', [SettingsController::class, 'getSettings']);
            Route::put('/settings', [SettingsController::class, 'updateSettings']);

            // Stage Default Thumbnails Management
            Route::post('/stages/{id}/file-thumbnails', [ThumbnailController::class, 'saveStageDefaultThumbnails']);
            Route::post('/educational-stages/{id}/file-thumbnails', [ThumbnailController::class, 'saveStageDefaultThumbnails']);
            Route::delete('/stages/{id}/file-thumbnails/{format}', [ThumbnailController::class, 'deleteStageDefaultThumbnail']);
            Route::delete('/educational-stages/{id}/file-thumbnails/{format}', [ThumbnailController::class, 'deleteStageDefaultThumbnail']);
        });
    });
});

