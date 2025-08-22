<?php

declare(strict_types=1);

use App\Application\DTOs\CreditSimulationResponseDTO;
use App\Infrastructure\Http\Resources\CreditSimulationResource;
use Illuminate\Http\Request;

describe('CreditSimulationResource', function () {
    beforeEach(function () {
        $this->simulations = [
            [
                'financial_institution' => 'Test Bank',
                'credit_modality' => 'Personal Credit',
                'total_amount' => 120000,
                'monthly_payment' => 10000,
                'monthly_interest_rate' => 2.5,
            ],
            [
                'financial_institution' => 'Another Bank',
                'credit_modality' => 'Payroll Credit',
                'total_amount' => 115000,
                'monthly_payment' => 9500,
                'monthly_interest_rate' => 2.0,
            ],
        ];

        $this->simulationDTO = new CreditSimulationResponseDTO(
            cpf: '11144477735',
            requestedAmount: 100000,
            requestedInstallments: 12,
            simulations: $this->simulations,
            status: 'success',
            message: 'Simulations generated successfully'
        );
    });

    it('can be instantiated with CreditSimulationResponseDTO', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);

        expect($resource)->toBeInstanceOf(CreditSimulationResource::class);
    });

    it('converts DTO to array with correct structure', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array)->toBeArray()
            ->toHaveKeys([
                'cpf',
                'requested_amount',
                'requested_installments',
                'simulations',
                'total_simulations',
                'status',
                'message',
                'best_offer',
                'generated_at',
                'links',
            ]);
    });

    it('includes correct basic information', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['cpf'])->toBe('11144477735')
            ->and($array['requested_installments'])->toBe(12)
            ->and($array['status'])->toBe('success')
            ->and($array['message'])->toBe('Simulations generated successfully');
    });

    it('formats requested amount correctly', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['requested_amount'])->toHaveKeys(['value', 'formatted'])
            ->and($array['requested_amount']['value'])->toBe(100000.0)
            ->and($array['requested_amount']['formatted'])->toBe('R$ 100.000,00');
    });

    it('includes simulations data', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['simulations'])->toBeArray()
            ->and($array['simulations'])->toHaveCount(2)
            ->and($array['simulations'][0]['financial_institution'])->toBe('Test Bank')
            ->and($array['simulations'][1]['financial_institution'])->toBe('Another Bank');
    });

    it('calculates total simulations correctly', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['total_simulations'])->toBe(2);
    });

    it('includes best offer from DTO method', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        // The getBestSimulation should return the first simulation or null
        expect($array['best_offer'])->not->toBeNull();
    });

    it('includes generated timestamp', function () {
        $beforeTime = time();

        $resource = new CreditSimulationResource($this->simulationDTO);
        $array = $resource->toArray(new Request);

        $afterTime = time();
        $timestamp = strtotime($array['generated_at']);

        expect($array['generated_at'])->toBeString()
            ->and($array['generated_at'])->toContain('T')
            ->and($timestamp)->toBeGreaterThanOrEqual($beforeTime)
            ->and($timestamp)->toBeLessThanOrEqual($afterTime);
    });

    it('includes navigation links', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['links'])->toBeArray()
            ->toHaveKeys(['search', 'index']);
    });

    it('handles empty simulations', function () {
        $emptySimulationDTO = new CreditSimulationResponseDTO(
            cpf: '11144477735',
            requestedAmount: 50000,
            requestedInstallments: 6,
            simulations: [],
            status: 'no_offers',
            message: 'No simulations available'
        );

        $resource = new CreditSimulationResource($emptySimulationDTO);
        $array = $resource->toArray(new Request);

        expect($array['simulations'])->toBeArray()
            ->and($array['simulations'])->toHaveCount(0)
            ->and($array['total_simulations'])->toBe(0)
            ->and($array['status'])->toBe('no_offers')
            ->and($array['message'])->toBe('No simulations available');
    });

    it('handles null message', function () {
        $dtoWithNullMessage = new CreditSimulationResponseDTO(
            cpf: '11144477735',
            requestedAmount: 75000,
            requestedInstallments: 24,
            simulations: $this->simulations,
            status: 'success',
            message: null
        );

        $resource = new CreditSimulationResource($dtoWithNullMessage);
        $array = $resource->toArray(new Request);

        expect($array['message'])->toBeNull();
    });

    it('formats different amount values correctly', function () {
        $amounts = [1000, 50000, 100000, 500000.50];

        foreach ($amounts as $amount) {
            $dto = new CreditSimulationResponseDTO(
                cpf: '11144477735',
                requestedAmount: $amount,
                requestedInstallments: 12,
                simulations: [],
                status: 'success'
            );

            $resource = new CreditSimulationResource($dto);
            $array = $resource->toArray(new Request);

            expect($array['requested_amount']['value'])->toBe((float) $amount)
                ->and($array['requested_amount']['formatted'])->toContain('R$')
                ->and($array['requested_amount']['formatted'])->toContain(',');
        }
    });

    it('handles different installment counts', function () {
        $installments = [1, 6, 12, 24, 36, 48];

        foreach ($installments as $installmentCount) {
            $dto = new CreditSimulationResponseDTO(
                cpf: '11144477735',
                requestedAmount: 100000,
                requestedInstallments: $installmentCount,
                simulations: [],
                status: 'success'
            );

            $resource = new CreditSimulationResource($dto);
            $array = $resource->toArray(new Request);

            expect($array['requested_installments'])->toBe($installmentCount);
        }
    });

    it('handles different status values', function () {
        $statuses = ['success', 'no_offers', 'error', 'processing'];

        foreach ($statuses as $status) {
            $dto = new CreditSimulationResponseDTO(
                cpf: '11144477735',
                requestedAmount: 100000,
                requestedInstallments: 12,
                simulations: [],
                status: $status,
                message: "Status is {$status}"
            );

            $resource = new CreditSimulationResource($dto);
            $array = $resource->toArray(new Request);

            expect($array['status'])->toBe($status)
                ->and($array['message'])->toBe("Status is {$status}");
        }
    });

    it('maintains data integrity through resource conversion', function () {
        $resource = new CreditSimulationResource($this->simulationDTO);
        $array = $resource->toArray(new Request);

        expect($array['cpf'])->toBe($this->simulationDTO->cpf)
            ->and($array['requested_amount']['value'])->toBe($this->simulationDTO->requestedAmount)
            ->and($array['requested_installments'])->toBe($this->simulationDTO->requestedInstallments)
            ->and($array['simulations'])->toBe($this->simulationDTO->simulations)
            ->and($array['status'])->toBe($this->simulationDTO->status)
            ->and($array['message'])->toBe($this->simulationDTO->message);
    });
});
