<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\InstallmentCount;

describe('InstallmentCount', function () {
    it('can be created with valid value', function () {
        $installmentCount = new InstallmentCount(12);

        expect($installmentCount->value)->toBe(12);
    });

    it('throws exception for zero installments', function () {
        expect(fn () => new InstallmentCount(0))
            ->toThrow(\InvalidArgumentException::class, 'Número de parcelas deve ser maior que zero');
    });

    it('throws exception for negative installments', function () {
        expect(fn () => new InstallmentCount(-5))
            ->toThrow(\InvalidArgumentException::class, 'Número de parcelas deve ser maior que zero');
    });

    it('accepts exactly 1 installment', function () {
        $installmentCount = new InstallmentCount(1);

        expect($installmentCount->value)->toBe(1);
    });

    it('formats installments correctly', function () {
        $installmentCount = new InstallmentCount(24);

        expect($installmentCount->formatted)->toBe('24x');
    });

    it('calculates years correctly for exact years', function () {
        $installmentCount = new InstallmentCount(24);

        expect($installmentCount->years)->toBe(2);
    });

    it('calculates years correctly rounding up', function () {
        $installmentCount = new InstallmentCount(13);

        expect($installmentCount->years)->toBe(2);
    });

    it('calculates years correctly for single installment', function () {
        $installmentCount = new InstallmentCount(1);

        expect($installmentCount->years)->toBe(1);
    });

    it('returns "À vista" for single installment', function () {
        $installmentCount = new InstallmentCount(1);

        expect($installmentCount->periodDescription)->toBe('À vista');
    });

    it('returns months description for less than 12 installments', function () {
        $installmentCount = new InstallmentCount(6);

        expect($installmentCount->periodDescription)->toBe('6 meses');
    });

    it('returns month description for exactly 2 installments', function () {
        $installmentCount = new InstallmentCount(2);

        expect($installmentCount->periodDescription)->toBe('2 meses');
    });

    it('returns years description for exact years', function () {
        $installmentCount = new InstallmentCount(24);

        expect($installmentCount->periodDescription)->toBe('2 anos');
    });

    it('returns year description for 12 installments', function () {
        $installmentCount = new InstallmentCount(12);

        expect($installmentCount->periodDescription)->toBe('1 ano');
    });

    it('returns years and months description for mixed periods', function () {
        $installmentCount = new InstallmentCount(25);

        expect($installmentCount->periodDescription)->toBe('3 anos e 1 mês');
    });

    it('returns years and months description for multiple months', function () {
        $installmentCount = new InstallmentCount(26);

        expect($installmentCount->periodDescription)->toBe('3 anos e 2 meses');
    });

    it('identifies short term correctly', function () {
        expect((new InstallmentCount(1))->isShortTerm())->toBeTrue()
            ->and((new InstallmentCount(6))->isShortTerm())->toBeTrue()
            ->and((new InstallmentCount(12))->isShortTerm())->toBeTrue()
            ->and((new InstallmentCount(13))->isShortTerm())->toBeFalse();
    });

    it('identifies medium term correctly', function () {
        expect((new InstallmentCount(12))->isMediumTerm())->toBeFalse()
            ->and((new InstallmentCount(13))->isMediumTerm())->toBeTrue()
            ->and((new InstallmentCount(24))->isMediumTerm())->toBeTrue()
            ->and((new InstallmentCount(36))->isMediumTerm())->toBeTrue()
            ->and((new InstallmentCount(37))->isMediumTerm())->toBeFalse();
    });

    it('identifies long term correctly', function () {
        expect((new InstallmentCount(36))->isLongTerm())->toBeFalse()
            ->and((new InstallmentCount(37))->isLongTerm())->toBeTrue()
            ->and((new InstallmentCount(60))->isLongTerm())->toBeTrue()
            ->and((new InstallmentCount(360))->isLongTerm())->toBeTrue();
    });

    it('compares equality correctly', function () {
        $installment1 = new InstallmentCount(12);
        $installment2 = new InstallmentCount(12);
        $installment3 = new InstallmentCount(24);

        expect($installment1->equals($installment2))->toBeTrue()
            ->and($installment1->equals($installment3))->toBeFalse();
    });

    it('compares greater than correctly', function () {
        $installment1 = new InstallmentCount(24);
        $installment2 = new InstallmentCount(12);
        $installment3 = new InstallmentCount(36);

        expect($installment1->isGreaterThan($installment2))->toBeTrue()
            ->and($installment1->isGreaterThan($installment3))->toBeFalse()
            ->and($installment1->isGreaterThan($installment1))->toBeFalse();
    });

    it('compares less than correctly', function () {
        $installment1 = new InstallmentCount(12);
        $installment2 = new InstallmentCount(24);
        $installment3 = new InstallmentCount(6);

        expect($installment1->isLessThan($installment2))->toBeTrue()
            ->and($installment1->isLessThan($installment3))->toBeFalse()
            ->and($installment1->isLessThan($installment1))->toBeFalse();
    });

    it('handles edge cases for period descriptions', function () {
        expect((new InstallmentCount(11))->periodDescription)->toBe('11 meses')
            ->and((new InstallmentCount(13))->periodDescription)->toBe('2 anos e 1 mês')
            ->and((new InstallmentCount(36))->periodDescription)->toBe('3 anos')
            ->and((new InstallmentCount(37))->periodDescription)->toBe('4 anos e 1 mês');
    });

    it('validates boundary values', function () {
        // Test minimum boundary
        $installment1 = new InstallmentCount(1);
        expect($installment1->value)->toBe(1);

        expect(fn () => new InstallmentCount(0))->toThrow(\InvalidArgumentException::class);
    });

    it('handles common installment values', function () {
        $commonValues = [1, 2, 3, 6, 12, 18, 24, 36, 48, 60, 72, 84, 96, 120, 180, 240, 360];

        foreach ($commonValues as $value) {
            $installmentCount = new InstallmentCount($value);
            expect($installmentCount->value)->toBe($value);
            expect($installmentCount->formatted)->toBe($value . 'x');
        }
    });
});
