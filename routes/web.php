<?php

use Illuminate\Support\Facades\Route;

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
