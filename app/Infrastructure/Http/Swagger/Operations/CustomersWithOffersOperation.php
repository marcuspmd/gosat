<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/credit/customers-with-offers',
    operationId: 'getCustomersWithOffers',
    summary: 'Listar clientes com ofertas',
    description: 'Retorna todos os clientes que possuem ofertas de crédito disponíveis.',
    tags: ['Credit Offers'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Lista de clientes com ofertas obtida com sucesso',
            content: new OA\JsonContent(
                properties: [
                    'status' => new OA\Property(property: 'status', type: 'string', example: 'success'),
                    'data' => new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(type: 'object')
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 500,
            description: 'Erro interno do servidor',
            content: new OA\JsonContent(ref: '#/components/schemas/InternalError')
        ),
    ]
)]
class CustomersWithOffersOperation {}
