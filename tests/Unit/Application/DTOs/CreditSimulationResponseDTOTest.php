<?php

declare(strict_types=1);

use App\Application\DTOs\CreditSimulationResponseDTO;

describe('CreditSimulationResponseDTO', function () {
    it('can be created with all parameters', function () {
        $simulations = [
            ['institution' => 'Bank A', 'rate' => 2.5],
            ['institution' => 'Bank B', 'rate' => 3.0],
        ];

        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: $simulations,
            status: 'success',
            message: 'Simulation completed'
        );

        expect($dto->cpf)->toBe('12345678901')
            ->and($dto->requestedAmount)->toBe(10000.0)
            ->and($dto->requestedInstallments)->toBe(24)
            ->and($dto->simulations)->toBe($simulations)
            ->and($dto->status)->toBe('success')
            ->and($dto->message)->toBe('Simulation completed');
    });

    it('can be created with null message', function () {
        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: [],
            status: 'success'
        );

        expect($dto->message)->toBeNull();
    });

    it('can be converted to array', function () {
        $simulations = [
            ['institution' => 'Bank A', 'rate' => 2.5],
            ['institution' => 'Bank B', 'rate' => 3.0],
        ];

        $dto = new CreditSimulationResponseDTO(
            cpf: '98765432100',
            requestedAmount: 5000.0,
            requestedInstallments: 12,
            simulations: $simulations,
            status: 'success',
            message: 'Success'
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'cpf' => '98765432100',
            'requested_amount' => 5000.0,
            'requested_installments' => 12,
            'simulations' => $simulations,
            'total_simulations' => 2,
            'status' => 'success',
            'message' => 'Success',
        ]);
    });

    it('includes total_simulations count in toArray', function () {
        $simulations = [
            ['institution' => 'Bank A'],
            ['institution' => 'Bank B'],
            ['institution' => 'Bank C'],
        ];

        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: $simulations,
            status: 'success'
        );

        $array = $dto->toArray();

        expect($array['total_simulations'])->toBe(3);
    });

    it('returns false when has no simulations', function () {
        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: [],
            status: 'no_offers'
        );

        expect($dto->hasSimulations())->toBeFalse();
    });

    it('returns true when has simulations', function () {
        $simulations = [
            ['institution' => 'Bank A', 'rate' => 2.5],
        ];

        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: $simulations,
            status: 'success'
        );

        expect($dto->hasSimulations())->toBeTrue();
    });

    it('returns null for best simulation when no simulations exist', function () {
        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: [],
            status: 'no_offers'
        );

        expect($dto->getBestSimulation())->toBeNull();
    });

    it('returns first simulation as best simulation', function () {
        $simulations = [
            ['institution' => 'Best Bank', 'rate' => 1.5],
            ['institution' => 'Other Bank', 'rate' => 2.5],
            ['institution' => 'Another Bank', 'rate' => 3.0],
        ];

        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: $simulations,
            status: 'success'
        );

        $bestSimulation = $dto->getBestSimulation();

        expect($bestSimulation)->toBe(['institution' => 'Best Bank', 'rate' => 1.5]);
    });

    it('handles empty simulations array correctly', function () {
        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: [],
            status: 'no_offers',
            message: 'No offers available'
        );

        expect($dto->hasSimulations())->toBeFalse()
            ->and($dto->getBestSimulation())->toBeNull()
            ->and($dto->toArray()['total_simulations'])->toBe(0);
    });

    it('handles single simulation correctly', function () {
        $simulation = ['institution' => 'Single Bank', 'rate' => 2.0];
        $simulations = [$simulation];

        $dto = new CreditSimulationResponseDTO(
            cpf: '12345678901',
            requestedAmount: 10000.0,
            requestedInstallments: 24,
            simulations: $simulations,
            status: 'success'
        );

        expect($dto->hasSimulations())->toBeTrue()
            ->and($dto->getBestSimulation())->toBe($simulation)
            ->and($dto->toArray()['total_simulations'])->toBe(1);
    });
});
