<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class CreditSimulationResponseDTO
{
    public function __construct(
        public string $cpf,
        public float $requestedAmount,
        public int $requestedInstallments,
        public array $simulations,
        public string $status,
        public ?string $message = null
    ) {}

    public function toArray(): array
    {
        return [
            'cpf' => $this->cpf,
            'requested_amount' => $this->requestedAmount,
            'requested_installments' => $this->requestedInstallments,
            'simulations' => $this->simulations,
            'total_simulations' => count($this->simulations),
            'status' => $this->status,
            'message' => $this->message,
        ];
    }

    public function hasSimulations(): bool
    {
        return ! empty($this->simulations);
    }

    public function getBestSimulation(): ?array
    {
        if (empty($this->simulations)) {
            return null;
        }

        return $this->simulations[0];
    }
}
