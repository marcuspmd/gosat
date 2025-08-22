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
use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

describe('CreditOfferEntity Feature Tests', function () {
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
    });

    describe('updateModel method integration', function () {
        it('updates a real Eloquent model successfully', function () {
            $entity = new CreditOfferEntity(
                id: 'offer-123',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(5000.00),
                maxAmount: new Money(50000.00),
                monthlyInterestRate: new InterestRate(2.8),
                minInstallments: new InstallmentCount(12),
                maxInstallments: new InstallmentCount(48),
                requestId: 'updated-request',
                errorMessage: 'Updated error message',
                updatedAt: new \DateTimeImmutable('2024-01-15 10:30:00')
            );

            // Create a real Eloquent model
            $model = new CreditOfferModel;
            $model->id = 'original-id';
            $model->customer_id = 'old-customer';
            $model->institution_id = 'old-institution';
            $model->modality_id = 'old-modality';
            $model->min_amount_cents = 100000;
            $model->max_amount_cents = 1000000;
            $model->monthly_interest_rate = 1.5;
            $model->min_installments = 6;
            $model->max_installments = 24;
            $model->request_id = 'old-request';
            $model->error_message = 'old error';
            $model->updated_at = new \DateTimeImmutable('2024-01-01');

            // Execute the updateModel method
            $entity->updateModel($model);

            // Verify all properties were updated correctly
            expect($model->id)->toBe('original-id') // ID should remain unchanged
                ->and($model->customer_id)->toBe('customer-123')
                ->and($model->institution_id)->toBe('institution-456')
                ->and($model->modality_id)->toBe('modality-789')
                ->and($model->min_amount_cents)->toBe(500000)
                ->and($model->max_amount_cents)->toBe(5000000)
                ->and($model->monthly_interest_rate)->toEqual(2.8)
                ->and($model->min_installments)->toBe(12)
                ->and($model->max_installments)->toBe(48)
                ->and($model->request_id)->toBe('updated-request')
                ->and($model->error_message)->toBe('Updated error message')
                ->and($model->updated_at->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });

        it('handles null values in updateModel correctly', function () {
            $entity = new CreditOfferEntity(
                id: 'offer-456',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(1000.00),
                maxAmount: new Money(10000.00),
                monthlyInterestRate: new InterestRate(1.5),
                minInstallments: new InstallmentCount(6),
                maxInstallments: new InstallmentCount(24),
                requestId: null,
                errorMessage: null
            );

            $model = new CreditOfferModel;
            $model->request_id = 'will-be-null';
            $model->error_message = 'will-be-null';

            $entity->updateModel($model);

            expect($model->request_id)->toBeNull()
                ->and($model->error_message)->toBeNull()
                ->and($model->customer_id)->toBe('customer-123')
                ->and($model->institution_id)->toBe('institution-456')
                ->and($model->modality_id)->toBe('modality-789');
        });
    });

    describe('fromModel method integration', function () {
        it('creates entity from real Eloquent models with relationships', function () {
            // Create real Eloquent models with relationships
            $customerModel = new CustomerModel;
            $customerModel->id = 'customer-789';
            $customerModel->cpf = CpfHelper::valid('2');
            $customerModel->is_active = true;
            $customerModel->created_at = new \DateTimeImmutable('2024-01-01');
            $customerModel->updated_at = new \DateTimeImmutable('2024-01-01');

            $institutionModel = new InstitutionModel;
            $institutionModel->id = 'institution-123';
            $institutionModel->name = 'Test Bank';
            $institutionModel->slug = 'test-bank';
            $institutionModel->is_active = true;
            $institutionModel->created_at = new \DateTimeImmutable('2024-01-01');
            $institutionModel->updated_at = new \DateTimeImmutable('2024-01-01');

            $modalityModel = new CreditModalityModel;
            $modalityModel->id = 'modality-456';
            $modalityModel->standard_code = 'personal-credit';
            $modalityModel->name = 'Personal Credit';
            $modalityModel->is_active = true;
            $modalityModel->created_at = new \DateTimeImmutable('2024-01-01');
            $modalityModel->updated_at = new \DateTimeImmutable('2024-01-01');

            $offerModel = new CreditOfferModel;
            $offerModel->id = 'offer-789';
            $offerModel->customer_id = 'customer-789';
            $offerModel->institution_id = 'institution-123';
            $offerModel->modality_id = 'modality-456';
            $offerModel->min_amount_cents = 200000;
            $offerModel->max_amount_cents = 3000000;
            $offerModel->monthly_interest_rate = 2.5;
            $offerModel->min_installments = 12;
            $offerModel->max_installments = 60;
            $offerModel->request_id = 'req-test';
            $offerModel->error_message = 'Test error';
            $offerModel->created_at = new \DateTimeImmutable('2024-01-01');
            $offerModel->updated_at = new \DateTimeImmutable('2024-01-02');

            // Set up relationships
            $offerModel->setRelation('customer', $customerModel);
            $offerModel->setRelation('institution', $institutionModel);
            $offerModel->setRelation('modality', $modalityModel);

            $entity = CreditOfferEntity::fromModel($offerModel);

            expect($entity->id)->toBe('offer-789')
                ->and($entity->customer->id)->toBe('customer-789')
                ->and($entity->customer->cpf->value)->toBe(CpfHelper::valid('2'))
                ->and($entity->institution->id)->toBe('institution-123')
                ->and($entity->institution->name)->toBe('Test Bank')
                ->and($entity->modality->id)->toBe('modality-456')
                ->and($entity->modality->standardCode)->toBe('personal-credit')
                ->and($entity->minAmount->value)->toBe(2000.00)
                ->and($entity->maxAmount->value)->toBe(30000.00)
                ->and($entity->monthlyInterestRate->monthlyRate)->toBe(2.5)
                ->and($entity->minInstallments->value)->toBe(12)
                ->and($entity->maxInstallments->value)->toBe(60)
                ->and($entity->requestId)->toBe('req-test')
                ->and($entity->errorMessage)->toBe('Test error')
                ->and($entity->createdAt->format('Y-m-d H:i:s'))->toBe($offerModel->created_at->format('Y-m-d H:i:s'))
                ->and($entity->updatedAt->format('Y-m-d H:i:s'))->toBe($offerModel->updated_at->format('Y-m-d H:i:s'));
        });
    });

    describe('toModel method integration', function () {
        it('creates a real Eloquent model when none provided', function () {
            $entity = new CreditOfferEntity(
                id: 'offer-new',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(3000.00),
                maxAmount: new Money(25000.00),
                monthlyInterestRate: new InterestRate(3.2),
                minInstallments: new InstallmentCount(18),
                maxInstallments: new InstallmentCount(72),
                requestId: 'new-request',
                errorMessage: null,
                createdAt: new \DateTimeImmutable('2024-02-01'),
                updatedAt: new \DateTimeImmutable('2024-02-02')
            );

            $model = $entity->toModel();

            expect($model)->toBeInstanceOf(CreditOfferModel::class)
                ->and($model->id)->toBe('offer-new')
                ->and($model->customer_id)->toBe('customer-123')
                ->and($model->institution_id)->toBe('institution-456')
                ->and($model->modality_id)->toBe('modality-789')
                ->and($model->min_amount_cents)->toBe(300000)
                ->and($model->max_amount_cents)->toBe(2500000)
                ->and($model->monthly_interest_rate)->toEqual(3.2)
                ->and($model->min_installments)->toBe(18)
                ->and($model->max_installments)->toBe(72)
                ->and($model->request_id)->toBe('new-request')
                ->and($model->error_message)->toBeNull()
                ->and($model->created_at->format('Y-m-d H:i:s'))->toBe($entity->createdAt->format('Y-m-d H:i:s'))
                ->and($model->updated_at->format('Y-m-d H:i:s'))->toBe($entity->updatedAt->format('Y-m-d H:i:s'));
        });

        it('uses provided model when given', function () {
            $entity = new CreditOfferEntity(
                id: 'offer-provided',
                customer: $this->customer,
                institution: $this->institution,
                modality: $this->modality,
                minAmount: new Money(4000.00),
                maxAmount: new Money(40000.00),
                monthlyInterestRate: new InterestRate(2.9),
                minInstallments: new InstallmentCount(24),
                maxInstallments: new InstallmentCount(84)
            );

            $providedModel = new CreditOfferModel;
            $providedModel->some_existing_field = 'should_remain';

            $result = $entity->toModel($providedModel);

            expect($result)->toBe($providedModel)
                ->and($result->id)->toBe('offer-provided')
                ->and($result->customer_id)->toBe('customer-123')
                ->and($result->min_amount_cents)->toBe(400000)
                ->and($result->max_amount_cents)->toBe(4000000);
        });
    });
});
