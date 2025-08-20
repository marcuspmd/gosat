<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreditSimulationRequest',
    title: 'Credit Simulation Request',
    description: 'Dados para simular uma oferta de crédito',
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
        new OA\Property(
            property: 'offer_id',
            description: 'ID da oferta de crédito (opcional)',
            type: 'string',
            format: 'uuid',
            example: '550e8400-e29b-41d4-a716-446655440000'
        ),
        new OA\Property(
            property: 'amount',
            description: 'Valor desejado em centavos',
            type: 'integer',
            minimum: 100,
            example: 100000
        ),
        new OA\Property(
            property: 'installments',
            description: 'Número de parcelas desejadas',
            type: 'integer',
            minimum: 1,
            maximum: 120,
            example: 12
        ),
    ]
)]
#[OA\Schema(
    schema: 'MoneyValue',
    title: 'Money Value',
    description: 'Representação de valor monetário',
    type: 'object',
    properties: [
        new OA\Property(property: 'cents', type: 'integer', example: 100000),
        new OA\Property(property: 'formatted', type: 'string', example: 'R$ 1.000,00'),
    ]
)]
#[OA\Schema(
    schema: 'InterestRate',
    title: 'Interest Rate',
    description: 'Representação de taxa de juros',
    type: 'object',
    properties: [
        new OA\Property(property: 'monthly', type: 'number', format: 'float', example: 1.2),
        new OA\Property(property: 'annual', type: 'number', format: 'float', example: 15.39),
        new OA\Property(property: 'formatted_monthly', type: 'string', example: '1,2000% a.m.'),
        new OA\Property(property: 'formatted_annual', type: 'string', example: '15,39% a.a.'),
    ]
)]
#[OA\Schema(
    schema: 'CreditSimulationResponse',
    title: 'Credit Simulation Response',
    description: 'Resultado da simulação de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'cpf', type: 'string', example: '12345678901'),
        new OA\Property(property: 'requested_amount', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'installments', type: 'integer', example: 12),
        new OA\Property(property: 'monthly_payment', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'total_amount', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'total_interest', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'interest_rate', ref: '#/components/schemas/InterestRate'),
    ]
)]
class CreditSimulationSchema
{
}