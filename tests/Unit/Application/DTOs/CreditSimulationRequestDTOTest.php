<?php

declare(strict_types=1);

use App\Application\DTOs\CreditSimulationRequestDTO;

describe('CreditSimulationRequestDTO', function () {
    it('can be created with required parameters', function () {
        $dto = new CreditSimulationRequestDTO(
            cpf: '12345678901',
            amount: 1000.0,
            installments: 12
        );

        expect($dto->cpf)->toBe('12345678901')
            ->and($dto->amount)->toBe(1000.0)
            ->and($dto->installments)->toBe(12);
    });

    it('uses default installments value when not provided', function () {
        $dto = new CreditSimulationRequestDTO(
            cpf: '12345678901',
            amount: 1000.0
        );

        expect($dto->installments)->toBe(1);
    });

    it('can be created from array', function () {
        $data = [
            'cpf' => '98765432100',
            'amount' => 5000.0,
            'installments' => 24,
        ];

        $dto = CreditSimulationRequestDTO::fromArray($data);

        expect($dto->cpf)->toBe('98765432100')
            ->and($dto->amount)->toBe(5000.0)
            ->and($dto->installments)->toBe(24);
    });

    it('handles missing cpf in array gracefully', function () {
        $data = [
            'amount' => 5000.0,
            'installments' => 24,
        ];

        $dto = CreditSimulationRequestDTO::fromArray($data);

        expect($dto->cpf)->toBe('')
            ->and($dto->amount)->toBe(5000.0)
            ->and($dto->installments)->toBe(24);
    });

    it('handles missing amount in array gracefully', function () {
        $data = [
            'cpf' => '98765432100',
            'installments' => 24,
        ];

        $dto = CreditSimulationRequestDTO::fromArray($data);

        expect($dto->cpf)->toBe('98765432100')
            ->and($dto->amount)->toBe(0.0)
            ->and($dto->installments)->toBe(24);
    });

    it('uses default installments when missing in array', function () {
        $data = [
            'cpf' => '98765432100',
            'amount' => 5000.0,
        ];

        $dto = CreditSimulationRequestDTO::fromArray($data);

        expect($dto->installments)->toBe(1);
    });

    it('handles empty array gracefully', function () {
        $dto = CreditSimulationRequestDTO::fromArray([]);

        expect($dto->cpf)->toBe('')
            ->and($dto->amount)->toBe(0.0)
            ->and($dto->installments)->toBe(1);
    });

    it('converts string amount to float in fromArray', function () {
        $data = [
            'cpf' => '98765432100',
            'amount' => '5000.50',
            'installments' => 24,
        ];

        $dto = CreditSimulationRequestDTO::fromArray($data);

        expect($dto->amount)->toBe(5000.5);
    });

    it('converts string installments to int in fromArray', function () {
        $data = [
            'cpf' => '98765432100',
            'amount' => 5000.0,
            'installments' => '24',
        ];

        $dto = CreditSimulationRequestDTO::fromArray($data);

        expect($dto->installments)->toBe(24);
    });

    it('can be converted to array', function () {
        $dto = new CreditSimulationRequestDTO(
            cpf: '12345678901',
            amount: 1500.75,
            installments: 18
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'cpf' => '12345678901',
            'amount' => 1500.75,
            'installments' => 18,
        ]);
    });

    it('maintains data integrity through array conversion cycle', function () {
        $originalData = [
            'cpf' => '11122233344',
            'amount' => 2500.25,
            'installments' => 36,
        ];

        $dto = CreditSimulationRequestDTO::fromArray($originalData);
        $convertedData = $dto->toArray();

        expect($convertedData)->toBe($originalData);
    });
});
