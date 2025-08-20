<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\StandardModality;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;

final class EloquentCreditModalityRepository implements CreditModalityRepositoryInterface
{
    public function findById(string $id): ?CreditModalityEntity
    {
        // TODO: Implementar quando tiver a tabela credit_modalities
        return null;
    }

    public function findByStandardModality(StandardModality $standardModality): ?CreditModalityEntity
    {
        // TODO: Implementar quando tiver a tabela credit_modalities
        return null;
    }

    public function findActive(): array
    {
        // TODO: Implementar quando tiver a tabela credit_modalities
        return [];
    }

    public function findAll(): array
    {
        // TODO: Implementar quando tiver a tabela credit_modalities
        return [];
    }

    public function save(CreditModalityEntity $modality): void
    {
        // TODO: Implementar quando tiver a tabela credit_modalities
    }

    public function delete(string $id): void
    {
        // TODO: Implementar quando tiver a tabela credit_modalities
    }
}
