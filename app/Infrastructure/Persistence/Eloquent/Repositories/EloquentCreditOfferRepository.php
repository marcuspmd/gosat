<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\ValueObjects\CreditOfferStatus;
use App\Domain\Shared\ValueObjects\CPF;

final class EloquentCreditOfferRepository implements CreditOfferRepositoryInterface
{
    public function findById(string $id): ?CreditOfferEntity
    {
        // TODO: Implementar quando tiver a tabela credit_offers
        return null;
    }

    public function findByRequestId(string $requestId): array
    {
        // TODO: Implementar quando tiver a tabela credit_offers
        return [];
    }

    public function findByCpf(CPF $cpf): array
    {
        // Por enquanto, retornar array vazio até implementar a tabela
        return [];
    }

    public function findByStatus(CreditOfferStatus $status): array
    {
        // TODO: Implementar quando tiver a tabela credit_offers
        return [];
    }

    public function findByCpfAndRequestId(CPF $cpf, string $requestId): array
    {
        // TODO: Implementar quando tiver a tabela credit_offers
        return [];
    }

    public function findPendingOffers(): array
    {
        // TODO: Implementar quando tiver a tabela credit_offers
        return [];
    }

    public function findCompletedOffers(CPF $cpf): array
    {
        // TODO: Implementar quando tiver a tabela credit_offers
        return [];
    }

    public function save(CreditOfferEntity $offer): void
    {
        // TODO: Implementar quando tiver a tabela credit_offers
    }

    public function saveAll(array $offers): void
    {
        // TODO: Implementar quando tiver a tabela credit_offers
    }

    public function delete(string $id): void
    {
        // TODO: Implementar quando tiver a tabela credit_offers
    }

    public function markRequestAsFailed(string $requestId, string $errorMessage): void
    {
        // TODO: Implementar quando tiver a tabela credit_offers
    }
}
