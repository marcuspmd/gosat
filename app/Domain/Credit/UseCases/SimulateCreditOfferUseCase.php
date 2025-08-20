<?php

declare(strict_types=1);

namespace App\Domain\Credit\UseCases;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Services\CreditCalculatorService;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;

final readonly class SimulateCreditOfferUseCase
{
    public function __construct(
        private CreditCalculatorService $calculatorService
    ) {}

    public function execute(CreditOfferEntity $offer, Money $requestedAmount, InstallmentCount $desiredInstallments): array
    {
        $this->validateInputs($offer, $requestedAmount, $desiredInstallments);

        $monthlyPayment = $this->calculatorService->calculateMonthlyPayment(
            $requestedAmount,
            $offer->monthlyInterestRate,
            $desiredInstallments
        );

        $totalAmount = $this->calculatorService->calculateTotalAmount(
            $requestedAmount,
            $offer->monthlyInterestRate,
            $desiredInstallments
        );

        $totalInterest = $totalAmount->subtract($requestedAmount);

        return [
            'institution' => [
                'id' => $offer->institution->id,
                'name' => $offer->institution->name,
                'website' => $offer->institution->website,
            ],
            'modality' => [
                'name' => $offer->modality->name,
                'standard_code' => $offer->modality->standardCode->value,
                'description' => $offer->modality->description,
                'risk_level' => $offer->modality->standardCode->riskLevel(),
            ],
            'simulation' => [
                'requested_amount' => $requestedAmount->formatted,
                'requested_amount_cents' => $requestedAmount->amountInCents,
                'installments' => $desiredInstallments->value,
                'installment_description' => $desiredInstallments->periodDescription,
                'monthly_payment' => $monthlyPayment->formatted,
                'monthly_payment_cents' => $monthlyPayment->amountInCents,
                'total_amount' => $totalAmount->formatted,
                'total_amount_cents' => $totalAmount->amountInCents,
                'total_interest' => $totalInterest->formatted,
                'total_interest_cents' => $totalInterest->amountInCents,
                'monthly_interest_rate' => $offer->monthlyInterestRate->formattedMonthly,
                'annual_interest_rate' => $offer->monthlyInterestRate->formattedAnnual,
                'effective_rate' => $this->calculateEffectiveRate($requestedAmount, $totalAmount),
            ],
            'limits' => [
                'min_amount' => $offer->minAmount->formatted,
                'max_amount' => $offer->maxAmount->formatted,
                'min_installments' => $offer->minInstallments->value,
                'max_installments' => $offer->maxInstallments->value,
            ],
        ];
    }

    public function executeMultiple(array $offers, Money $requestedAmount, InstallmentCount $desiredInstallments): array
    {
        if (empty($offers)) {
            throw new InvalidArgumentException('Lista de ofertas não pode estar vazia');
        }

        $simulations = [];

        foreach ($offers as $offer) {
            if (! $offer instanceof CreditOfferEntity) {
                continue;
            }

            try {
                // Verificar se a oferta suporta os valores solicitados
                if ($requestedAmount->isLessThan($offer->minAmount) ||
                    $requestedAmount->isGreaterThan($offer->maxAmount)) {
                    continue;
                }

                if ($desiredInstallments->isLessThan($offer->minInstallments) ||
                    $desiredInstallments->isGreaterThan($offer->maxInstallments)) {
                    continue;
                }

                $simulation = $this->execute($offer, $requestedAmount, $desiredInstallments);
                $simulations[] = $simulation;

            } catch (InvalidArgumentException $e) {
                // Pular ofertas inválidas
                continue;
            }
        }

        // Ordenar por menor taxa efetiva
        usort($simulations, function ($a, $b) {
            return $a['simulation']['effective_rate'] <=> $b['simulation']['effective_rate'];
        });

        return $simulations;
    }

    private function validateInputs(CreditOfferEntity $offer, Money $requestedAmount, InstallmentCount $desiredInstallments): void
    {
        if ($requestedAmount->isLessThan($offer->minAmount)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Valor solicitado (%s) é menor que o mínimo permitido (%s)',
                    $requestedAmount->formatted,
                    $offer->minAmount->formatted
                )
            );
        }

        if ($requestedAmount->isGreaterThan($offer->maxAmount)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Valor solicitado (%s) é maior que o máximo permitido (%s)',
                    $requestedAmount->formatted,
                    $offer->maxAmount->formatted
                )
            );
        }

        if ($desiredInstallments->isLessThan($offer->minInstallments)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Número de parcelas (%d) é menor que o mínimo permitido (%d)',
                    $desiredInstallments->value,
                    $offer->minInstallments->value
                )
            );
        }

        if ($desiredInstallments->isGreaterThan($offer->maxInstallments)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Número de parcelas (%d) é maior que o máximo permitido (%d)',
                    $desiredInstallments->value,
                    $offer->maxInstallments->value
                )
            );
        }
    }

    private function calculateEffectiveRate(Money $requestedAmount, Money $totalAmount): float
    {
        if ($requestedAmount->amountInCents === 0) {
            return 0;
        }

        return ($totalAmount->amountInCents - $requestedAmount->amountInCents) / $requestedAmount->amountInCents;
    }
}
