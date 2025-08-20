<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Integration\Entities\ModalityMappingEntity;
use App\Domain\Integration\Repositories\ModalityMappingRepositoryInterface;

final class EloquentModalityMappingRepository implements ModalityMappingRepositoryInterface
{
    public function findById(string $id): ?ModalityMappingEntity
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return null;
    }

    public function findByInstitutionAndExternalCode(
        string $institutionId,
        string $externalCode
    ): ?ModalityMappingEntity {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return null;
    }

    public function findByInstitution(string $institutionId): array
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return [];
    }

    public function findByStandardModality(ModalityMappingEntity $standardModality): array
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return [];
    }

    public function findByInstitutionExternalId(string $institutionExternalId): array
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return [];
    }

    public function findStaleMapping(int $daysThreshold = 30): array
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return [];
    }

    public function findAll(): array
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return [];
    }

    public function save(ModalityMappingEntity $mapping): void
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
    }

    public function saveAll(array $mappings): void
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
    }

    public function delete(string $id): void
    {
        // TODO: Implementar quando tiver a tabela modality_mappings
    }

    public function getStandardModalityFromExternal(
        string $institutionId,
        string $externalCode
    ): ?ModalityMappingEntity {
        // TODO: Implementar quando tiver a tabela modality_mappings
        return null;
    }
}
