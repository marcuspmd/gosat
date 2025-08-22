<?php

declare(strict_types=1);

use App\Domain\Credit\Services\CreditCalculatorService;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;

/*
 * @property CreditCalculatorService $calculator
 */

describe('CreditCalculatorService', function () {

    beforeEach(function () {
        $this->calculator = new CreditCalculatorService;
    });

    test('calculates monthly payment correctly for multiple installments', function () {
        $principal = new Money(10000); // R$ 10.000,00
        $rate = InterestRate::fromPercentage(2.5); // 2.5% a.m.
        $installments = new InstallmentCount(12);

        $monthlyPayment = $this->calculator->calculateMonthlyPayment($principal, $rate, $installments);

        // Verificar se o cálculo está na faixa esperada (Price table)
        expect($monthlyPayment->value)->toBeGreaterThan(900)
            ->and($monthlyPayment->value)->toBeLessThan(1200);
    });

    test('calculates total amount correctly', function () {
        $principal = new Money(10000);
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(10);

        $totalAmount = $this->calculator->calculateTotalAmount($principal, $rate, $installments);

        expect($totalAmount->value)->toBeGreaterThan(10000) // Deve ser maior que o principal
            ->and($totalAmount->value)->toBeLessThan(15000); // Mas não excessivo
    });

    test('calculates total interest correctly', function () {
        $principal = new Money(10000);
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(10);

        $totalInterest = $this->calculator->calculateTotalInterest($principal, $rate, $installments);

        expect($totalInterest->value)->toBeGreaterThan(0) // Deve haver juros
            ->and($totalInterest->value)->toBeLessThan(5000); // Mas não excessivo
    });

    test('handles single installment correctly', function () {
        $principal = new Money(10000);
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(1);

        $monthlyPayment = $this->calculator->calculateMonthlyPayment($principal, $rate, $installments);

        expect($monthlyPayment->value)->toBe(10000.0); // À vista = valor principal
    });

    test('calculates effective rate correctly', function () {
        $principal = new Money(10000);
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(12);

        $effectiveRate = $this->calculator->calculateEffectiveRate($principal, $rate, $installments);

        expect($effectiveRate)->toBeGreaterThan(0.10) // Pelo menos 10%
            ->and($effectiveRate)->toBeLessThan(0.30); // Mas não mais que 30%
    });

    test('generates amortization schedule correctly', function () {
        $principal = new Money(1000);
        $rate = InterestRate::fromPercentage(1.0); // 1% a.m.
        $installments = new InstallmentCount(3);

        $schedule = $this->calculator->calculateAmortizationSchedule($principal, $rate, $installments);

        expect($schedule)->toHaveCount(3);

        // Verificar primeira parcela
        expect($schedule[0]['month'])->toBe(1)
            ->and($schedule[0]['payment'])->toBeInstanceOf(Money::class)
            ->and($schedule[0]['principal'])->toBeInstanceOf(Money::class)
            ->and($schedule[0]['interest'])->toBeInstanceOf(Money::class)
            ->and($schedule[0]['balance'])->toBeInstanceOf(Money::class);

        // Última parcela deve ter saldo zero
        expect($schedule[2]['balance']->value)->toBe(0.0);
    });

    test('calculates maximum affordable amount correctly', function () {
        $monthlyIncome = new Money(5000);
        $debtRatio = 0.30; // 30% da renda
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(24);

        $maxAmount = $this->calculator->calculateMaxAffordableAmount(
            $monthlyIncome,
            $debtRatio,
            $rate,
            $installments
        );

        expect($maxAmount->value)->toBeGreaterThan(0)
            ->and($maxAmount->value)->toBeLessThan(50000); // Valor razoável
    });

    test('throws exception for invalid debt ratio', function () {
        $monthlyIncome = new Money(5000);
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(24);

        expect(fn () => $this->calculator->calculateMaxAffordableAmount(
            $monthlyIncome,
            0, // Invalid ratio
            $rate,
            $installments
        ))->toThrow(InvalidArgumentException::class);

        expect(fn () => $this->calculator->calculateMaxAffordableAmount(
            $monthlyIncome,
            1.5, // Invalid ratio > 1
            $rate,
            $installments
        ))->toThrow(InvalidArgumentException::class);
    });

    test('calculates effective rate correctly with zero principal', function () {
        $principal = new Money(0); // Zero principal to test line 74
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(12);

        $effectiveRate = $this->calculator->calculateEffectiveRate($principal, $rate, $installments);

        expect($effectiveRate)->toBe(0.0);
    });

    test('calculates max affordable amount for single installment', function () {
        $monthlyIncome = new Money(5000);
        $debtRatio = 0.30; // 30% da renda
        $rate = InterestRate::fromPercentage(2.0);
        $installments = new InstallmentCount(1); // Single installment to test line 132

        $maxAmount = $this->calculator->calculateMaxAffordableAmount(
            $monthlyIncome,
            $debtRatio,
            $rate,
            $installments
        );

        // For single installment, max amount should be 30% of monthly income
        expect($maxAmount->value)->toBe(1500.0);
    });

    describe('compareOffers', function () {

        test('returns empty array when input is empty', function () {
            $result = $this->calculator->compareOffers([]);

            expect($result)->toBe([]);
        });

        test('sorts offers by effective rate ascending', function () {
            $calculations = [
                ['name' => 'Offer A', 'effective_rate' => 0.25],
                ['name' => 'Offer B', 'effective_rate' => 0.15],
                ['name' => 'Offer C', 'effective_rate' => 0.30],
            ];

            $result = $this->calculator->compareOffers($calculations);

            expect($result)->toHaveCount(3)
                ->and($result[0]['name'])->toBe('Offer B') // Lowest rate first
                ->and($result[0]['effective_rate'])->toBe(0.15)
                ->and($result[1]['name'])->toBe('Offer A')
                ->and($result[1]['effective_rate'])->toBe(0.25)
                ->and($result[2]['name'])->toBe('Offer C') // Highest rate last
                ->and($result[2]['effective_rate'])->toBe(0.30);
        });

        test('handles single offer correctly', function () {
            $calculations = [
                ['name' => 'Single Offer', 'effective_rate' => 0.20],
            ];

            $result = $this->calculator->compareOffers($calculations);

            expect($result)->toHaveCount(1)
                ->and($result[0]['name'])->toBe('Single Offer')
                ->and($result[0]['effective_rate'])->toBe(0.20);
        });

        test('handles offers with same effective rate', function () {
            $calculations = [
                ['name' => 'Offer A', 'effective_rate' => 0.20],
                ['name' => 'Offer B', 'effective_rate' => 0.20],
                ['name' => 'Offer C', 'effective_rate' => 0.15],
            ];

            $result = $this->calculator->compareOffers($calculations);

            expect($result)->toHaveCount(3)
                ->and($result[0]['effective_rate'])->toBe(0.15) // Lowest first
                ->and($result[1]['effective_rate'])->toBe(0.20)
                ->and($result[2]['effective_rate'])->toBe(0.20);
        });
    });
});
