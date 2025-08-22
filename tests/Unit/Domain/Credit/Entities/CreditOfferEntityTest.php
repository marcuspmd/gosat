<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use Tests\Helpers\CpfHelper;

describe('CreditOfferEntity', function () {
    beforeEach(function () {
        $this->customer = new CustomerEntity(
            id: 'customer-123',
            cpf: new CPF(CpfHelper::valid('1'))
        );

        $this->institution = new InstitutionEntity(
            id: 'institution-456',
            institutionId: 123,
            name: 'Banco Teste'
        );

        $this->modality = new CreditModalityEntity(
            id: 'modality-789',
            standardCode: 'credito-pessoal',
            name: 'Crédito Pessoal'
        );
    });

    it('can be created with valid parameters', function () {
        $offer = new CreditOfferEntity(
            id: 'offer-123',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(1000.00),
            maxAmount: new Money(50000.00),
            monthlyInterestRate: new InterestRate(2.5),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(60)
        );

        expect($offer->id)->toBe('offer-123')
            ->and($offer->customer)->toBe($this->customer)
            ->and($offer->institution)->toBe($this->institution)
            ->and($offer->modality)->toBe($this->modality)
            ->and($offer->minAmount->value)->toBe(1000.00)
            ->and($offer->maxAmount->value)->toBe(50000.00)
            ->and($offer->monthlyInterestRate->monthlyRate)->toBe(2.5)
            ->and($offer->minInstallments->value)->toBe(6)
            ->and($offer->maxInstallments->value)->toBe(60)
            ->and($offer->requestId)->toBeNull()
            ->and($offer->errorMessage)->toBeNull()
            ->and($offer->createdAt)->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($offer->updatedAt)->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('can be created with optional parameters', function () {
        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-02 15:30:00');

        $offer = new CreditOfferEntity(
            id: 'offer-456',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(500.00),
            maxAmount: new Money(25000.00),
            monthlyInterestRate: new InterestRate(1.8),
            minInstallments: new InstallmentCount(12),
            maxInstallments: new InstallmentCount(48),
            requestId: 'request-789',
            errorMessage: 'Test error message',
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        expect($offer->requestId)->toBe('request-789')
            ->and($offer->errorMessage)->toBe('Test error message')
            ->and($offer->createdAt)->toBe($createdAt)
            ->and($offer->updatedAt)->toBe($updatedAt);
    });

    it('can convert to array representation', function () {
        $offer = new CreditOfferEntity(
            id: 'offer-array',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(2000.00),
            maxAmount: new Money(30000.00),
            monthlyInterestRate: new InterestRate(3.2),
            minInstallments: new InstallmentCount(3),
            maxInstallments: new InstallmentCount(36),
            requestId: 'req-123'
        );

        $array = $offer->toArray();

        expect($array)->toHaveKey('id')
            ->and($array['id'])->toBe('offer-array')
            ->and($array)->toHaveKey('customer_id')
            ->and($array['customer_id'])->toBe('customer-123')
            ->and($array)->toHaveKey('institution_id')
            ->and($array['institution_id'])->toBe('institution-456')
            ->and($array)->toHaveKey('modality_id')
            ->and($array['modality_id'])->toBe('modality-789')
            ->and($array)->toHaveKey('request_id')
            ->and($array['request_id'])->toBe('req-123');
    });

    it('handles different money values correctly', function () {
        $offer = new CreditOfferEntity(
            id: 'money-test',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(0.01), // 1 cent
            maxAmount: new Money(999999.99), // Large amount
            monthlyInterestRate: new InterestRate(0.1),
            minInstallments: new InstallmentCount(1),
            maxInstallments: new InstallmentCount(1)
        );

        expect($offer->minAmount->value)->toBe(0.01)
            ->and($offer->maxAmount->value)->toBe(999999.99);
    });

    it('handles different interest rates correctly', function () {
        $testCases = [
            0.0,    // Zero interest
            0.5,    // Low interest
            5.0,    // Medium interest
            15.99,   // High interest
        ];

        foreach ($testCases as $rate) {
            $offer = new CreditOfferEntity(
                id: "rate-test-{$rate}",
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate($rate),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24)
            );

            expect($offer->monthlyInterestRate->monthlyRate)->toBe($rate);
        }
    });

    it('handles different installment counts correctly', function () {
        $testCases = [
            ['min' => 1, 'max' => 1],     // Single installment
            ['min' => 6, 'max' => 60],    // Common range
            ['min' => 12, 'max' => 120],  // Extended range
        ];

        foreach ($testCases as $case) {
            $offer = new CreditOfferEntity(
                id: "installment-test-{$case['min']}-{$case['max']}",
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount($case['min']),
                maxInstallments: new InstallmentCount($case['max'])
            );

            expect($offer->minInstallments->value)->toBe($case['min'])
                ->and($offer->maxInstallments->value)->toBe($case['max']);
        }
    });

    it('can handle different error message scenarios', function () {
        $errorMessages = [
            null,
            '',
            'CPF inválido',
            'Renda insuficiente para aprovação',
            'Cliente não encontrado na base de dados da instituição',
        ];

        foreach ($errorMessages as $error) {
            $offer = new CreditOfferEntity(
                id: 'error-test-' . md5((string) $error),
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24),
                errorMessage: $error
            );

            expect($offer->errorMessage)->toBe($error);
        }
    });

    it('can handle different request ID scenarios', function () {
        $requestIds = [
            null,
            'simple-id',
            'uuid-12345678-1234-1234-1234-123456789012',
            'complex_request-ID-123',
        ];

        foreach ($requestIds as $requestId) {
            $offer = new CreditOfferEntity(
                id: 'request-test-' . md5((string) $requestId),
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24),
                requestId: $requestId
            );

            expect($offer->requestId)->toBe($requestId);
        }
    });

    it('maintains references to related entities', function () {
        $offer = new CreditOfferEntity(
            id: 'reference-test',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(1000.00),
            maxAmount: new Money(10000.00),
            monthlyInterestRate: new InterestRate(2.0),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(24)
        );

        // Should maintain the same object references
        expect($offer->customer)->toBe($this->customer)
            ->and($offer->institution)->toBe($this->institution)
            ->and($offer->modality)->toBe($this->modality);

        // Should have access to related entity properties
        expect($offer->customer->id)->toBe('customer-123')
            ->and($offer->institution->name)->toBe('Banco Teste')
            ->and($offer->modality->standardCode)->toBe('credito-pessoal');
    });

    it('uses current timestamp when dates are not provided', function () {
        $beforeCreation = new \DateTimeImmutable;

        $offer = new CreditOfferEntity(
            id: 'timestamp-test',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(1000.00),
            maxAmount: new Money(10000.00),
            monthlyInterestRate: new InterestRate(2.0),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(24)
        );

        $afterCreation = new \DateTimeImmutable;

        expect($offer->createdAt)->toBeGreaterThanOrEqual($beforeCreation)
            ->and($offer->createdAt)->toBeLessThanOrEqual($afterCreation)
            ->and($offer->updatedAt)->toBeGreaterThanOrEqual($beforeCreation)
            ->and($offer->updatedAt)->toBeLessThanOrEqual($afterCreation);
    });

    describe('equals method', function () {
        it('returns true for entities with same id', function () {
            $offer1 = new CreditOfferEntity(
                id: 'same-id',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24)
            );

            $offer2 = new CreditOfferEntity(
                id: 'same-id',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(2000.00), // Different values
                maxAmount: new Money(20000.00),
                monthlyInterestRate: new InterestRate(3.0),
                minInstallments: new InstallmentCount(12),
                maxInstallments: new InstallmentCount(36)
            );

            expect($offer1->equals($offer2))->toBeTrue();
        });

        it('returns false for entities with different id', function () {
            $offer1 = new CreditOfferEntity(
                id: 'id-1',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24)
            );

            $offer2 = new CreditOfferEntity(
                id: 'id-2',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00), // Same values but different ID
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24)
            );

            expect($offer1->equals($offer2))->toBeFalse();
        });
    });

    describe('updateModel method', function () {
        it('has correct method signature', function () {
            $entity = new CreditOfferEntity(
                id: 'offer-123',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(5000.00),
                maxAmount: new Money(50000.00),
                monthlyInterestRate: new InterestRate(2.8),
                minInstallments: new InstallmentCount(12),
                maxInstallments: new InstallmentCount(48)
            );

            $reflection = new \ReflectionMethod($entity, 'updateModel');
            $params = $reflection->getParameters();

            expect($reflection->getReturnType()->getName())->toBe('void')
                ->and($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and($params[0]->getType()->getName())->toBe('App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel');
        });
    });

    describe('Dependency injection in toModel method', function () {
        it('has correct method signature for dependency injection', function () {
            $entity = new CreditOfferEntity(
                id: '123',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(2.0),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24)
            );

            $reflection = new \ReflectionMethod($entity, 'toModel');
            $params = $reflection->getParameters();

            expect($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and($params[0]->getType()->getName())->toBe('App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel')
                ->and($params[0]->allowsNull())->toBeTrue()
                ->and($params[0]->isDefaultValueAvailable())->toBeTrue()
                ->and($params[0]->getDefaultValue())->toBeNull();
        });
    });
});
