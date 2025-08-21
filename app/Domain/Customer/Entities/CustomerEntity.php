<?php

declare(strict_types=1);

namespace App\Domain\Customer\Entities;

use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use DateTimeImmutable;

final class CustomerEntity
{
    public function __construct(
        public string $id,
        public CPF $cpf,
        public bool $isActive = true,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;
    }

    public static function fromModel(CustomerModel $model): self
    {
        return new self(
            id: $model->id,
            cpf: new CPF($model->cpf),
            isActive: $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public function toModel(): CustomerModel
    {
        $model = new CustomerModel;
        $model->id = $this->id;
        $model->cpf = $this->cpf->value;
        $model->is_active = $this->isActive;
        $model->created_at = $this->createdAt;
        $model->updated_at = $this->updatedAt;

        return $model;
    }

    public function updateModel(CustomerModel $model): void
    {
        $model->cpf = $this->cpf->value;
        $model->is_active = $this->isActive;
        $model->updated_at = $this->updatedAt;
    }

    public function equals(CustomerEntity $other): bool
    {
        return $this->id === $other->id;
    }
}
