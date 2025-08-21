<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreditSimulationRequest',
    title: 'Credit Simulation Request',
    description: 'Dados para simular uma oferta de crédito',
    type: 'object',
    required: ['cpf', 'valor_desejado', 'quantidade_parcelas'],
    properties: [
        new OA\Property(
            property: 'cpf',
            description: 'CPF do cliente (apenas números, 11 dígitos)',
            type: 'string',
            pattern: '^\d{11}$',
            example: '12345678901'
        ),
        new OA\Property(
            property: 'valor_desejado',
            description: 'Valor desejado em centavos',
            type: 'integer',
            minimum: 100,
            example: 5000000
        ),
        new OA\Property(
            property: 'quantidade_parcelas',
            description: 'Número de parcelas desejadas',
            type: 'integer',
            minimum: 1,
            example: 24
        ),
        new OA\Property(
            property: 'modalidade',
            description: 'Modalidade específica (opcional)',
            type: 'string',
            nullable: true,
            example: 'Crédito Pessoal'
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
    description: 'Detalhes de uma oferta simulada',
    type: 'object',
    properties: [
        new OA\Property(property: 'instituicaoFinanceira', description: 'Nome da instituição', type: 'string', example: 'Banco Bradesco'),
        new OA\Property(property: 'modalidadeCredito', description: 'Modalidade de crédito', type: 'string', example: 'Crédito Pessoal'),
        new OA\Property(property: 'valorSolicitado', description: 'Valor solicitado em centavos', type: 'integer', example: 5000000),
        new OA\Property(property: 'valorAPagar', description: 'Valor total a pagar em centavos', type: 'integer', example: 6200000),
        new OA\Property(property: 'taxaJurosMensal', description: 'Taxa de juros mensal', type: 'number', format: 'float', example: 0.012),
        new OA\Property(property: 'taxaJurosAnual', description: 'Taxa de juros anual', type: 'number', format: 'float', example: 0.144),
        new OA\Property(property: 'qntParcelas', description: 'Número de parcelas', type: 'integer', example: 24),
        new OA\Property(property: 'parcelaMensal', description: 'Valor da parcela mensal em centavos', type: 'integer', example: 258333),
        new OA\Property(property: 'totalJuros', description: 'Total de juros em centavos', type: 'integer', example: 1200000),
        new OA\Property(property: 'taxaJuros', description: 'Taxa de juros (duplicado)', type: 'number', format: 'float', example: 0.012),
        new OA\Property(
            property: 'limites',
            description: 'Limites disponíveis para esta modalidade',
            type: 'object',
            properties: [
                new OA\Property(property: 'valorMinimo', type: 'integer', example: 100000),
                new OA\Property(property: 'valorMaximo', type: 'integer', example: 10000000),
                new OA\Property(property: 'parcelasMinima', type: 'integer', example: 1),
                new OA\Property(property: 'parcelasMaxima', type: 'integer', example: 60),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'CreditSimulationResponse',
    title: 'Credit Simulation Response',
    description: 'Resultado da simulação de crédito',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['success'], example: 'success'),
        new OA\Property(property: 'cpf', type: 'string', example: '12345678901'),
        new OA\Property(
            property: 'parametros',
            type: 'object',
            properties: [
                new OA\Property(property: 'valor_desejado', type: 'integer', example: 5000000),
                new OA\Property(property: 'quantidade_parcelas', type: 'integer', example: 24),
            ]
        ),
        new OA\Property(
            property: 'ofertas',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CreditSimulationOffer')
        ),
        new OA\Property(property: 'total_ofertas_encontradas', type: 'integer', example: 3),
    ]
)]
class CreditSimulationSchema {}
