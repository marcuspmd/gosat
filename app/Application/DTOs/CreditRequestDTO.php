<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class CreditRequestDTO
{
    public function __construct(
        public string $requestId,
        public string $cpf,
        public string $status,
        public string $message
    ) {}

    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'cpf' => $this->cpf,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
