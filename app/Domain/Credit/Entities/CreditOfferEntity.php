<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use DateTimeImmutable;

final class CreditOfferEntity
{
    public function __construct(
        public string $id,
        public CustomerEntity $customer,
        public InstitutionEntity $institution,
        public CreditModalityEntity $modality,
        public Money $minAmount,
        public Money $maxAmount,
        public InterestRate $monthlyInterestRate,
        public InstallmentCount $minInstallments,
        public InstallmentCount $maxInstallments,
        public ?string $requestId = null,
        public ?string $errorMessage = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;

    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'modality_id' => $this->modality->id,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'monthly_interest_rate' => $this->monthlyInterestRate,
            'min_installments' => $this->minInstallments,
            'max_installments' => $this->maxInstallments,
            'request_id' => $this->requestId,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    public function equals(CreditOfferEntity $other): bool
    {
        return $this->id === $other->id;
    }

    public static function fromModel(CreditOfferModel $model): self
    {
        return new self(
            id: $model->id,
            customer: CustomerEntity::fromModel($model->customer),
            institution: InstitutionEntity::fromModel($model->institution),
            modality: CreditModalityEntity::fromModel($model->modality),
            minAmount: Money::fromCents($model->min_amount_cents),
            maxAmount: Money::fromCents($model->max_amount_cents),
            monthlyInterestRate: new InterestRate($model->monthly_interest_rate ?? 0.0),
            minInstallments: new InstallmentCount($model->min_installments),
            maxInstallments: new InstallmentCount($model->max_installments),
            requestId: $model->request_id,
            errorMessage: $model->error_message,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public function toModel(): CreditOfferModel
    {
        $model = new CreditOfferModel;
        $model->id = $this->id;
        $model->customer_id = $this->customer->id;
        $model->institution_id = $this->institution->id;
        $model->modality_id = $this->modality->id;
        $model->min_amount_cents = $this->minAmount->amountInCents;
        $model->max_amount_cents = $this->maxAmount->amountInCents;
        $model->monthly_interest_rate = $this->monthlyInterestRate->monthlyRate;
        $model->min_installments = $this->minInstallments->value;
        $model->max_installments = $this->maxInstallments->value;
        $model->request_id = $this->requestId;
        $model->error_message = $this->errorMessage;
        $model->created_at = $this->createdAt;
        $model->updated_at = $this->updatedAt;

        return $model;
    }

    public function updateModel(CreditOfferModel $model): void
    {
        $model->customer_id = $this->customer->id;
        $model->institution_id = $this->institution->id;
        $model->modality_id = $this->modality->id;
        $model->min_amount_cents = $this->minAmount->amountInCents;
        $model->max_amount_cents = $this->maxAmount->amountInCents;
        $model->monthly_interest_rate = $this->monthlyInterestRate->monthlyRate;
        $model->min_installments = $this->minInstallments->value;
        $model->max_installments = $this->maxInstallments->value;
        $model->request_id = $this->requestId;
        $model->error_message = $this->errorMessage;
        $model->updated_at = $this->updatedAt;
    }
}
