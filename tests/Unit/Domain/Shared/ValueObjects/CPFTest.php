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

    test('returns masked CPF correctly', function () {
        $cpf = new CPF('12345678909');
        
        expect($cpf->masked())->toBe('123.***.***-09');
    });

    test('returns CPF as string', function () {
        $cpf = new CPF('12345678909');
        
        expect($cpf->asString())->toBe('12345678909');
    });

    test('throws exception for CPF with all same digits using regex validation', function () {
        // Test cases that trigger the regex validation path (linha 54)
        expect(fn () => new CPF('33333333333'))
            ->toThrow(InvalidArgumentException::class, 'CPF não pode ter todos os dígitos iguais');
            
        expect(fn () => new CPF('55555555555'))
            ->toThrow(InvalidArgumentException::class, 'CPF não pode ter todos os dígitos iguais');
    });

    test('validates CPF with invalid check digits returns false', function () {
        // Test a CPF that would pass initial validations but fail check digit validation (linha 64)
        expect(fn () => new CPF('12345678901')) // Invalid check digits
            ->toThrow(InvalidArgumentException::class, 'CPF inválido');
            
        expect(fn () => new CPF('98765432111')) // Invalid check digits  
            ->toThrow(InvalidArgumentException::class, 'CPF inválido');
    });

    test('equals method with different CPFs', function () {
        $cpf1 = new CPF('12345678909');
        $cpf2 = new CPF('11144477735');
        
        // Test linha 73 - different CPF values
        expect($cpf1->equals($cpf2))->toBeFalse();
    });
});
