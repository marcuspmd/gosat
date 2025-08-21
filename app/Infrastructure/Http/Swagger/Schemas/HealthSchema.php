<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'HealthCheckResponse',
    title: 'Health Check Response',
    description: 'Resposta do health check do serviço',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'healthy'),
        new OA\Property(property: 'service', type: 'string', example: 'credit-offer-api'),
        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
    ]
)]
class HealthSchema {}
