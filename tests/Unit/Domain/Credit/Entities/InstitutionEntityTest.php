<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\InstitutionEntity;

describe('InstitutionEntity', function () {
    it('can be created with valid parameters', function () {
        $entity = new InstitutionEntity(
            id: 'inst-123',
            institutionId: 456,
            name: 'Banco Central do Brasil'
        );

        expect($entity->id)->toBe('inst-123')
            ->and($entity->institutionId)->toBe(456)
            ->and($entity->name)->toBe('Banco Central do Brasil')
            ->and($entity->isActive)->toBeTrue()
            ->and($entity->slug)->toBe('banco-central-do-brasil')
            ->and($entity->createdAt)->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($entity->updatedAt)->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('can be created with custom dates and inactive status', function () {
        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-02 15:30:00');

        $entity = new InstitutionEntity(
            id: 'inst-456',
            institutionId: 789,
            name: 'Banco Inativo',
            isActive: false,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        expect($entity->id)->toBe('inst-456')
            ->and($entity->institutionId)->toBe(789)
            ->and($entity->name)->toBe('Banco Inativo')
            ->and($entity->isActive)->toBeFalse()
            ->and($entity->createdAt)->toBe($createdAt)
            ->and($entity->updatedAt)->toBe($updatedAt);
    });

    it('trims and validates name input', function () {
        $entity = new InstitutionEntity(
            id: 'inst-trim',
            institutionId: 100,
            name: '  Banco com Espaços  '
        );

        expect($entity->name)->toBe('Banco com Espaços');
    });

    it('generates slug from name', function () {
        $testCases = [
            ['name' => 'Banco do Brasil', 'expectedSlug' => 'banco-do-brasil'],
            ['name' => 'Caixa Econômica Federal', 'expectedSlug' => 'caixa-economica-federal'],
            ['name' => 'Banco & Financeira', 'expectedSlug' => 'banco-financeira'],
            ['name' => 'BANCO UPPERCASE', 'expectedSlug' => 'banco-uppercase'],
            ['name' => 'Banco_com_Underscore', 'expectedSlug' => 'banco-com-underscore'],
        ];

        foreach ($testCases as $case) {
            $entity = new InstitutionEntity(
                id: 'slug-test-' . md5($case['name']),
                institutionId: 999,
                name: $case['name']
            );

            expect($entity->slug)->toBe($case['expectedSlug']);
        }
    });

    it('throws exception for empty name', function () {
        expect(fn () => new InstitutionEntity(
            id: 'empty-name',
            institutionId: 123,
            name: ''
        ))->toThrow(\InvalidArgumentException::class, 'Nome da instituição não pode estar vazio');
    });

    it('throws exception for whitespace-only name', function () {
        expect(fn () => new InstitutionEntity(
            id: 'whitespace-name',
            institutionId: 123,
            name: '   '
        ))->toThrow(\InvalidArgumentException::class, 'Nome da instituição não pode estar vazio');
    });

    it('can check equality between entities', function () {
        $entity1 = new InstitutionEntity(
            id: 'same-id',
            institutionId: 123,
            name: 'Banco A'
        );

        $entity2 = new InstitutionEntity(
            id: 'same-id',
            institutionId: 456,
            name: 'Banco B'
        );

        $entity3 = new InstitutionEntity(
            id: 'different-id',
            institutionId: 123,
            name: 'Banco A'
        );

        expect($entity1->equals($entity2))->toBeTrue(); // Same ID
        expect($entity1->equals($entity3))->toBeFalse(); // Different ID
    });

    it('can create copy with modified attributes using copyWith', function () {
        $original = new InstitutionEntity(
            id: 'original',
            institutionId: 123,
            name: 'Banco Original',
            isActive: true
        );

        $modified = $original->copyWith(
            institutionId: 456,
            name: 'Banco Modificado',
            isActive: false
        );

        // Original should remain unchanged
        expect($original->id)->toBe('original')
            ->and($original->institutionId)->toBe(123)
            ->and($original->name)->toBe('Banco Original')
            ->and($original->isActive)->toBeTrue();

        // Modified should have new values
        expect($modified->id)->toBe('original') // ID should remain the same
            ->and($modified->institutionId)->toBe(456)
            ->and($modified->name)->toBe('Banco Modificado')
            ->and($modified->isActive)->toBeFalse();
    });

    it('copyWith preserves original values when null is passed', function () {
        $original = new InstitutionEntity(
            id: 'preserve-test',
            institutionId: 789,
            name: 'Banco Original'
        );

        $copy = $original->copyWith();

        expect($copy->id)->toBe($original->id)
            ->and($copy->institutionId)->toBe($original->institutionId)
            ->and($copy->name)->toBe($original->name)
            ->and($copy->isActive)->toBe($original->isActive);
    });

    it('handles different institution ID values', function () {
        $idTests = [
            1,
            999999,
            0, // Edge case: zero ID
        ];

        foreach ($idTests as $institutionId) {
            $entity = new InstitutionEntity(
                id: "id-test-{$institutionId}",
                institutionId: $institutionId,
                name: 'Test Bank'
            );

            expect($entity->institutionId)->toBe($institutionId);
        }
    });

    it('handles long institution names', function () {
        $longName = str_repeat('Banco ', 20) . 'Limitada'; // Very long name

        $entity = new InstitutionEntity(
            id: 'long-name',
            institutionId: 123,
            name: $longName
        );

        expect($entity->name)->toBe($longName)
            ->and(strlen($entity->slug))->toBeGreaterThan(0); // Should still generate slug
    });

    it('handles special characters in name correctly', function () {
        $specialNames = [
            'Banco São Paulo & Cia',
            'Crédit Agricole Brasil',
            'Banco (Internacional)',
            'Banco "Especial"',
            "Banco d'Ouro",
        ];

        foreach ($specialNames as $name) {
            $entity = new InstitutionEntity(
                id: 'special-' . md5($name),
                institutionId: 123,
                name: $name
            );

            expect($entity->name)->toBe($name)
                ->and(strlen($entity->slug))->toBeGreaterThan(0);
        }
    });

    it('maintains immutability of core properties', function () {
        $entity = new InstitutionEntity(
            id: 'immutable-test',
            institutionId: 123,
            name: 'Banco Imutável'
        );

        // Properties should be readonly or have controlled setters
        expect($entity->id)->toBe('immutable-test')
            ->and($entity->institutionId)->toBe(123)
            ->and($entity->name)->toBe('Banco Imutável');
    });

    it('uses current timestamp when dates are not provided', function () {
        $beforeCreation = new \DateTimeImmutable;

        $entity = new InstitutionEntity(
            id: 'timestamp-test',
            institutionId: 456,
            name: 'Banco Timestamp'
        );

        $afterCreation = new \DateTimeImmutable;

        expect($entity->createdAt)->toBeGreaterThanOrEqual($beforeCreation)
            ->and($entity->createdAt)->toBeLessThanOrEqual($afterCreation)
            ->and($entity->updatedAt)->toBeGreaterThanOrEqual($beforeCreation)
            ->and($entity->updatedAt)->toBeLessThanOrEqual($afterCreation);
    });

    it('can toggle active status', function () {
        $activeEntity = new InstitutionEntity(
            id: 'status-test',
            institutionId: 123,
            name: 'Banco Ativo',
            isActive: true
        );

        $inactiveEntity = new InstitutionEntity(
            id: 'status-test-2',
            institutionId: 456,
            name: 'Banco Inativo',
            isActive: false
        );

        expect($activeEntity->isActive)->toBeTrue()
            ->and($inactiveEntity->isActive)->toBeFalse();
    });

    describe('Dependency injection in toModel method', function () {
        it('has correct method signature for dependency injection', function () {
            $entity = new InstitutionEntity(
                id: '123',
                institutionId: 1,
                name: 'Test Institution'
            );
            
            $reflection = new \ReflectionMethod($entity, 'toModel');
            $params = $reflection->getParameters();
            
            expect($params)->toHaveCount(1)
                ->and($params[0]->getName())->toBe('model')
                ->and($params[0]->hasType())->toBeTrue()
                ->and($params[0]->getType()->getName())->toBe('App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel')
                ->and($params[0]->allowsNull())->toBeTrue()
                ->and($params[0]->isDefaultValueAvailable())->toBeTrue()
                ->and($params[0]->getDefaultValue())->toBeNull();
        });
    });
});
