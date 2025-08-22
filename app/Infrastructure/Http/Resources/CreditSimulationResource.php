<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\DTOs\CreditSimulationResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditSimulationResource extends JsonResource
{
    public function __construct(CreditSimulationResponseDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'cpf' => $this->resource->cpf,
            'requested_amount' => [
                'value' => $this->resource->requestedAmount,
                'formatted' => 'R$ ' . number_format($this->resource->requestedAmount, 2, ',', '.'),
            ],
            'requested_installments' => $this->resource->requestedInstallments,
            'simulations' => $this->resource->simulations,
            'total_simulations' => $this->resource->totalSimulations ?? count($this->resource->simulations),
            'status' => $this->resource->status,
            'message' => $this->resource->message,
            'best_offer' => $this->resource->getBestSimulation(),
            'generated_at' => (new \DateTimeImmutable)->format('c'),
            'links' => [
                'search' => '/api/v1/credit',
                'index' => '/api/v1/credit',
            ],
        ];
    }
}
