<?php

use Illuminate\Support\Facades\Route;

// Primary versioned API routes: /api/v1/...
Route::prefix('v1')->group(function () {
    require __DIR__ . '/api_v1_routes.php';
});

// Fallback unversioned API routes: /api/... (matches /api/educational-stages, /api/auth/login, etc.)
Route::group([], function () {
    require __DIR__ . '/api_v1_routes.php';
});
