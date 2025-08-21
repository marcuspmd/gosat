<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Shared\ValueObjects\CPF;

interface CreditOfferRepositoryInterface
{
    public function findById(string $id): ?array;

    public function findByCpf(CPF $cpf): array;

    public function findByRequestId(string $requestId): array;

    public function save(CreditOfferEntity $offer): void;

    public function saveAll(array $offers): void;

    public function delete(string $id): void;

    public function softDeleteByCpf(CPF $cpf): void;
}
