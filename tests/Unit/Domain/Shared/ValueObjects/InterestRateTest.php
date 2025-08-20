<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\InterestRate;

describe('InterestRate', function () {
    it('can be created with valid monthly rate', function () {
        $rate = new InterestRate(0.02);

        expect($rate->monthlyRate)->toBe(0.02);
    });

    it('throws exception for negative monthly rate', function () {
        expect(fn () => new InterestRate(-0.01))
            ->toThrow(\InvalidArgumentException::class, 'Taxa de juros não pode ser negativa');
    });

    it('accepts zero monthly rate', function () {
        $rate = new InterestRate(0.0);

        expect($rate->monthlyRate)->toBe(0.0);
    });

    it('calculates annual rate correctly', function () {
        $rate = new InterestRate(0.01); // 1% monthly

        $expectedAnnual = pow(1.01, 12) - 1; // ~12.68%
        expect($rate->annualRate)->toBeFloat()
            ->and(abs($rate->annualRate - $expectedAnnual))->toBeLessThan(0.000001);
    });

    it('formats monthly rate correctly', function () {
        $rate = new InterestRate(0.0275);

        expect($rate->formattedMonthly)->toBe('2,7500% a.m.');
    });

    it('formats annual rate correctly', function () {
        $rate = new InterestRate(0.02);

        expect($rate->formattedAnnual)->toMatch('/\d{2},\d{2}% a\.a\./');
    });

    it('creates from annual rate correctly', function () {
        $rate = InterestRate::fromAnnual(0.12); // 12% annual

        $expectedMonthly = pow(1.12, 1 / 12) - 1;
        expect(abs($rate->monthlyRate - $expectedMonthly))->toBeLessThan(0.000001);
    });

    it('throws exception for negative annual rate', function () {
        expect(fn () => InterestRate::fromAnnual(-0.05))
            ->toThrow(\InvalidArgumentException::class, 'Taxa de juros anual não pode ser negativa');
    });

    it('creates from percentage correctly', function () {
        $rate = InterestRate::fromPercentage(2.5);

        expect($rate->monthlyRate)->toBe(0.025);
    });

    it('handles zero percentage', function () {
        $rate = InterestRate::fromPercentage(0);

        expect($rate->monthlyRate)->toBe(0.0);
    });

    it('calculates compound interest correctly', function () {
        $rate = new InterestRate(0.02);

        expect($rate->compound(12))->toBeFloat()
            ->and(abs($rate->compound(12) - pow(1.02, 12)))->toBeLessThan(0.000001);
    });

    it('handles zero periods in compound', function () {
        $rate = new InterestRate(0.02);

        expect($rate->compound(0))->toBe(1.0);
    });

    it('throws exception for negative periods in compound', function () {
        $rate = new InterestRate(0.02);

        expect(fn () => $rate->compound(-1))
            ->toThrow(\InvalidArgumentException::class, 'Número de períodos não pode ser negativo');
    });

    it('compares equality correctly with tolerance', function () {
        $rate1 = new InterestRate(0.02);
        $rate2 = new InterestRate(0.02);
        $rate3 = new InterestRate(0.020000001); // Within tolerance
        $rate4 = new InterestRate(0.02001); // Outside tolerance

        expect($rate1->equals($rate2))->toBeTrue()
            ->and($rate1->equals($rate3))->toBeTrue()
            ->and($rate1->equals($rate4))->toBeFalse();
    });

    it('compares greater than correctly', function () {
        $rate1 = new InterestRate(0.03);
        $rate2 = new InterestRate(0.02);
        $rate3 = new InterestRate(0.04);

        expect($rate1->isGreaterThan($rate2))->toBeTrue()
            ->and($rate1->isGreaterThan($rate3))->toBeFalse()
            ->and($rate1->isGreaterThan($rate1))->toBeFalse();
    });

    it('compares less than correctly', function () {
        $rate1 = new InterestRate(0.02);
        $rate2 = new InterestRate(0.03);
        $rate3 = new InterestRate(0.01);

        expect($rate1->isLessThan($rate2))->toBeTrue()
            ->and($rate1->isLessThan($rate3))->toBeFalse()
            ->and($rate1->isLessThan($rate1))->toBeFalse();
    });

    it('handles high monthly rates', function () {
        $rate = new InterestRate(0.10); // 10% monthly

        expect($rate->monthlyRate)->toBe(0.10)
            ->and($rate->formattedMonthly)->toBe('10,0000% a.m.')
            ->and($rate->annualRate)->toBeGreaterThan(2.0); // Should be > 200%
    });

    it('handles very small rates', function () {
        $rate = new InterestRate(0.0001); // 0.01% monthly

        expect($rate->monthlyRate)->toBe(0.0001)
            ->and($rate->formattedMonthly)->toBe('0,0100% a.m.');
    });

    it('maintains precision in calculations', function () {
        $rate = InterestRate::fromAnnual(0.1268); // Specific annual rate
        $backToAnnual = $rate->annualRate;

        expect(abs($backToAnnual - 0.1268))->toBeLessThan(0.0001);
    });

    it('formats rates with correct decimal places', function () {
        $rate1 = new InterestRate(0.025);
        $rate2 = new InterestRate(0.025555);

        expect($rate1->formattedMonthly)->toBe('2,5000% a.m.')
            ->and($rate2->formattedMonthly)->toBe('2,5555% a.m.')
            ->and($rate1->formattedAnnual)->toMatch('/\d{2},\d{2}% a\.a\./')
            ->and($rate2->formattedAnnual)->toMatch('/\d{2},\d{2}% a\.a\./');
    });

    it('handles edge cases in compound calculation', function () {
        $rate = new InterestRate(0.0);

        expect($rate->compound(12))->toBe(1.0);

        $highRate = new InterestRate(1.0); // 100% monthly
        expect($highRate->compound(2))->toBe(4.0);
    });

    it('works with common market rates', function () {
        $marketRates = [
            0.005,  // 0.5% monthly
            0.01,   // 1% monthly
            0.015,  // 1.5% monthly
            0.02,   // 2% monthly
            0.025,  // 2.5% monthly
            0.03,   // 3% monthly
        ];

        foreach ($marketRates as $monthly) {
            $rate = new InterestRate($monthly);

            expect($rate->monthlyRate)->toBe($monthly);
            expect($rate->annualRate)->toBeGreaterThan($monthly * 12); // Due to compounding
            expect($rate->formattedMonthly)->toContain('% a.m.');
            expect($rate->formattedAnnual)->toContain('% a.a.');
        }
    });

    it('validates annual to monthly conversion', function () {
        $annualRates = [0.06, 0.12, 0.24]; // 6%, 12%, 24% annually

        foreach ($annualRates as $annual) {
            $rate = InterestRate::fromAnnual($annual);
            $convertedBack = $rate->annualRate;

            expect(abs($convertedBack - $annual))->toBeLessThan(0.000001);
        }
    });
});
