<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/credit/status/{requestId}',
    operationId: 'getCreditOfferStatus',
    summary: 'Verificar status de consulta',
    description: 'Verifica o status atual de uma consulta de crédito e retorna os resultados quando disponíveis.',
    tags: ['Credit Offers'],
    parameters: [
        new OA\Parameter(
            name: 'requestId',
            description: 'ID único da consulta de crédito (UUID)',
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
            response: 202,
            description: 'Consulta ainda em processamento',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'processing'),
                    new OA\Property(property: 'message', type: 'string', example: 'Consulta ainda em processamento'),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Consulta não encontrada',
            content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
        ),
    ]
)]
class CreditStatusOperation
{
}