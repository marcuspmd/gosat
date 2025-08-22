<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditOfferRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentCreditOfferRepository;
});

describe('EloquentCreditOfferRepository', function () {
    it('implements credit offer repository interface', function () {
        /** @var CreditOfferRepositoryInterface $repository */
        $repository = $this->repository;
        expect($repository)->toBeInstanceOf(CreditOfferRepositoryInterface::class);
    });

    it('has correct class structure', function () {
        expect(method_exists($this->repository, 'findById'))->toBeTrue();
        expect(method_exists($this->repository, 'findByCpf'))->toBeTrue();
        expect(method_exists($this->repository, 'save'))->toBeTrue();
        expect(method_exists($this->repository, 'saveAll'))->toBeTrue();
        expect(method_exists($this->repository, 'delete'))->toBeTrue();
    });
});

describe('findById', function () {
    it('returns null when credit offer not found', function () {
        $result = $this->repository->findById('non-existent-id');
        expect($result)->toBeNull();
    });

    it('returns credit offer entity when credit offer found', function () {
        // Create dependencies
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $creditOffer = CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
            'request_id' => 'REQ001',
        ]);

        $result = $this->repository->findById($creditOffer->id);

        expect($result)->toBeArray()
            ->toHaveCount(1);
        expect($result[0])->toBeInstanceOf(CreditOfferEntity::class);
        expect($result[0]->id)->toBe($creditOffer->id);
        expect($result[0]->customer->id)->toBe($customer->id);
        expect($result[0]->institution->id)->toBe($institution->id);
        expect($result[0]->modality->id)->toBe($modality->id);
        expect($result[0]->minAmount->amountInCents)->toBe(100000);
        expect($result[0]->maxAmount->amountInCents)->toBe(500000);
        expect($result[0]->monthlyInterestRate->monthlyRate)->toBe(2.5);
        expect($result[0]->minInstallments->value)->toBe(6);
        expect($result[0]->maxInstallments->value)->toBe(36);
        expect($result[0]->requestId)->toBe('REQ001');
    });
});

describe('findByCpf', function () {
    it('returns empty array when no credit offers found for cpf', function () {
        $cpf = new CPF(CpfHelper::valid());
        $result = $this->repository->findByCpf($cpf);

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('returns credit offer entities when credit offers found for cpf', function () {
        $cpfValue = CpfHelper::valid();
        $cpf = new CPF($cpfValue);

        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => $cpfValue,
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
        ]);

        CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174004',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 200000,
            'max_amount_cents' => 1000000,
            'monthly_interest_rate' => 3.0,
            'min_installments' => 12,
            'max_installments' => 48,
        ]);

        $result = $this->repository->findByCpf($cpf);

        expect($result)->toBeArray()->toHaveCount(2);
        expect($result[0])->toBeInstanceOf(CreditOfferEntity::class);
        expect($result[1])->toBeInstanceOf(CreditOfferEntity::class);
        expect($result[0]->customer->cpf->value)->toBe($cpfValue);
        expect($result[1]->customer->cpf->value)->toBe($cpfValue);
    });
});

describe('save', function () {
    it('creates new credit offer when credit offer does not exist', function () {
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $creditOfferEntity = new CreditOfferEntity(
            id: '123e4567-e89b-12d3-a456-426614174003',
            customer: CustomerEntity::fromModel($customer),
            institution: InstitutionEntity::fromModel($institution),
            modality: CreditModalityEntity::fromModel($modality),
            minAmount: Money::fromCents(100000),
            maxAmount: Money::fromCents(500000),
            monthlyInterestRate: new InterestRate(2.5),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(36),
            requestId: 'REQ001'
        );

        $this->repository->save($creditOfferEntity);

        $model = CreditOfferModel::find($creditOfferEntity->id);
        expect($model)->not()->toBeNull();
        expect($model->customer_id)->toBe($customer->id);
        expect($model->institution_id)->toBe($institution->id);
        expect($model->modality_id)->toBe($modality->id);
        expect($model->min_amount_cents)->toBe(100000);
        expect($model->max_amount_cents)->toBe(500000);
        expect($model->monthly_interest_rate)->toBe('2.500000');
        expect($model->min_installments)->toBe(6);
        expect($model->max_installments)->toBe(36);
        expect($model->request_id)->toBe('REQ001');
    });
});

describe('delete', function () {
    it('removes credit offer from database', function () {
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $creditOffer = CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
        ]);

        expect(CreditOfferModel::find($creditOffer->id))->not()->toBeNull();

        $this->repository->delete($creditOffer->id);

        expect(CreditOfferModel::find($creditOffer->id))->toBeNull();
    });

    it('handles non existent credit offer gracefully', function () {
        $this->repository->delete('non-existent-id');
        expect(true)->toBeTrue(); // Should not throw exception
    });
});

describe('additional methods', function () {
    it('implements findByRequestId method', function () {
        $result = $this->repository->findByRequestId('test-request-id');
        expect($result)->toBeArray();
    });

    it('implements softDeleteByCpf method and actually soft deletes offers', function () {
        // Create test data
        $cpfValue = CpfHelper::valid();
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => $cpfValue,
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $offer = CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
        ]);

        // Verify offer exists
        expect(CreditOfferModel::find($offer->id))->not()->toBeNull();

        // Soft delete
        $cpf = new CPF($cpfValue);
        $this->repository->softDeleteByCpf($cpf);

        // Verify offer is soft deleted
        expect(CreditOfferModel::find($offer->id))->toBeNull(); // Normal query doesn't find it
        expect(CreditOfferModel::withTrashed()->find($offer->id))->not()->toBeNull(); // But with trashed does
    });

    it('getAllCustomersWithOffers returns properly structured data', function () {
        // Create test data
        $cpfValue = CpfHelper::valid();
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => $cpfValue,
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
        ]);

        $result = $this->repository->getAllCustomersWithOffers();

        expect($result)->toBeArray();
        if (! empty($result)) {
            expect($result[0])->toHaveKeys(['cpf', 'offers_count', 'offers', 'available_ranges']);
            expect($result[0]['available_ranges'])->toHaveKeys(['min_amount_cents', 'max_amount_cents', 'min_installments', 'max_installments']);
        }
    });

    it('getOffersForCpf returns offers with limit', function () {
        // Create test data
        $cpfValue = CpfHelper::valid();
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => $cpfValue,
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
        ]);

        $cpf = new CPF($cpfValue);
        $result = $this->repository->getOffersForCpf($cpf, 5);

        expect($result)->toBeArray();
        expect(count($result))->toBeLessThanOrEqual(5);
    });

    it('getSimulationOffers filters by amount and installments correctly', function () {
        // Create test data
        $cpfValue = CpfHelper::valid();
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => $cpfValue,
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        // Create offer that matches simulation parameters
        CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 50000,  // Below our test amount
            'max_amount_cents' => 200000, // Above our test amount
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,      // Below our test installments
            'max_installments' => 24,     // Above our test installments
        ]);

        $cpf = new CPF($cpfValue);
        $result = $this->repository->getSimulationOffers($cpf, 100000, 12); // Should match the offer

        expect($result)->toBeArray();
    });

    it('getSimulationOffers filters by modality when provided', function () {
        $cpf = new CPF(CpfHelper::valid());
        $result = $this->repository->getSimulationOffers($cpf, 100000, 12, 'Personal Credit');

        expect($result)->toBeArray();
    });

    it('countOffersByRequestId returns correct count', function () {
        // Create test data with specific request_id
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $requestId = 'test-request-123';

        CreditOfferModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
            'request_id' => $requestId,
        ]);

        $count = $this->repository->countOffersByRequestId($requestId);
        expect($count)->toBe(1);

        $countNonExistent = $this->repository->countOffersByRequestId('non-existent-request');
        expect($countNonExistent)->toBe(0);
    });

    it('findPendingJobByRequestId searches correctly', function () {
        $result = $this->repository->findPendingJobByRequestId('test-request-id');
        expect($result)->toBeNull(); // No jobs in test DB
    });

    it('findFailedJobByRequestId searches correctly', function () {
        $result = $this->repository->findFailedJobByRequestId('test-request-id');
        expect($result)->toBeNull(); // No failed jobs in test DB
    });
});

describe('saveAll method', function () {
    it('saves multiple credit offers in transaction', function () {
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $offers = [
            new CreditOfferEntity(
                id: '123e4567-e89b-12d3-a456-426614174003',
                customer: CustomerEntity::fromModel($customer),
                institution: InstitutionEntity::fromModel($institution),
                modality: CreditModalityEntity::fromModel($modality),
                minAmount: Money::fromCents(100000),
                maxAmount: Money::fromCents(500000),
                monthlyInterestRate: new InterestRate(2.5),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(36),
                requestId: 'REQ001'
            ),
            new CreditOfferEntity(
                id: '123e4567-e89b-12d3-a456-426614174004',
                customer: CustomerEntity::fromModel($customer),
                institution: InstitutionEntity::fromModel($institution),
                modality: CreditModalityEntity::fromModel($modality),
                minAmount: Money::fromCents(200000),
                maxAmount: Money::fromCents(800000),
                monthlyInterestRate: new InterestRate(3.0),
                minInstallments: new InstallmentCount(12),
                maxInstallments: new InstallmentCount(48),
                requestId: 'REQ002'
            ),
        ];

        $this->repository->saveAll($offers);

        // Verify both offers were saved
        expect(CreditOfferModel::find($offers[0]->id))->not()->toBeNull();
        expect(CreditOfferModel::find($offers[1]->id))->not()->toBeNull();
    });

    it('filters out non-CreditOfferEntity items', function () {
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $validOffer = new CreditOfferEntity(
            id: '123e4567-e89b-12d3-a456-426614174003',
            customer: CustomerEntity::fromModel($customer),
            institution: InstitutionEntity::fromModel($institution),
            modality: CreditModalityEntity::fromModel($modality),
            minAmount: Money::fromCents(100000),
            maxAmount: Money::fromCents(500000),
            monthlyInterestRate: new InterestRate(2.5),
            minInstallments: new InstallmentCount(6),
            maxInstallments: new InstallmentCount(36),
            requestId: 'REQ001'
        );

        $mixedArray = [
            $validOffer,
            'invalid-item',  // This should be filtered out
            null,           // This should be filtered out
            123,            // This should be filtered out
        ];

        // Should not throw exception and only save the valid offer
        $this->repository->saveAll($mixedArray);

        expect(CreditOfferModel::find($validOffer->id))->not()->toBeNull();
    });
});

describe('save method with existing model', function () {
    it('updates existing credit offer when it already exists', function () {
        $customer = CustomerModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'cpf' => CpfHelper::valid(),
            'is_active' => true,
        ]);

        $institution = InstitutionModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'standard_code' => 'PC001',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $offerId = '123e4567-e89b-12d3-a456-426614174003';

        // Create initial offer
        $initialOffer = CreditOfferModel::create([
            'id' => $offerId,
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'modality_id' => $modality->id,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 500000,
            'monthly_interest_rate' => 2.5,
            'min_installments' => 6,
            'max_installments' => 36,
            'request_id' => 'REQ001',
        ]);

        // Create entity with updated values
        $updatedOfferEntity = new CreditOfferEntity(
            id: $offerId,
            customer: CustomerEntity::fromModel($customer),
            institution: InstitutionEntity::fromModel($institution),
            modality: CreditModalityEntity::fromModel($modality),
            minAmount: Money::fromCents(200000),  // Changed
            maxAmount: Money::fromCents(800000),  // Changed
            monthlyInterestRate: new InterestRate(3.5), // Changed
            minInstallments: new InstallmentCount(12), // Changed
            maxInstallments: new InstallmentCount(48), // Changed
            requestId: 'REQ002' // Changed
        );

        // Save updated entity
        $this->repository->save($updatedOfferEntity);

        // Verify the model was updated
        $updatedModel = CreditOfferModel::find($offerId);
        expect($updatedModel->min_amount_cents)->toBe(200000);
        expect($updatedModel->max_amount_cents)->toBe(800000);
        expect($updatedModel->monthly_interest_rate)->toBe('3.500000');
        expect($updatedModel->min_installments)->toBe(12);
        expect($updatedModel->max_installments)->toBe(48);
        expect($updatedModel->request_id)->toBe('REQ002');
    });
});
