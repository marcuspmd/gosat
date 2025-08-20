<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\StandardModalityEntity;

interface CreditModalityRepositoryInterface
{
    public function findById(string $id): ?CreditModalityEntity;

    public function findByStandardModality(StandardModalityEntity $standardModality): ?CreditModalityEntity;

    public function findActive(): array;

    public function findAll(): array;

    public function save(CreditModalityEntity $modality): void;

    public function delete(string $id): void;
}
