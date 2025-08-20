<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Shared\ValueObjects\CPF;

interface CreditOfferRepositoryInterface
{
    public function findById(string $id): ?CreditOfferEntity;

    /**
     * @return CreditOfferEntity[]
     */
    public function findByRequestId(string $requestId): array;

    public function findByCpf(CPF $cpf): array;

    public function findByCpfAndRequestId(CPF $cpf, string $requestId): array;

    public function findCompletedOffers(CPF $cpf): array;

    public function save(CreditOfferEntity $offer): void;

    public function saveAll(array $offers): void;

    public function delete(string $id): void;

    public function markRequestAsFailed(string $requestId, string $errorMessage): void;
}
