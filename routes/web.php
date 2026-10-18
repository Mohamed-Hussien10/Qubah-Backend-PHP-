<?php

use Illuminate\Support\Facades\Route;

// Root-level API routes: /educational-stages, /free-trial/stages, /auth/login, etc.
// Required because the production mobile app makes requests with leading slashes,
// which causes Dio to resolve them against the domain root (stripping /api/v1/).
Route::middleware('api')->group(function () {
    require __DIR__ . '/api_v1_routes.php';
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard/{any?}', function () {
    $indexPath = public_path('dashboard/index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }
    return response('<h3>لوحة التحكم قيد التجهيز</h3>', 200, ['Content-Type' => 'text/html; charset=utf-8']);
})->where('any', '.*');
