<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Operations;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/v1/credit/simulate',
    operationId: 'simulateCreditOffer',
    summary: 'Simular oferta de crédito',
    description: 'Simula os valores de uma oferta de crédito específica com base no valor solicitado e número de parcelas.',
    tags: ['Credit Offers'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CreditSimulationRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Simulação realizada com sucesso',
            content: new OA\JsonContent(ref: '#/components/schemas/CreditSimulationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'Dados de entrada inválidos',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
        ),
    ]
)]
class CreditSimulateOperation {}
