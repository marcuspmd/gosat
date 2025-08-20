<?php

declare(strict_types=1);

namespace App\Domain\Credit\Services;

use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;

final readonly class CreditCalculatorService
{
    public function calculateMonthlyPayment(
        Money $principalAmount,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): Money {
        if ($installments->value === 1) {
            // Pagamento à vista
            return $principalAmount;
        }

        $rate = $monthlyRate->monthlyRate;
        $periods = $installments->value;

        // Fórmula SAC (Sistema de Amortização Constante) - Price
        $factor = $monthlyRate->compound($periods);
        $monthlyPayment = $principalAmount->value * ($rate * $factor) / ($factor - 1);

        return new Money($monthlyPayment);
    }

    public function calculateTotalAmount(
        Money $principalAmount,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): Money {
        $monthlyPayment = $this->calculateMonthlyPayment(
            $principalAmount,
            $monthlyRate,
            $installments
        );

        return $monthlyPayment->multiply($installments->value);
    }

    public function calculateTotalInterest(
        Money $principalAmount,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): Money {
        $totalAmount = $this->calculateTotalAmount(
            $principalAmount,
            $monthlyRate,
            $installments
        );

        return $totalAmount->subtract($principalAmount);
    }

    public function calculateEffectiveRate(
        Money $principalAmount,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): float {
        $totalAmount = $this->calculateTotalAmount(
            $principalAmount,
            $monthlyRate,
            $installments
        );

        if ($principalAmount->amountInCents === 0) {
            return 0;
        }

        return ($totalAmount->amountInCents - $principalAmount->amountInCents) / $principalAmount->amountInCents;
    }

    public function calculateAmortizationSchedule(
        Money $principalAmount,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): array {
        $monthlyPayment = $this->calculateMonthlyPayment(
            $principalAmount,
            $monthlyRate,
            $installments
        );

        $schedule = [];
        $remainingBalance = $principalAmount->amountInCents;
        $rate = $monthlyRate->monthlyRate;

        for ($month = 1; $month <= $installments->value; $month++) {
            $interestPayment = (int) round($remainingBalance * $rate);
            $principalPayment = $monthlyPayment->amountInCents - $interestPayment;

            // Ajuste para última parcela
            if ($month === $installments->value) {
                $principalPayment = $remainingBalance;
                $interestPayment = $monthlyPayment->amountInCents - $principalPayment;
            }

            $remainingBalance -= $principalPayment;

            $schedule[] = [
                'month' => $month,
                'payment' => Money::fromCents($monthlyPayment->amountInCents),
                'principal' => Money::fromCents($principalPayment),
                'interest' => Money::fromCents($interestPayment),
                'balance' => Money::fromCents(max(0, $remainingBalance)),
            ];
        }

        return $schedule;
    }

    public function calculateMaxAffordableAmount(
        Money $monthlyIncome,
        float $debtToIncomeRatio,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): Money {
        if ($debtToIncomeRatio <= 0 || $debtToIncomeRatio > 1) {
            throw new InvalidArgumentException('Taxa de comprometimento deve estar entre 0 e 1');
        }

        $maxMonthlyPayment = $monthlyIncome->multiply($debtToIncomeRatio);

        if ($installments->value === 1) {
            return $maxMonthlyPayment;
        }

        $rate = $monthlyRate->monthlyRate;
        $periods = $installments->value;

        // Cálculo inverso da fórmula Price
        $factor = $monthlyRate->compound($periods);
        $maxPrincipal = $maxMonthlyPayment->value * ($factor - 1) / ($rate * $factor);

        return new Money($maxPrincipal);
    }

    public function compareOffers(array $calculations): array
    {
        if (empty($calculations)) {
            return [];
        }

        // Ordenar por taxa efetiva (menor = melhor)
        usort($calculations, function ($a, $b) {
            return $a['effective_rate'] <=> $b['effective_rate'];
        });

        return $calculations;
    }
}
