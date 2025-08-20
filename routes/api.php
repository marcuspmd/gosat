<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Api\CreditOfferController;
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

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', [HealthController::class, 'health'])
        ->name('api.health');

    Route::prefix('credit')->name('api.credit.')->group(function () {

        // Iniciar nova consulta de crédito
        Route::post('/search', [CreditOfferController::class, 'search'])
            ->name('search');

        // Verificar status de uma consulta
        Route::get('/status/{requestId}', [CreditOfferController::class, 'status'])
            ->name('status')
            ->where('requestId', '[a-f0-9-]{36}'); // UUID format

        // Simular oferta de crédito
        Route::post('/simulate', [CreditOfferController::class, 'simulate'])
            ->name('simulate');

        // Listar ofertas para um CPF
        Route::get('/offers', [CreditOfferController::class, 'offers'])
            ->name('offers');
    });
});
