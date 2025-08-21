<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Models\CreditRequestModel;
use DateTimeImmutable;
use DateTime;

final class CreditRequestEntity
{
    public function __construct(
        public string $id,
        public CustomerEntity $customer,
        public Money $amount,
        public InstallmentCount $installments,
        public ?DateTime $validAt = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->createdAt ??= new DateTimeImmutable();
        $this->updatedAt ??= new DateTimeImmutable();
    }

    public static function fromModel(CreditRequestModel $model): self
    {
        return new self(
            id: $model->id,
            customer: CustomerEntity::fromModel($model->customer),
            amount: Money::fromCents($model->amount_cents),
            installments: new InstallmentCount($model->installments),
            validAt: $model->valid_at?->toDateTime(),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public function toModel(): CreditRequestModel
    {
        $model = new CreditRequestModel();
        $model->id = $this->id;
        $model->customer_id = $this->customer->id;
        $model->amount_cents = $this->amount->amountInCents;
        $model->installments = $this->installments->value;
        $model->valid_at = $this->validAt;
        $model->created_at = $this->createdAt;
        $model->updated_at = $this->updatedAt;
        
        return $model;
    }

    public function updateModel(CreditRequestModel $model): void
    {
        $model->customer_id = $this->customer->id;
        $model->amount_cents = $this->amount->amountInCents;
        $model->installments = $this->installments->value;
        $model->valid_at = $this->validAt;
        $model->updated_at = $this->updatedAt;
    }

    public function isExpired(): bool
    {
        if ($this->validAt === null) {
            return false;
        }

        return $this->validAt < new DateTime();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public function equals(CreditRequestEntity $other): bool
    {
        return $this->id === $other->id;
    }
}