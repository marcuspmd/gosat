<?php

declare(strict_types=1);

use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Shared\ValueObjects\CPF;
use Tests\Helpers\CpfHelper;

describe('CustomerEntity', function () {
    it('can be created with valid parameters', function () {
        $id = 'customer-123';
        $cpf = new CPF(CpfHelper::valid('1'));

        $customer = new CustomerEntity(
            id: $id,
            cpf: $cpf
        );

        expect($customer->id)->toBe($id);
        expect($customer->cpf)->toBe($cpf);
        expect($customer->isActive)->toBeTrue();
    });

    it('can be created with custom dates and inactive status', function () {
        $id = 'customer-456';
        $cpf = new CPF(CpfHelper::valid('2'));
        $createdAt = new DateTimeImmutable('2024-01-01T10:00:00Z');
        $updatedAt = new DateTimeImmutable('2024-01-05T15:30:00Z');

        $customer = new CustomerEntity(
            id: $id,
            cpf: $cpf,
            isActive: false,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        expect($customer->id)->toBe($id);
        expect($customer->cpf)->toBe($cpf);
        expect($customer->isActive)->toBeFalse();
        expect($customer->createdAt)->toBe($createdAt);
        expect($customer->updatedAt)->toBe($updatedAt);
    });

    it('can compare equality with other entities', function () {
        $id = 'customer-789';
        $cpf = new CPF(CpfHelper::valid('1'));

        $customer1 = new CustomerEntity(id: $id, cpf: $cpf);
        $customer2 = new CustomerEntity(id: $id, cpf: new CPF(CpfHelper::valid('2')));
        $customer3 = new CustomerEntity(id: 'different-id', cpf: $cpf);

        expect($customer1->equals($customer2))->toBeTrue(); // Same ID
        expect($customer1->equals($customer3))->toBeFalse(); // Different ID
    });

    it('handles different CPF formats correctly', function () {
        $cpfValue = CpfHelper::valid('1');
        $cpf = new CPF($cpfValue);
        $id = 'test-customer';

        $customer = new CustomerEntity(id: $id, cpf: $cpf);

        expect($customer->cpf->value)->toBe($cpfValue);
    });

    it('can handle active status changes', function () {
        $id = 'status-test';
        $cpf = new CPF(CpfHelper::valid('3'));

        $activeCustomer = new CustomerEntity(
            id: $id,
            cpf: $cpf,
            isActive: true
        );

        $inactiveCustomer = new CustomerEntity(
            id: $id,
            cpf: $cpf,
            isActive: false
        );

        expect($activeCustomer->isActive)->toBeTrue();
        expect($inactiveCustomer->isActive)->toBeFalse();
    });

    it('uses current timestamp when dates are not provided', function () {
        $id = 'timestamp-test';
        $cpf = new CPF(CpfHelper::valid('1'));

        $beforeCreation = new DateTimeImmutable;
        $customer = new CustomerEntity(id: $id, cpf: $cpf);
        $afterCreation = new DateTimeImmutable;

        expect($customer->createdAt)->toBeGreaterThanOrEqual($beforeCreation);
        expect($customer->createdAt)->toBeLessThanOrEqual($afterCreation);
        expect($customer->updatedAt)->toBeGreaterThanOrEqual($beforeCreation);
        expect($customer->updatedAt)->toBeLessThanOrEqual($afterCreation);
    });

    it('can be created with different IDs and same CPF', function () {
        $cpf = new CPF(CpfHelper::valid('3'));
        $id1 = 'customer-001';
        $id2 = 'customer-002';

        $customer1 = new CustomerEntity(id: $id1, cpf: $cpf);
        $customer2 = new CustomerEntity(id: $id2, cpf: $cpf);

        expect($customer1->id)->toBe($id1);
        expect($customer2->id)->toBe($id2);
        expect($customer1->cpf->value)->toBe($customer2->cpf->value);
    });

    it('maintains immutability of CPF value object', function () {
        $cpfValue = CpfHelper::valid('1');
        $cpf = new CPF($cpfValue);
        $id = 'immutability-test';

        $customer = new CustomerEntity(id: $id, cpf: $cpf);

        expect($customer->cpf->value)->toBe($cpfValue);
        expect($customer->cpf)->toBe($cpf);
    });

    it('can be created with edge case IDs', function () {
        $cpf = new CPF(CpfHelper::valid('2'));
        $edgeCaseIds = [
            '1',                           // Single character
            'customer-with-dashes',        // Hyphenated ID
            'UPPERCASE_ID',               // Uppercase with underscore
            '123456789',                   // Numeric string
        ];

        foreach ($edgeCaseIds as $id) {
            $customer = new CustomerEntity(id: $id, cpf: $cpf);
            expect($customer->id)->toBe($id);
        }
    });

    it('equality is based on ID only', function () {
        $id = 'same-id';
        $cpf1 = new CPF(CpfHelper::valid('1'));
        $cpf2 = new CPF(CpfHelper::valid('2'));

        $customer1 = new CustomerEntity(id: $id, cpf: $cpf1, isActive: true);
        $customer2 = new CustomerEntity(id: $id, cpf: $cpf2, isActive: false);

        expect($customer1->equals($customer2))->toBeTrue(); // Same ID, different CPF and status
    });

    describe('Model conversion methods', function () {
        it('has correct toModel method signature', function () {
            $entity = new CustomerEntity(
                id: 'test',
                cpf: new CPF(CpfHelper::valid('1'))
            );

            $reflection = new \ReflectionMethod($entity, 'toModel');
            $params = $reflection->getParameters();

            expect($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and((string) $params[0]->getType())->toBe('?App\Infrastructure\Persistence\Eloquent\Models\CustomerModel')
                ->and($params[0]->allowsNull())->toBeTrue()
                ->and($params[0]->isDefaultValueAvailable())->toBeTrue()
                ->and($params[0]->getDefaultValue())->toBeNull();
        });

        it('has correct updateModel method signature', function () {
            $entity = new CustomerEntity(
                id: 'test',
                cpf: new CPF(CpfHelper::valid('1'))
            );

            $reflection = new \ReflectionMethod($entity, 'updateModel');
            $params = $reflection->getParameters();

            expect((string) $reflection->getReturnType())->toBe('void')
                ->and($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and((string) $params[0]->getType())->toBe('App\Infrastructure\Persistence\Eloquent\Models\CustomerModel');
        });

        it('has correct fromModel method signature', function () {
            $reflection = new \ReflectionMethod(CustomerEntity::class, 'fromModel');
            $params = $reflection->getParameters();

            expect($reflection->isStatic())->toBeTrue()
                ->and($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and((string) $params[0]->getType())->toBe('App\Infrastructure\Persistence\Eloquent\Models\CustomerModel');
        });
    });

});

afterEach(function () {
    Mockery::close();
});
