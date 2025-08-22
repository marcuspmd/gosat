<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/v1/sse/test',
    operationId: 'sseTestEvent',
    summary: 'Enviar evento de teste',
    description: 'Envia um evento de teste para o stream SSE.',
    tags: ['Server-Sent Events'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Evento de teste enviado com sucesso',
            content: new OA\JsonContent(
                properties: [
                    'message' => new OA\Property(property: 'message', type: 'string', example: 'Test event sent successfully'),
                ],
                type: 'object'
            )
        ),
    ]
)]
class SSETestOperation {}
