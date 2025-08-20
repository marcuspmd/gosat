<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Money;

describe('Money Value Object', function () {

    test('creates money from float value', function () {
        $money = new Money(100.50);

        expect($money->amountInCents)->toBe(10050)
            ->and($money->value)->toBe(100.5)
            ->and($money->formatted)->toBe('R$ 100,50');
    });

    test('creates money from cents', function () {
        $money = Money::fromCents(10050);

        expect($money->amountInCents)->toBe(10050)
            ->and($money->value)->toBe(100.5)
            ->and($money->formatted)->toBe('R$ 100,50');
    });

    test('handles zero values correctly', function () {
        $money = new Money(0);

        expect($money->amountInCents)->toBe(0)
            ->and($money->value)->toBe(0.0)
            ->and($money->formatted)->toBe('R$ 0,00');
    });

    test('throws exception for negative values', function () {
        expect(fn () => new Money(-100))
            ->toThrow(InvalidArgumentException::class, 'Valor monetário não pode ser negativo');

        expect(fn () => Money::fromCents(-1000))
            ->toThrow(InvalidArgumentException::class, 'Valor em centavos não pode ser negativo');
    });

    test('adds money values correctly', function () {
        $money1 = new Money(100.50);
        $money2 = new Money(50.25);

        $result = $money1->add($money2);

        expect($result->value)->toBe(150.75)
            ->and($result->formatted)->toBe('R$ 150,75');
    });

    test('subtracts money values correctly', function () {
        $money1 = new Money(100.50);
        $money2 = new Money(50.25);

        $result = $money1->subtract($money2);

        expect($result->value)->toBe(50.25)
            ->and($result->formatted)->toBe('R$ 50,25');
    });

    test('throws exception when subtraction results in negative', function () {
        $money1 = new Money(50.00);
        $money2 = new Money(100.00);

        expect(fn () => $money1->subtract($money2))
            ->toThrow(InvalidArgumentException::class, 'Resultado da subtração não pode ser negativo');
    });

    test('multiplies money correctly', function () {
        $money = new Money(100.00);

        $result = $money->multiply(1.5);

        expect($result->value)->toBe(150.0)
            ->and($result->formatted)->toBe('R$ 150,00');
    });

    test('divides money correctly', function () {
        $money = new Money(100.00);

        $result = $money->divide(2);

        expect($result->value)->toBe(50.0)
            ->and($result->formatted)->toBe('R$ 50,00');
    });

    test('throws exception for negative multiplier', function () {
        $money = new Money(100.00);

        expect(fn () => $money->multiply(-1))
            ->toThrow(InvalidArgumentException::class, 'Multiplicador não pode ser negativo');
    });

    test('throws exception for zero or negative divisor', function () {
        $money = new Money(100.00);

        expect(fn () => $money->divide(0))
            ->toThrow(InvalidArgumentException::class, 'Divisor deve ser maior que zero');

        expect(fn () => $money->divide(-1))
            ->toThrow(InvalidArgumentException::class, 'Divisor deve ser maior que zero');
    });

    test('compares money values correctly', function () {
        $money1 = new Money(100.00);
        $money2 = new Money(50.00);
        $money3 = new Money(100.00);

        expect($money1->isGreaterThan($money2))->toBeTrue()
            ->and($money2->isLessThan($money1))->toBeTrue()
            ->and($money1->equals($money3))->toBeTrue()
            ->and($money1->equals($money2))->toBeFalse();
    });

    test('handles rounding correctly', function () {
        $money = new Money(100.999);

        // Should round to nearest cent
        expect($money->amountInCents)->toBe(10100)
            ->and($money->value)->toBe(101.0);
    });
});
