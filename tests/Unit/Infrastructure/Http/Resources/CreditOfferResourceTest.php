<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\Enums\CreditOfferStatus;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Http\Resources\CreditOfferResource;
use Illuminate\Http\Request;
use Tests\Helpers\CpfHelper;

describe('CreditOfferResource', function () {
    beforeEach(function () {
        $this->customer = new CustomerEntity(
            id: 'customer-123',
            cpf: new CPF(CpfHelper::valid('1'))
        );

        $this->institution = new InstitutionEntity(
            id: 'institution-456',
            institutionId: 1,
            name: 'Test Institution'
        );

        $this->modality = new CreditModalityEntity(
            id: 'modality-789',
            standardCode: 'test-modality',
            name: 'Test Modality'
        );

        $this->creditOffer = new CreditOfferEntity(
            id: 'offer-123',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(1000.00),
            maxAmount: new Money(10000.00),
            monthlyInterestRate: new InterestRate(2.5),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(24),
            status: CreditOfferStatus::ACTIVE,
            requestId: 'request-456',
            errorMessage: 'Test error',
            createdAt: new DateTimeImmutable('2024-01-01 10:00:00'),
            updatedAt: new DateTimeImmutable('2024-01-02 15:30:00')
        );
    });

    it('can be instantiated with CreditOfferEntity', function () {
        $resource = new CreditOfferResource($this->creditOffer);

        expect($resource)->toBeInstanceOf(CreditOfferResource::class);
    });

    it('converts entity to array with correct structure', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array)->toBeArray()
            ->toHaveKeys([
                'id',
                'request_id',
                'institution',
                'modality',
                'amounts',
                'installments',
                'interest_rate',
                'calculated_values',
                'status',
                'status_label',
                'error_message',
                'created_at',
                'updated_at',
            ]);
    });

    it('includes correct basic offer information', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['id'])->toBe('offer-123')
            ->and($array['request_id'])->toBe('request-456')
            ->and($array['error_message'])->toBe('Test error');
    });

    it('includes institution information', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['institution'])->toHaveKey('id')
            ->and($array['institution']['id'])->toBe('institution-456');
    });

    it('includes modality information', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['modality'])->toHaveKeys(['id', 'name'])
            ->and($array['modality']['id'])->toBe('modality-789')
            ->and($array['modality']['name'])->toBe('Test Modality');
    });

    it('includes amount information with cents and fallback formatting', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['amounts'])->toHaveKeys(['min', 'max'])
            ->and($array['amounts']['min'])->toHaveKeys(['cents', 'formatted'])
            ->and($array['amounts']['max'])->toHaveKeys(['cents', 'formatted'])
            ->and($array['amounts']['min']['cents'])->toBe(100000)
            ->and($array['amounts']['max']['cents'])->toBe(1000000);
    });

    it('includes installments information', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['installments'])->toHaveKeys(['min', 'max'])
            ->and($array['installments']['min'])->toBe(6)
            ->and($array['installments']['max'])->toBe(24);
    });

    it('includes interest rate information with fallback values', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['interest_rate'])->toHaveKeys(['monthly', 'annual', 'formatted_monthly', 'formatted_annual'])
            ->and($array['interest_rate']['monthly'])->toBe(2.5)
            ->and($array['interest_rate']['annual'])->toBeFloat();
    });

    it('includes calculated values with fallback defaults', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['calculated_values'])->toHaveKeys([
            'monthly_payment',
            'total_amount',
            'total_interest',
            'effective_rate',
        ])
            ->and($array['calculated_values']['monthly_payment'])->toHaveKeys(['cents', 'formatted'])
            ->and($array['calculated_values']['total_amount'])->toHaveKeys(['cents', 'formatted'])
            ->and($array['calculated_values']['total_interest'])->toHaveKeys(['cents', 'formatted']);
    });

    it('includes formatted timestamps', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['created_at'])->toBeString()
            ->and($array['updated_at'])->toBeString()
            ->and($array['created_at'])->toContain('2024-01-01T10:00:00')
            ->and($array['updated_at'])->toContain('2024-01-02T15:30:00');
    });

    it('handles null error message', function () {
        $creditOfferWithoutError = new CreditOfferEntity(
            id: 'offer-no-error',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(1000.00),
            maxAmount: new Money(10000.00),
            monthlyInterestRate: new InterestRate(2.5),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(24),
            requestId: 'request-no-error',
            errorMessage: null
        );

        $resource = new CreditOfferResource($creditOfferWithoutError);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['error_message'])->toBeNull();
    });

    it('handles null request id', function () {
        $creditOfferWithoutRequestId = new CreditOfferEntity(
            id: 'offer-no-request-id',
            customer: $this->customer,
            institution: $this->institution,
            modality: $this->modality,
            minAmount: new Money(1000.00),
            maxAmount: new Money(10000.00),
            monthlyInterestRate: new InterestRate(2.5),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(24),
            requestId: null,
            errorMessage: null
        );

        $resource = new CreditOfferResource($creditOfferWithoutRequestId);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['request_id'])->toBeNull();
    });

    it('provides fallback values for nullable properties', function () {
        $resource = new CreditOfferResource($this->creditOffer);
        $request = new Request;

        $array = $resource->toArray($request);

        // Test that fallback values are used when properties might be null
        expect($array['amounts']['min']['cents'])->toBeInt()
            ->and($array['amounts']['max']['cents'])->toBeInt()
            ->and($array['installments']['min'])->toBeInt()
            ->and($array['installments']['max'])->toBeInt()
            ->and($array['interest_rate']['monthly'])->toBeFloat()
            ->and($array['calculated_values']['effective_rate'])->toBeFloat();
    });
});
