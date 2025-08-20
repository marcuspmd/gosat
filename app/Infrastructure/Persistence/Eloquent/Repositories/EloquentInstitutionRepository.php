<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;

final class EloquentInstitutionRepository implements InstitutionRepositoryInterface
{
    public function findById(string $id): ?InstitutionEntity
    {
        // TODO: Implementar quando tiver a tabela institutions
        return null;
    }

    public function findBySlug(string $slug): ?InstitutionEntity
    {
        // TODO: Implementar quando tiver a tabela institutions
        return null;
    }

    public function findActive(): array
    {
        // TODO: Implementar quando tiver a tabela institutions
        return [];
    }

    public function findAll(): array
    {
        // TODO: Implementar quando tiver a tabela institutions
        return [];
    }

    public function save(InstitutionEntity $institution): void
    {
        // TODO: Implementar quando tiver a tabela institutions
    }

    public function delete(string $id): void
    {
        // TODO: Implementar quando tiver a tabela institutions
    }
}
