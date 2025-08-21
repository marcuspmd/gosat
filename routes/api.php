<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Api\CreditOfferController;
use App\Infrastructure\Http\Controllers\Api\SwaggerController;
use App\Infrastructure\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Documentation routes
Route::get('/docs', [SwaggerController::class, 'docs'])->name('api.docs');
Route::get('/docs.json', [SwaggerController::class, 'json'])
    ->middleware(\App\Http\Middleware\FixSwaggerResponse::class)
    ->name('api.docs.json');
Route::post('/docs/generate', [SwaggerController::class, 'generate'])->name('api.docs.generate');

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', [HealthController::class, 'health'])
        ->name('api.health');

    Route::prefix('credit')->name('api.credit.')->group(function () {

        Route::post('/', [CreditOfferController::class, 'creditRequest'])
            ->name('creditRequest');

        Route::post('/search', [CreditOfferController::class, 'creditRequest'])
            ->name('creditSearch');

        Route::get('/status/{requestId}', [CreditOfferController::class, 'getRequestStatus'])
            ->name('getRequestStatus');

        Route::get('/customers-with-offers', [CreditOfferController::class, 'getAllCustomersWithOffers'])
            ->name('customersWithOffers');

        Route::get('/offers', [CreditOfferController::class, 'getCreditOffers'])
            ->name('getOffers');

        Route::post('/simulate', [CreditOfferController::class, 'simulateCredit'])
            ->name('simulateCredit');

    });
});
