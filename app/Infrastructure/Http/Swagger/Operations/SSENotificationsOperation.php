<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/sse/notifications',
    operationId: 'sseNotifications',
    summary: 'Stream de notificações SSE',
    description: 'Endpoint para receber notificações em tempo real via Server-Sent Events.',
    tags: ['Server-Sent Events'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Stream de eventos iniciado com sucesso',
            content: new OA\MediaType(
                mediaType: 'text/event-stream',
                schema: new OA\Schema(type: 'string')
            )
        ),
    ]
)]
class SSENotificationsOperation {}
