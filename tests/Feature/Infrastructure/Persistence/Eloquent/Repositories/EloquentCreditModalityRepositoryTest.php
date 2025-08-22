<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditModalityRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new EloquentCreditModalityRepository;
});

describe('EloquentCreditModalityRepository', function () {
    it('implements credit modality repository interface', function () {
        expect($this->repository)->toBeInstanceOf(CreditModalityRepositoryInterface::class);
    });

    it('has correct class structure', function () {
        expect(method_exists($this->repository, 'findById'))->toBeTrue();
        expect(method_exists($this->repository, 'findBySlug'))->toBeTrue();
        expect(method_exists($this->repository, 'save'))->toBeTrue();
        expect(method_exists($this->repository, 'delete'))->toBeTrue();
    });
});

describe('findById', function () {
    it('returns null when credit modality not found', function () {
        $result = $this->repository->findById('non-existent-id');
        expect($result)->toBeNull();
    });

    it('returns credit modality entity when credit modality found', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $result = $this->repository->findById($modality->id);

        expect($result)->toBeInstanceOf(CreditModalityEntity::class);
        expect($result->id)->toBe($modality->id);
        expect($result->standardCode)->toBe('personal-credit');
        expect($result->name)->toBe('Personal Credit');
        expect($result->isActive)->toBeTrue();
    });

    it('handles different id formats', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        // Test with exact case
        $result = $this->repository->findById($modality->id);
        expect($result)->toBeInstanceOf(CreditModalityEntity::class);

        // Test that search is case sensitive (as expected)
        $result = $this->repository->findById(strtoupper((string) $modality->id));
        expect($result)->toBeNull();
    });

    it('returns inactive credit modalities correctly', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => false,
        ]);

        $result = $this->repository->findById($modality->id);

        expect($result)->toBeInstanceOf(CreditModalityEntity::class);
        expect($result->isActive)->toBeFalse();
    });
});

describe('findBySlug', function () {
    it('returns null when credit modality with slug not found', function () {
        $result = $this->repository->findBySlug('non-existent-slug');
        expect($result)->toBeNull();
    });

    it('returns credit modality entity when credit modality with slug found', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $result = $this->repository->findBySlug('personal-credit');

        expect($result)->toBeInstanceOf(CreditModalityEntity::class);
        expect($result->id)->toBe($modality->id);
        expect($result->standardCode)->toBe('personal-credit');
        expect($result->name)->toBe('Personal Credit');
    });

    it('handles different slug formats', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        // Test with uppercase slug
        $result = $this->repository->findBySlug('PERSONAL-CREDIT');
        expect($result)->toBeNull(); // Should be null because byStandardCode is case-sensitive

        // Test with exact match
        $result = $this->repository->findBySlug('personal-credit');
        expect($result)->toBeInstanceOf(CreditModalityEntity::class);
    });

    it('finds inactive credit modalities', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => false,
        ]);

        $result = $this->repository->findBySlug('personal-credit');

        expect($result)->toBeInstanceOf(CreditModalityEntity::class);
        expect($result->isActive)->toBeFalse();
    });
});

describe('save', function () {
    it('creates new credit modality when credit modality does not exist', function () {
        $modalityEntity = new CreditModalityEntity(
            id: '123e4567-e89b-12d3-a456-426614174000',
            standardCode: 'personal-credit',
            name: 'Personal Credit',
            isActive: true
        );

        $this->repository->save($modalityEntity);

        $model = CreditModalityModel::find($modalityEntity->id);
        expect($model)->not()->toBeNull();
        expect($model->standard_code)->toBe('personal-credit');
        expect($model->name)->toBe('Personal Credit');
        expect($model->is_active)->toBeTrue();
    });

    it('updates existing credit modality when credit modality exists', function () {
        $originalModel = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $updatedEntity = new CreditModalityEntity(
            id: (string) $originalModel->id,
            standardCode: 'business-credit',
            name: 'Business Credit',
            isActive: false
        );

        $this->repository->save($updatedEntity);

        $model = CreditModalityModel::find($originalModel->id);
        expect($model)->not()->toBeNull();
        expect($model->standard_code)->toBe('business-credit');
        expect($model->name)->toBe('Business Credit');
        expect($model->is_active)->toBeFalse();
    });

    it('persists credit modality with all required fields', function () {
        $modalityEntity = new CreditModalityEntity(
            id: '123e4567-e89b-12d3-a456-426614174000',
            standardCode: 'personal-credit',
            name: 'Personal Credit',
            isActive: true
        );

        $this->repository->save($modalityEntity);

        $model = CreditModalityModel::find($modalityEntity->id);
        expect($model->id)->toBe($modalityEntity->id);
        expect($model->standard_code)->toBe('personal-credit');
        expect($model->name)->toBe('Personal Credit');
        expect($model->is_active)->toBeTrue();
        expect($model->created_at)->not()->toBeNull();
        expect($model->updated_at)->not()->toBeNull();
    });

    it('handles names with special characters', function () {
        $modalityEntity = new CreditModalityEntity(
            id: '123e4567-e89b-12d3-a456-426614174000',
            standardCode: 'credito-especial',
            name: 'Crédito Especial & Rápido',
            isActive: true
        );

        $this->repository->save($modalityEntity);

        $model = CreditModalityModel::find($modalityEntity->id);
        expect($model->name)->toBe('Crédito Especial & Rápido');
        expect($model->standard_code)->toBe('credito-especial');
    });
});

describe('delete', function () {
    it('removes credit modality from database', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        expect(CreditModalityModel::find($modality->id))->not()->toBeNull();

        $this->repository->delete($modality->id);

        expect(CreditModalityModel::find($modality->id))->toBeNull();
    });

    it('handles non existent credit modality gracefully', function () {
        $this->repository->delete('non-existent-id');
        expect(true)->toBeTrue(); // Should not throw exception
    });
});

describe('complex operations', function () {
    it('can save and retrieve credit modality in sequence', function () {
        $modalityEntity = new CreditModalityEntity(
            id: '123e4567-e89b-12d3-a456-426614174000',
            standardCode: 'personal-credit',
            name: 'Personal Credit',
            isActive: true
        );

        $this->repository->save($modalityEntity);
        $retrieved = $this->repository->findById($modalityEntity->id);

        expect($retrieved)->toBeInstanceOf(CreditModalityEntity::class);
        expect($retrieved->id)->toBe($modalityEntity->id);
        expect($retrieved->standardCode)->toBe('personal-credit');
        expect($retrieved->name)->toBe('Personal Credit');
    });

    it('handles save update retrieve cycle', function () {
        $modalityEntity = new CreditModalityEntity(
            id: '123e4567-e89b-12d3-a456-426614174000',
            standardCode: 'personal-credit',
            name: 'Personal Credit',
            isActive: true
        );

        // Save
        $this->repository->save($modalityEntity);

        // Update
        $updatedEntity = new CreditModalityEntity(
            id: $modalityEntity->id,
            standardCode: 'business-credit',
            name: 'Business Credit',
            isActive: false
        );
        $this->repository->save($updatedEntity);

        // Retrieve
        $retrieved = $this->repository->findById($modalityEntity->id);

        expect($retrieved->standardCode)->toBe('business-credit');
        expect($retrieved->name)->toBe('Business Credit');
        expect($retrieved->isActive)->toBeFalse();
    });
});

describe('edge cases', function () {
    it('handles empty string id gracefully', function () {
        $result = $this->repository->findById('');
        expect($result)->toBeNull();
    });

    it('handles empty string slug gracefully', function () {
        $result = $this->repository->findBySlug('');
        expect($result)->toBeNull();
    });

    it('handles whitespace in id gracefully', function () {
        $result = $this->repository->findById('   ');
        expect($result)->toBeNull();
    });

    it('handles whitespace in slug gracefully', function () {
        $result = $this->repository->findBySlug('   ');
        expect($result)->toBeNull();
    });

    it('returns consistent results for repeated calls', function () {
        $modality = CreditModalityModel::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'standard_code' => 'personal-credit',
            'name' => 'Personal Credit',
            'is_active' => true,
        ]);

        $result1 = $this->repository->findById($modality->id);
        $result2 = $this->repository->findById($modality->id);

        expect($result1->equals($result2))->toBeTrue();
    });
});
