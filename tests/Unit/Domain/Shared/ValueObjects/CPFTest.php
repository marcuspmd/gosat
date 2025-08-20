<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\CPF;

describe('CPF Value Object', function () {

    test('creates valid CPF from formatted string', function () {
        $cpf = new CPF('12345678909'); // Usando CPF de teste válido

        expect($cpf->value)->toBe('12345678909')
            ->and($cpf->formatted)->toBe('123.456.789-09');
    });

    test('creates valid CPF from unformatted string', function () {
        $cpf = new CPF('12345678909');

        expect($cpf->value)->toBe('12345678909')
            ->and($cpf->formatted)->toBe('123.456.789-09');
    });

    test('validates CPF check digits correctly', function () {
        // CPFs de teste válidos
        expect(fn () => new CPF('12345678909'))->not->toThrow(InvalidArgumentException::class);
        expect(fn () => new CPF('11144477735'))->not->toThrow(InvalidArgumentException::class);
        expect(fn () => new CPF('98765432100'))->not->toThrow(InvalidArgumentException::class);
    });

    test('throws exception for invalid CPF length', function () {
        expect(fn () => new CPF('123.456.789-0'))
            ->toThrow(InvalidArgumentException::class, 'CPF deve ter 11 dígitos');

        expect(fn () => new CPF('123.456.789-012'))
            ->toThrow(InvalidArgumentException::class, 'CPF deve ter 11 dígitos');
    });

    test('throws exception for CPF with all same digits', function () {
        expect(fn () => new CPF('000.000.000-00'))
            ->toThrow(InvalidArgumentException::class, 'CPF não pode ter todos os dígitos iguais');
    });

    test('throws exception for invalid check digits', function () {
        expect(fn () => new CPF('123.456.789-00'))
            ->toThrow(InvalidArgumentException::class, 'CPF inválido');

        expect(fn () => new CPF('987.654.321-99'))
            ->toThrow(InvalidArgumentException::class, 'CPF inválido');
    });

    test('compares CPF objects correctly', function () {
        $cpf1 = new CPF('123.456.789-09');
        $cpf2 = new CPF('12345678909');
        $cpf3 = new CPF('987.654.321-00');

        expect($cpf1->equals($cpf2))->toBeTrue()
            ->and($cpf1->equals($cpf3))->toBeFalse();
    });
});
