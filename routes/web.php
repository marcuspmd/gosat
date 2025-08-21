<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('CreditConsultation');
})->name('home');

Route::get('/api/sse/notifications', [App\Infrastructure\Http\Controllers\Api\SSEController::class, 'stream'])
    ->name('sse.notifications');

Route::post('/api/sse/test', [App\Infrastructure\Http\Controllers\Api\SSEController::class, 'testEvent'])
    ->name('sse.test');

Route::post('/api/sse/clear', [App\Infrastructure\Http\Controllers\Api\SSEController::class, 'clearEvents'])
    ->name('sse.clear');
