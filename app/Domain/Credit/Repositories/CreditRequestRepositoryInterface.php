<?php

declare(strict_types=1);

namespace App\Domain\Credit\Repositories;

use App\Domain\Credit\Entities\CreditRequestEntity;
use App\Domain\Customer\Entities\CustomerEntity;

interface CreditRequestRepositoryInterface
{
    public function findById(string $id): ?CreditRequestEntity;

    /**
     * @return CreditRequestEntity[]
     */
    public function findByCustomer(CustomerEntity $customer): array;

    /**
     * @return CreditRequestEntity[]
     */
    public function findValidRequests(): array;

    /**
     * @return CreditRequestEntity[]
     */
    public function findExpiredRequests(): array;

    public function save(CreditRequestEntity $request): void;

    public function delete(string $id): void;
}