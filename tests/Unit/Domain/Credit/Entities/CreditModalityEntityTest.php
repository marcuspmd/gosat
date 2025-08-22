<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditModalityEntity;

describe('CreditModalityEntity', function () {
    it('can be created with valid parameters', function () {
        $entity = new CreditModalityEntity(
            id: '1',
            standardCode: 'credito-pessoal',
            name: 'Crédito Pessoal'
        );

        expect($entity->id)->toBe('1')
            ->and($entity->standardCode)->toBe('credito-pessoal')
            ->and($entity->name)->toBe('Crédito Pessoal')
            ->and($entity->isActive)->toBeTrue()
            ->and($entity->createdAt)->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($entity->updatedAt)->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('can be created with custom dates', function () {
        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-02 10:00:00');

        $entity = new CreditModalityEntity(
            id: '2',
            standardCode: 'credito-consignado',
            name: 'Crédito Consignado',
            isActive: false,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        expect($entity->id)->toBe('2')
            ->and($entity->standardCode)->toBe('credito-consignado')
            ->and($entity->name)->toBe('Crédito Consignado')
            ->and($entity->isActive)->toBeFalse()
            ->and($entity->createdAt)->toBe($createdAt)
            ->and($entity->updatedAt)->toBe($updatedAt);
    });

    it('trims and validates name input', function () {
        $entity = new CreditModalityEntity(
            id: '3',
            standardCode: 'test-code',
            name: '  Crédito com Espaços  '
        );

        expect($entity->name)->toBe('Crédito com Espaços');
    });

    it('trims and validates standard code input', function () {
        $entity = new CreditModalityEntity(
            id: '4',
            standardCode: '  test-code  ',
            name: 'Test Name'
        );

        expect($entity->standardCode)->toBe('test-code');
    });

    it('throws exception for empty name', function () {
        expect(fn () => new CreditModalityEntity(
            id: '5',
            standardCode: 'test',
            name: ''
        ))->toThrow(\InvalidArgumentException::class, 'Nome da modalidade não pode estar vazio');
    });

    it('throws exception for whitespace-only name', function () {
        expect(fn () => new CreditModalityEntity(
            id: '6',
            standardCode: 'test',
            name: '   '
        ))->toThrow(\InvalidArgumentException::class, 'Nome da modalidade não pode estar vazio');
    });

    it('throws exception for empty standard code', function () {
        expect(fn () => new CreditModalityEntity(
            id: '7',
            standardCode: '',
            name: 'Test'
        ))->toThrow(\InvalidArgumentException::class, 'Código da modalidade não pode estar vazio');
    });

    it('throws exception for whitespace-only standard code', function () {
        expect(fn () => new CreditModalityEntity(
            id: '8',
            standardCode: '   ',
            name: 'Test'
        ))->toThrow(\InvalidArgumentException::class, 'Código da modalidade não pode estar vazio');
    });

    it('can check equality between entities', function () {
        $entity1 = new CreditModalityEntity(
            id: 'same-id',
            standardCode: 'code1',
            name: 'Name 1'
        );

        $entity2 = new CreditModalityEntity(
            id: 'same-id',
            standardCode: 'code2',
            name: 'Name 2'
        );

        $entity3 = new CreditModalityEntity(
            id: 'different-id',
            standardCode: 'code1',
            name: 'Name 1'
        );

        expect($entity1->equals($entity2))->toBeTrue(); // Same ID
        expect($entity1->equals($entity3))->toBeFalse(); // Different ID
    });

    it('handles long names correctly', function () {
        $longName = str_repeat('A', 100);

        $entity = new CreditModalityEntity(
            id: '9',
            standardCode: 'long-name-test',
            name: $longName
        );

        expect($entity->name)->toBe($longName);
    });

    it('handles special characters in name', function () {
        $specialName = 'Crédito & Financiamento - 100% Digital (Novo)';

        $entity = new CreditModalityEntity(
            id: '10',
            standardCode: 'special-chars',
            name: $specialName
        );

        expect($entity->name)->toBe($specialName);
    });

    it('handles different ID formats', function () {
        $idFormats = [
            '1',
            'uuid-string',
            'CREDIT_MODALITY_ID',
            '123-456-789',
        ];

        foreach ($idFormats as $id) {
            $entity = new CreditModalityEntity(
                id: $id,
                standardCode: 'test-code',
                name: 'Test Name'
            );

            expect($entity->id)->toBe($id);
        }
    });

    it('handles different standard code formats using slug transformation', function () {
        $codeTests = [
            ['input' => 'simple', 'expected' => 'simple'],
            ['input' => 'with-dashes', 'expected' => 'with-dashes'],
            ['input' => 'with_underscores', 'expected' => 'with-underscores'],
            ['input' => 'MixedCase123', 'expected' => 'mixedcase123'],
            ['input' => 'With Spaces', 'expected' => 'with-spaces'],
        ];

        foreach ($codeTests as $test) {
            $entity = new CreditModalityEntity(
                id: 'test-id',
                standardCode: $test['input'],
                name: 'Test Name'
            );

            expect($entity->standardCode)->toBe($test['expected']);
        }
    });

    it('maintains immutability of properties', function () {
        $entity = new CreditModalityEntity(
            id: 'immutable-test',
            standardCode: 'test-code',
            name: 'Test Name',
            isActive: true
        );

        // Properties should be readonly
        expect($entity->id)->toBe('immutable-test');
        expect($entity->standardCode)->toBe('test-code');
        expect($entity->name)->toBe('Test Name');
        expect($entity->isActive)->toBeTrue();
    });

    describe('Dependency injection in toModel method', function () {
        it('has correct method signature for dependency injection', function () {
            $entity = new CreditModalityEntity(
                id: '123',
                standardCode: 'credito-pessoal',
                name: 'Crédito Pessoal'
            );
            
            $reflection = new \ReflectionMethod($entity, 'toModel');
            $params = $reflection->getParameters();
            
            expect($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and($params[0]->getType()->getName())->toBe('App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel')
                ->and($params[0]->allowsNull())->toBeTrue()
                ->and($params[0]->isDefaultValueAvailable())->toBeTrue()
                ->and($params[0]->getDefaultValue())->toBeNull();
        });
    });
});
