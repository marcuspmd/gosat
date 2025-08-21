<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Institution',
    title: 'Financial Institution',
    description: 'Instituição financeira',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', description: 'ID da instituição', type: 'string', example: 'banco-bradesco'),
    ]
)]
#[OA\Schema(
    schema: 'CreditModality',
    title: 'Credit Modality',
    description: 'Modalidade de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', description: 'ID da modalidade', type: 'string', example: 'personal-credit'),
        new OA\Property(property: 'name', description: 'Nome da modalidade', type: 'string', example: 'Crédito Pessoal'),
        new OA\Property(property: 'standard_code', description: 'Código padronizado da modalidade', type: 'string', example: 'PERSONAL_CREDIT'),
    ]
)]
#[OA\Schema(
    schema: 'AmountLimits',
    title: 'Amount Limits',
    description: 'Limites de valor para crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'min', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'max', ref: '#/components/schemas/MoneyValue'),
    ]
)]
#[OA\Schema(
    schema: 'InstallmentLimits',
    title: 'Installment Limits',
    description: 'Limites de parcelas',
    type: 'object',
    properties: [
        new OA\Property(property: 'min', description: 'Número mínimo de parcelas', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'CalculatedValues',
    title: 'Calculated Values',
    description: 'Valores calculados para simulação',
    type: 'object',
    properties: [
        new OA\Property(property: 'monthly_payment', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'total_amount', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'total_interest', ref: '#/components/schemas/MoneyValue'),
        new OA\Property(property: 'effective_rate', description: 'Taxa efetiva calculada', type: 'number', format: 'float', example: 1.2),
    ]
)]
#[OA\Schema(
    schema: 'CreditOfferBasic',
    title: 'Credit Offer Basic',
    description: 'Estrutura básica de uma oferta de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'institution_name', description: 'Nome da instituição', type: 'string', example: 'Banco Bradesco'),
        new OA\Property(property: 'modality_name', description: 'Nome da modalidade', type: 'string', example: 'Crédito Pessoal'),
        new OA\Property(property: 'max_amount_cents', description: 'Valor máximo em centavos', type: 'integer', example: 5000000),
        new OA\Property(property: 'min_amount_cents', description: 'Valor mínimo em centavos', type: 'integer', example: 100000),
        new OA\Property(property: 'max_installments', description: 'Número máximo de parcelas', type: 'integer', example: 60),
        new OA\Property(property: 'min_installments', description: 'Número mínimo de parcelas', type: 'integer', example: 1),
        new OA\Property(property: 'monthly_interest_rate', description: 'Taxa de juros mensal', type: 'number', format: 'float', example: 0.012),
        new OA\Property(property: 'created_at', description: 'Data de criação', type: 'string', format: 'date-time', example: '2023-12-01T10:30:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'CreditOffer',
    title: 'Credit Offer',
    description: 'Representa uma oferta de crédito disponível para um cliente',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', description: 'ID único da oferta', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'request_id', description: 'ID da consulta que originou esta oferta', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'institution', ref: '#/components/schemas/Institution'),
        new OA\Property(property: 'modality', ref: '#/components/schemas/CreditModality'),
        new OA\Property(property: 'amounts', ref: '#/components/schemas/AmountLimits'),
        new OA\Property(property: 'installments', ref: '#/components/schemas/InstallmentLimits'),
        new OA\Property(property: 'interest_rate', ref: '#/components/schemas/InterestRate'),
        new OA\Property(property: 'calculated_values', ref: '#/components/schemas/CalculatedValues'),
        new OA\Property(property: 'status', description: 'Status da oferta', type: 'string', enum: ['active', 'inactive', 'error'], example: 'active'),
        new OA\Property(property: 'status_label', description: 'Label do status', type: 'string', example: 'Ativa'),
        new OA\Property(property: 'error_message', description: 'Mensagem de erro (se houver)', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'created_at', description: 'Data de criação', type: 'string', format: 'date-time', example: '2023-12-01T10:30:00Z'),
        new OA\Property(property: 'updated_at', description: 'Data de atualização', type: 'string', format: 'date-time', example: '2023-12-01T10:30:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'CreditOffersResponse',
    title: 'Credit Offers Response',
    description: 'Lista de ofertas de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'cpf', type: 'string', example: '12345678901'),
        new OA\Property(property: 'total_offers', type: 'integer', example: 3),
        new OA\Property(property: 'limit', type: 'integer', example: 10),
        new OA\Property(
            property: 'offers',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CreditOfferBasic')
        ),
    ]
)]
class CreditOfferSchema {}
