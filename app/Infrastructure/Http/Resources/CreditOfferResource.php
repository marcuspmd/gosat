<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Domain\Credit\Entities\CreditOfferEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditOfferResource extends JsonResource
{
    public function __construct(CreditOfferEntity $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'request_id' => $this->resource->requestId,
            'institution' => [
                'id' => $this->resource->institution->id,
            ],
            'modality' => [
                'id' => $this->resource->modality->id,
                'name' => $this->resource->modality->name,
                'standard_code' => $this->resource->modality->standardCode->value,
            ],
            'amounts' => [
                'min' => [
                    'cents' => $this->resource->minAmount?->amountInCents ?? 0,
                    'formatted' => $this->resource->minAmount?->formatted ?? 'R$ 0,00',
                ],
                'max' => [
                    'cents' => $this->resource->maxAmount?->amountInCents ?? 0,
                    'formatted' => $this->resource->maxAmount?->formatted ?? 'R$ 0,00',
                ],
            ],
            'installments' => [
                'min' => $this->resource->minInstallments?->value ?? 1,
                'max' => $this->resource->maxInstallments?->value ?? 1,
            ],
            'interest_rate' => [
                'monthly' => $this->resource->monthlyInterestRate?->monthlyRate ?? 0,
                'annual' => $this->resource->monthlyInterestRate?->annualRate ?? 0,
                'formatted_monthly' => $this->resource->monthlyInterestRate?->formattedMonthly ?? '0,0000% a.m.',
                'formatted_annual' => $this->resource->monthlyInterestRate?->formattedAnnual ?? '0,00% a.a.',
            ],
            'calculated_values' => [
                'monthly_payment' => [
                    'cents' => $this->resource->monthlyPayment?->amountInCents ?? 0,
                    'formatted' => $this->resource->monthlyPayment?->formatted ?? 'R$ 0,00',
                ],
                'total_amount' => [
                    'cents' => $this->resource->totalAmount?->amountInCents ?? 0,
                    'formatted' => $this->resource->totalAmount?->formatted ?? 'R$ 0,00',
                ],
                'total_interest' => [
                    'cents' => $this->resource->totalInterest?->amountInCents ?? 0,
                    'formatted' => $this->resource->totalInterest?->formatted ?? 'R$ 0,00',
                ],
                'effective_rate' => method_exists($this->resource, 'calculateEffectiveRate') ? $this->resource->calculateEffectiveRate() : 0,
            ],
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->label(),
            'error_message' => $this->resource->errorMessage,
            'created_at' => $this->resource->createdAt?->toISOString(),
            'updated_at' => $this->resource->updatedAt?->toISOString(),
        ];
    }
}
