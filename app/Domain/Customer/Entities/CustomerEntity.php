<?php

declare(strict_types=1);

namespace App\Domain\Customer\Entities;

use App\Domain\Shared\ValueObjects\CPF;

final class CustomerEntity
{
    public function __construct(
        public string $id,
        public CPF $cpf
    ) {}
}
