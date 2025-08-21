<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('CreditConsultation');
})->name('home');

// SSE routes moved to api.php (versioned API) for consistency
