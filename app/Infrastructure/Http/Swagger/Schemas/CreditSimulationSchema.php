<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreditSimulationRequest',
    title: 'Credit Simulation Request',
    description: 'Data to simulate a credit offer',
    type: 'object',
    required: ['cpf', 'amount', 'installments'],
    properties: [
        new OA\Property(
            property: 'cpf',
            description: 'Customer CPF (numbers only, 11 digits)',
            type: 'string',
            pattern: '^\d{11}$',
            example: '12345678901'
        ),
        new OA\Property(
            property: 'amount',
            description: 'Desired amount in cents',
            type: 'integer',
            minimum: 100,
            example: 5000000
        ),
        new OA\Property(
            property: 'installments',
            description: 'Number of desired installments',
            type: 'integer',
            minimum: 1,
            example: 24
        ),
        new OA\Property(
            property: 'modality',
            description: 'Specific modality (optional)',
            type: 'string',
            nullable: true,
            example: 'Personal Credit'
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
    schema: 'CreditSimulationOffer',
    title: 'Credit Simulation Offer',
    description: 'Details of a simulated offer',
    type: 'object',
    properties: [
        new OA\Property(property: 'financial_institution', description: 'Financial institution name', type: 'string', example: 'Banco Bradesco'),
        new OA\Property(property: 'credit_modality', description: 'Credit modality', type: 'string', example: 'Personal Credit'),
        new OA\Property(property: 'requested_amount', description: 'Requested amount in cents', type: 'integer', example: 5000000),
        new OA\Property(property: 'total_amount', description: 'Total amount to pay in cents', type: 'integer', example: 6200000),
        new OA\Property(property: 'monthly_interest_rate', description: 'Monthly interest rate', type: 'number', format: 'float', example: 0.012),
        new OA\Property(property: 'annual_interest_rate', description: 'Annual interest rate', type: 'number', format: 'float', example: 0.144),
        new OA\Property(property: 'installments', description: 'Number of installments', type: 'integer', example: 24),
        new OA\Property(property: 'monthly_payment', description: 'Monthly payment amount in cents', type: 'integer', example: 258333),
        new OA\Property(property: 'total_interest', description: 'Total interest in cents', type: 'integer', example: 1200000),
        new OA\Property(
            property: 'limits',
            description: 'Available limits for this modality',
            type: 'object',
            properties: [
                new OA\Property(property: 'min_amount', type: 'integer', example: 100000),
                new OA\Property(property: 'max_amount', type: 'integer', example: 10000000),
                new OA\Property(property: 'min_installments', type: 'integer', example: 1),
                new OA\Property(property: 'max_installments', type: 'integer', example: 60),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'CreditSimulationResponse',
    title: 'Credit Simulation Response',
    description: 'Credit simulation result',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['success'], example: 'success'),
        new OA\Property(property: 'cpf', type: 'string', example: '12345678901'),
        new OA\Property(
            property: 'parameters',
            type: 'object',
            properties: [
                new OA\Property(property: 'amount', type: 'integer', example: 5000000),
                new OA\Property(property: 'installments', type: 'integer', example: 24),
            ]
        ),
        new OA\Property(
            property: 'offers',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CreditSimulationOffer')
        ),
        new OA\Property(property: 'total_offers_found', type: 'integer', example: 3),
    ]
)]
class CreditSimulationSchema {}
