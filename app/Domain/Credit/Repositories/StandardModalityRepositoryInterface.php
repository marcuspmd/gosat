<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\StandardModalityEntity;

interface StandardModalityRepositoryInterface
{
    public function findById(string $id): ?StandardModalityEntity;

    public function findByCode(string $code): ?StandardModalityEntity;

    public function findByKeyword(string $keyword): array;

    public function findActive(): array;

    public function findAll(): array;

    public function findByRiskLevel(string $riskLevel): array;

    public function save(StandardModalityEntity $modality): void;

    public function saveAll(array $modalities): void;

    public function delete(string $id): void;

    public function codeExists(string $code): bool;
}
