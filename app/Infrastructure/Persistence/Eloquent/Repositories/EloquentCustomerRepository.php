<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;

final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findById(string $id): ?CustomerEntity
    {
        return null;
    }

    public function findByCpf(CPF $cpf): ?CustomerEntity
    {
        return null;
    }

    public function save(CustomerEntity $customer): void {}

    public function exists(CPF $cpf): bool
    {
        return false;
    }
}
