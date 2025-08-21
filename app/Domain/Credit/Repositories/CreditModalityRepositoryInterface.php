<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\CreditModalityEntity;

interface CreditModalityRepositoryInterface
{
    public function findById(string $id): ?CreditModalityEntity;

    public function findBySlug(string $slug): ?CreditModalityEntity;

    public function save(CreditModalityEntity $modality): void;

    public function delete(string $id): void;
}
