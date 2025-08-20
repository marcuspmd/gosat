<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use App\Domain\Credit\ValueObjects\CreditOfferStatus;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use DateTimeImmutable;
use InvalidArgumentException;

final class CreditOfferEntity
{
    public string $requestId {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Request ID não pode estar vazio');
            }
            $this->requestId = trim($value);
        }
    }

    public Money $monthlyPayment {
        get {
            if ($this->status !== CreditOfferStatus::COMPLETED) {
                return new Money(0);
            }

            $factor = $this->monthlyInterestRate->compound($this->installments->value);
            $payment = $this->approvedAmount->value * ($this->monthlyInterestRate->monthlyRate * $factor) / ($factor - 1);

            return new Money($payment);
        }
    }

    public Money $totalAmount {
        get {
            if ($this->status !== CreditOfferStatus::COMPLETED) {
                return new Money(0);
            }

            return $this->monthlyPayment->multiply($this->installments->value);
        }
    }

    public Money $totalInterest {
        get {
            if ($this->status !== CreditOfferStatus::COMPLETED) {
                return new Money(0);
            }

            return $this->totalAmount->subtract($this->approvedAmount);
        }
    }

    public function __construct(
        public string $id,
        string $requestId,
        public CPF $cpf,
        public InstitutionEntity $institution,
        public CreditModalityEntity $modality,
        public Money $minAmount,
        public Money $maxAmount,
        public Money $approvedAmount,
        public InterestRate $monthlyInterestRate,
        public InstallmentCount $minInstallments,
        public InstallmentCount $maxInstallments,
        public InstallmentCount $installments,
        public CreditOfferStatus $status = CreditOfferStatus::PENDING,
        public ?string $errorMessage = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->requestId = $requestId;
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;

    }

    private function copyWith(
        ?CreditOfferStatus $status = null,
        ?string $errorMessage = null
    ): self {
        return new self(
            $this->id,
            $this->requestId,
            $this->cpf,
            $this->institution,
            $this->modality,
            $this->minAmount,
            $this->maxAmount,
            $this->approvedAmount,
            $this->monthlyInterestRate,
            $this->minInstallments,
            $this->maxInstallments,
            $this->installments,
            $status ?? $this->status,
            $errorMessage ?? $this->errorMessage,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function markAsCompleted(): self
    {
        return $this->copyWith(status: CreditOfferStatus::COMPLETED, errorMessage: null);
    }

    public function markAsFailed(string $errorMessage): self
    {
        return $this->copyWith(status: CreditOfferStatus::FAILED, errorMessage: $errorMessage);
    }

    public function markAsProcessing(): self
    {
        return $this->copyWith(status: CreditOfferStatus::PROCESSING, errorMessage: null);
    }

    public function calculateEffectiveRate(): float
    {
        if ($this->approvedAmount->value === 0) {
            return 0;
        }

        return ($this->totalAmount->value - $this->approvedAmount->value) / $this->approvedAmount->value;
    }

    public function isMoreAttractiveThan(CreditOfferEntity $other): bool
    {
        // Compara taxa de juros efetiva total
        return $this->calculateEffectiveRate() < $other->calculateEffectiveRate();
    }

    public function equals(CreditOfferEntity $other): bool
    {
        return $this->id === $other->id;
    }
}
