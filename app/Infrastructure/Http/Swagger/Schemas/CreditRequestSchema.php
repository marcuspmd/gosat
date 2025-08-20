<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreditSearchRequest',
    title: 'Credit Search Request',
    description: 'Dados para iniciar uma consulta de crédito',
    type: 'object',
    required: ['cpf'],
    properties: [
        new OA\Property(
            property: 'cpf',
            description: 'CPF do cliente (apenas números, 11 dígitos)',
            type: 'string',
            pattern: '^\d{11}$',
            example: '12345678901'
        ),
    ]
)]
#[OA\Schema(
    schema: 'CreditSearchResponse',
    title: 'Credit Search Response',
    description: 'Resposta da inicialização de consulta de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'request_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'cpf', type: 'string', example: '12345678901'),
        new OA\Property(property: 'status', type: 'string', example: 'processing'),
        new OA\Property(property: 'message', type: 'string', example: 'Consulta de crédito iniciada com sucesso'),
        new OA\Property(property: 'estimated_completion', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'CreditStatusResponse',
    title: 'Credit Status Response',
    description: 'Status de uma consulta de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'request_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'cpf', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['processing', 'completed', 'completed_with_failures', 'failed']),
        new OA\Property(property: 'progress', type: 'object', properties: [
            new OA\Property(property: 'total_institutions', type: 'integer'),
            new OA\Property(property: 'completed_institutions', type: 'integer'),
            new OA\Property(property: 'failed_institutions', type: 'integer'),
        ]),
        new OA\Property(property: 'offers_count', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CreditRequestSchema
{
}