<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/health',
    operationId: 'healthCheck',
    summary: 'Health check',
    description: 'Verifica se o serviço está funcionando corretamente.',
    tags: ['System'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Serviço funcionando corretamente',
            content: new OA\JsonContent(ref: '#/components/schemas/HealthCheckResponse')
        ),
    ]
)]
class HealthCheckOperation {}
