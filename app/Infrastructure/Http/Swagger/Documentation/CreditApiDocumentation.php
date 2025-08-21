<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Swagger\Documentation;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Credit Consultation API',
    description: 'API para consulta de ofertas de crédito integrada com múltiplas instituições financeiras brasileiras. Esta API permite consultar ofertas de crédito, simular condições e acompanhar o status das solicitações.',
    contact: new OA\Contact(
        name: 'GoSat API Support',
        email: 'api-support@gosat.org'
    ),
    license: new OA\License(
        name: 'MIT',
        url: 'https://opensource.org/licenses/MIT'
    )
)]
#[OA\Server(
    url: 'http://localhost:8080/api',
    description: 'Development Server'
)]
#[OA\Server(
    url: 'https://api.gosat.org/api',
    description: 'Production Server'
)]
#[OA\Tag(
    name: 'Credit Offers',
    description: 'Operações relacionadas às ofertas de crédito'
)]
#[OA\Tag(
    name: 'System',
    description: 'Operações de sistema e monitoramento'
)]
class CreditApiDocumentation {}
