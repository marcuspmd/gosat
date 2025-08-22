<?php

declare(strict_types=1);

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

describe('CustomerEntity Feature Tests', function () {
    describe('Model conversion methods integration', function () {
        it('creates entity from real Eloquent model', function () {
            // Create a real Eloquent model
            $model = new CustomerModel();
            $model->id = 'customer-456';
            $model->cpf = CpfHelper::valid('2');
            $model->is_active = false;
            $model->created_at = new \DateTimeImmutable('2024-01-01 10:00:00');
            $model->updated_at = new \DateTimeImmutable('2024-01-02 15:30:00');

            $entity = CustomerEntity::fromModel($model);

            expect($entity->id)->toBe('customer-456')
                ->and($entity->cpf->value)->toBe(CpfHelper::valid('2'))
                ->and($entity->isActive)->toBeFalse()
                ->and($entity->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-01 10:00:00')
                ->and($entity->updatedAt->format('Y-m-d H:i:s'))->toBe('2024-01-02 15:30:00');
        });

        it('converts entity to new model when none provided', function () {
            $entity = new CustomerEntity(
                id: 'new-customer',
                cpf: new CPF(CpfHelper::valid('3')),
                isActive: true,
                createdAt: new \DateTimeImmutable('2024-02-01 08:15:30'),
                updatedAt: new \DateTimeImmutable('2024-02-02 14:45:10')
            );

            $model = $entity->toModel();

            expect($model)->toBeInstanceOf(CustomerModel::class)
                ->and($model->id)->toBe('new-customer')
                ->and($model->cpf)->toBe(CpfHelper::valid('3'))
                ->and($model->is_active)->toBe(true)
                ->and($model->created_at->format('Y-m-d H:i:s'))->toBe('2024-02-01 08:15:30')
                ->and($model->updated_at->format('Y-m-d H:i:s'))->toBe('2024-02-02 14:45:10');
        });

        it('uses provided model when converting to model', function () {
            $entity = new CustomerEntity(
                id: 'provided-test',
                cpf: new CPF(CpfHelper::valid('4')),
                isActive: false
            );

            $providedModel = new CustomerModel();
            $providedModel->existing_field = 'should_remain';

            $result = $entity->toModel($providedModel);

            expect($result)->toBe($providedModel)
                ->and($result->id)->toBe('provided-test')
                ->and($result->cpf)->toBe(CpfHelper::valid('4'))
                ->and($result->is_active)->toBeFalse();
        });

        it('updates existing model with entity data', function () {
            $entity = new CustomerEntity(
                id: 'update-test',
                cpf: new CPF(CpfHelper::valid('5')),
                isActive: false,
                updatedAt: new \DateTimeImmutable('2024-03-01 12:00:00')
            );

            // Create a model with existing data
            $model = new CustomerModel();
            $model->id = 'original-id'; // Should remain unchanged
            $model->cpf = CpfHelper::valid('1'); // Should be updated
            $model->is_active = true; // Should be updated
            $model->created_at = new \DateTimeImmutable('2024-01-01');
            $model->updated_at = new \DateTimeImmutable('2024-01-01'); // Should be updated

            // Execute updateModel
            $entity->updateModel($model);

            // Verify updates (ID should remain unchanged)
            expect($model->id)->toBe('original-id') // ID not changed by updateModel
                ->and($model->cpf)->toBe(CpfHelper::valid('5'))
                ->and($model->is_active)->toBeFalse()
                ->and($model->updated_at->format('Y-m-d H:i:s'))->toBe('2024-03-01 12:00:00');

            // created_at should remain unchanged by updateModel
            expect($model->created_at->format('Y-m-d'))->toBe('2024-01-01');
        });

        it('handles active/inactive status correctly in model conversion', function () {
            // Test active customer
            $activeEntity = new CustomerEntity(
                id: 'active-customer',
                cpf: new CPF(CpfHelper::valid('6')),
                isActive: true
            );

            $activeModel = $activeEntity->toModel();
            expect($activeModel->is_active)->toBeTrue();

            // Test inactive customer
            $inactiveEntity = new CustomerEntity(
                id: 'inactive-customer',
                cpf: new CPF(CpfHelper::valid('7')),
                isActive: false
            );

            $inactiveModel = $inactiveEntity->toModel();
            expect($inactiveModel->is_active)->toBeFalse();
        });

        it('preserves CPF format in model conversion', function () {
            $cpfValue = CpfHelper::valid('8');
            $entity = new CustomerEntity(
                id: 'cpf-test',
                cpf: new CPF($cpfValue)
            );

            $model = $entity->toModel();

            expect($model->cpf)->toBe($cpfValue);

            // Test round-trip conversion
            $entityFromModel = CustomerEntity::fromModel($model);
            expect($entityFromModel->cpf->value)->toBe($cpfValue);
        });
    });
});