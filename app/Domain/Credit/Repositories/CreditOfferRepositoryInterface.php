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

    public function getAllCustomersWithOffers(): array;

    public function getOffersForCpf(CPF $cpf, int $limit = 10): array;

    public function getSimulationOffers(CPF $cpf, int $amountCents, int $installments, ?string $modality = null): array;

    public function countOffersByRequestId(string $requestId): int;

    public function findPendingJobByRequestId(string $requestId): ?object;

    public function findFailedJobByRequestId(string $requestId): ?object;
}
