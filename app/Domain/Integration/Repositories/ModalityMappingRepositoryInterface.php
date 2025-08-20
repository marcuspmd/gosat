<?php

declare(strict_types=1);

namespace App\Domain\Integration\Repositories;

use App\Domain\Integration\Entities\ModalityMappingEntity;

interface ModalityMappingRepositoryInterface
{
    public function findById(string $id): ?ModalityMappingEntity;

    public function findByInstitutionAndExternalCode(string $institutionId, string $externalCode): ?ModalityMappingEntity;

    public function save(ModalityMappingEntity $mapping): void;

    public function delete(string $id): void;
}
