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
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        
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

    // Public Proxy for Thumbnails to bypass CORS and Authentication for NetworkImage
    Route::get('/thumbnails/{path}', function ($path) {
        $fullPath = storage_path('app/public/thumbnails/' . $path);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        $mime = \Illuminate\Support\Facades\File::mimeType($fullPath);
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Access-Control-Allow-Origin' => '*',
        ]);
    })->where('path', '.*');

    // Public Proxy for Files (PDFs, etc) to bypass CORS
    Route::get('/files/{path}', function ($path) {
        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        $mime = \Illuminate\Support\Facades\File::mimeType($fullPath);
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization, X-Request-With',
            'Access-Control-Expose-Headers' => 'Accept-Ranges, Content-Encoding, Content-Length, Content-Range',
        ]);
    })->where('path', '.*');

    // Public Settings Config
    Route::get('/settings/config', [SettingsController::class, 'getPublicSettings']);

    // Public Hierarchical Content Navigation
    Route::get('/educational-stages', [EducationalStageController::class, 'index']);
    Route::get('/educational-stages/{id}', [EducationalStageController::class, 'show']);
    Route::get('/grades/{id}', [GradeController::class, 'show']);
    Route::get('/sections/{id}', [SectionController::class, 'show']);
    Route::get('/subjects/{id}', [SubjectController::class, 'show']);
    Route::get('/units/{id}', [UnitController::class, 'show']);
    Route::get('/lessons/{id}', [LessonController::class, 'show']);
    Route::get('/lesson-files/{id}', [LessonFileController::class, 'show']);

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

            // Dashboard aggregate statistics
            Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
            Route::get('/dashboard/revenue', [DashboardController::class, 'getRevenueData']);
            Route::get('/dashboard/users', [DashboardController::class, 'getUserGrowthData']);
            Route::get('/dashboard/activity', [DashboardController::class, 'getRecentActivity']);
        });
    });
});

