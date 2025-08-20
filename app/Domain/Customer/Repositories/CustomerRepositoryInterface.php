<?php

declare(strict_types=1);

namespace App\Domain\Customer\Repositories;

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\CPF;

interface CustomerRepositoryInterface
{
    public function findById(string $id): ?CustomerEntity;

    public function findByCpf(CPF $cpf): ?CustomerEntity;

    public function save(CustomerEntity $customer): void;

    public function exists(CPF $cpf): bool;
}
