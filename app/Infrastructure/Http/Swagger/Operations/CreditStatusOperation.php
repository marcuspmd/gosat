<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/credit/status/{requestId}',
    operationId: 'getCreditRequestStatus',
    summary: 'Verificar status de consulta de crédito',
    description: 'Verifica o status de processamento de uma consulta de crédito usando o request_id retornado pela consulta inicial.',
    tags: ['Credit Offers'],
    parameters: [
        new OA\Parameter(
            name: 'requestId',
            description: 'ID da requisição de crédito (UUID)',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Status da consulta obtido com sucesso',
            content: new OA\JsonContent(ref: '#/components/schemas/CreditStatusResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'ID de requisição inválido',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'invalid_request_id'),
                    new OA\Property(property: 'message', type: 'string', example: 'ID de requisição inválido'),
                ]
            )
        ),
        new OA\Response(
            response: 500,
            description: 'Erro interno do servidor',
            content: new OA\JsonContent(ref: '#/components/schemas/InternalError')
        ),
    ]
)]
class CreditStatusOperation {}
