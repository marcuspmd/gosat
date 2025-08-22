<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/v1/sse/clear',
    operationId: 'sseClearEvents',
    summary: 'Limpar eventos SSE',
    description: 'Limpa todos os eventos pendentes do stream SSE.',
    tags: ['Server-Sent Events'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Eventos limpos com sucesso',
            content: new OA\JsonContent(
                properties: [
                    'message' => new OA\Property(property: 'message', type: 'string', example: 'Events cleared successfully'),
                ],
                type: 'object'
            )
        ),
    ]
)]
class SSEClearOperation {}