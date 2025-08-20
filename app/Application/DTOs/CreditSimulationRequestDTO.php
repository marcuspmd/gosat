<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class CreditSimulationRequestDTO
{
    public function __construct(
        public string $cpf,
        public float $amount,
        public int $installments = 1
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['cpf'] ?? '',
            (float) ($data['amount'] ?? 0),
            (int) ($data['installments'] ?? 1)
        );
    }

    public function toArray(): array
    {
        return [
            'cpf' => $this->cpf,
            'amount' => $this->amount,
            'installments' => $this->installments,
        ];
    }
}
