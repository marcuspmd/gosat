<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthController extends Controller
{
    /**
     * Health check para verificar se o serviço está funcionando.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'credit-offer-api',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
