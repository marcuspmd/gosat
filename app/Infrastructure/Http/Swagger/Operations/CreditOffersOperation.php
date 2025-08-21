<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/credit/offers',
    operationId: 'getCreditOffers',
    summary: 'Listar ofertas de crédito',
    description: 'Retorna a lista das melhores ofertas de crédito disponíveis para um CPF específico.',
    tags: ['Credit Offers'],
    parameters: [
        new OA\Parameter(
            name: 'cpf',
            description: 'CPF do cliente (apenas números, 11 dígitos)',
            in: 'query',
            required: true,
            schema: new OA\Schema(type: 'string', pattern: '^\d{11}$', example: '12345678901')
        ),
        new OA\Parameter(
            name: 'limit',
            description: 'Número máximo de ofertas a retornar (1-100)',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10, example: 10)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Lista de ofertas obtida com sucesso',
            content: new OA\JsonContent(ref: '#/components/schemas/CreditOffersResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'Parâmetros inválidos',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
        ),
    ]
)]
class CreditOffersOperation {}
