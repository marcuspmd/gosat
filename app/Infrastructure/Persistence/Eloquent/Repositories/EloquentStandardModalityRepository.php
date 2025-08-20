<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\StandardModalityEntity;
use App\Domain\Credit\Repositories\StandardModalityRepositoryInterface;

final class EloquentStandardModalityRepository implements StandardModalityRepositoryInterface
{
    public function findById(string $id): ?StandardModalityEntity
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return null;
    }

    public function findByCode(string $code): ?StandardModalityEntity
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return null;
    }

    public function findByKeyword(string $keyword): array
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return [];
    }

    public function findActive(): array
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return [];
    }

    public function findAll(): array
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return [];
    }

    public function findByRiskLevel(string $riskLevel): array
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return [];
    }

    public function save(StandardModalityEntity $modality): void
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
    }

    public function saveAll(array $modalities): void
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
    }

    public function delete(string $id): void
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
    }

    public function codeExists(string $code): bool
    {
        // TODO: Implementar quando tiver a tabela standard_modalities
        return false;
    }
}
