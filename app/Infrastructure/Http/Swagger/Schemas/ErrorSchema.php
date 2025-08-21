<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationError',
    title: 'Validation Error Response',
    description: 'Resposta para erros de validação',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'validation_error'),
        new OA\Property(property: 'message', type: 'string', example: 'CPF inválido'),
    ]
)]
#[OA\Schema(
    schema: 'InternalError',
    title: 'Internal Error Response',
    description: 'Resposta para erros internos do servidor',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'internal_error'),
        new OA\Property(property: 'message', type: 'string', example: 'Erro interno do servidor'),
    ]
)]
#[OA\Schema(
    schema: 'NotFoundError',
    title: 'Not Found Error Response',
    description: 'Resposta para recursos não encontrados',
    type: 'object',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'not_found'),
        new OA\Property(property: 'message', type: 'string', example: 'Recurso não encontrado'),
    ]
)]
class ErrorSchema {}
