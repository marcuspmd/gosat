<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/v1/credit/search',
    operationId: 'searchCreditOffers',
    summary: 'Iniciar nova consulta de crédito',
    description: 'Inicia uma consulta assíncrona de ofertas de crédito em múltiplas instituições financeiras para o CPF fornecido.',
    tags: ['Credit Offers'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CreditSearchRequest')
    ),
    responses: [
        new OA\Response(
            response: 202,
            description: 'Consulta iniciada com sucesso',
            content: new OA\JsonContent(ref: '#/components/schemas/CreditSearchResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'Dados de entrada inválidos',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
        ),
        new OA\Response(
            response: 500,
            description: 'Erro interno do servidor',
            content: new OA\JsonContent(ref: '#/components/schemas/InternalError')
        ),
    ]
)]
class CreditSearchOperation
{
}