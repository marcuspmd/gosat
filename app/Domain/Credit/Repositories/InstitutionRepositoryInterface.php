<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\InstitutionEntity;

interface InstitutionRepositoryInterface
{
    public function findById(string $id): ?InstitutionEntity;

    public function findBySlug(string $slug): ?InstitutionEntity;

    public function findActive(): array;

    public function findAll(): array;

    public function save(InstitutionEntity $institution): void;

    public function delete(string $id): void;
}
