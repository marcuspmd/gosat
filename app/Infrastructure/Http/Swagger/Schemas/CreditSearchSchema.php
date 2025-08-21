<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreditSearchRequest',
    title: 'Credit Search Request',
    description: 'Dados para iniciar uma nova consulta de crédito',
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
    description: 'Resposta da consulta de crédito iniciada',
    type: 'object',
    properties: [
        new OA\Property(property: 'request_id', description: 'Request ID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'status', description: 'Status da consulta', type: 'string', enum: ['processing'], example: 'processing'),
        new OA\Property(property: 'message', description: 'Mensagem de status', type: 'string', example: 'Consulta em andamento. Use o request_id para verificar o status.'),
    ]
)]
#[OA\Schema(
    schema: 'CreditStatusResponse',
    title: 'Credit Status Response',
    description: 'Status de uma consulta de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'request_id', description: 'Request ID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'status', description: 'Request status', type: 'string', enum: ['processing', 'completed', 'failed'], example: 'completed'),
        new OA\Property(property: 'message', description: 'Descriptive status message', type: 'string', example: 'Request completed successfully'),
        new OA\Property(property: 'offers_found', description: 'Number of offers found (completed only)', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'created_at', description: 'Request creation date (processing only)', type: 'string', format: 'date-time', nullable: true, example: '2023-12-01T10:30:00Z'),
        new OA\Property(property: 'attempts', description: 'Number of attempts (processing only)', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'failed_at', description: 'Failure date (failed only)', type: 'string', format: 'date-time', nullable: true, example: '2023-12-01T10:35:00Z'),
        new OA\Property(property: 'error', description: 'Error description (failed only)', type: 'string', nullable: true, example: 'Internal error during processing'),
    ]
)]
class CreditSearchSchema {}
