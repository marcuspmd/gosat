<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInstitutionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentInstitutionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentInstitutionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentInstitutionRepository;
    }

    public function test_implements_institution_repository_interface(): void
    {
        $this->assertInstanceOf(InstitutionRepositoryInterface::class, $this->repository);
    }

    public function test_has_correct_class_structure(): void
    {
        $reflection = new \ReflectionClass(EloquentInstitutionRepository::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->implementsInterface(InstitutionRepositoryInterface::class));
    }

    public function test_find_by_id_returns_null_when_institution_not_found(): void
    {
        $result = $this->repository->findById('non-existent-id');

        $this->assertNull($result);
    }

    public function test_find_by_id_returns_institution_entity_when_institution_found(): void
    {
        // Create an institution model in database
        InstitutionModel::create([
            'id' => 'test-institution-id',
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'is_active' => true,
        ]);

        $result = $this->repository->findById('test-institution-id');

        $this->assertInstanceOf(InstitutionEntity::class, $result);
        $this->assertEquals('test-institution-id', $result->id);
        $this->assertEquals('Test Bank', $result->name);
        $this->assertEquals('test-bank', $result->slug);
        $this->assertTrue($result->isActive);
    }

    public function test_find_by_id_handles_different_id_formats(): void
    {
        $testIds = [
            'simple-id',
            'uuid-12345678-1234-1234-1234-123456789012',
            '123456789',
            'very-long-id-with-many-characters',
        ];

        foreach ($testIds as $index => $testId) {
            InstitutionModel::create([
                'id' => $testId,
                'name' => "Institution {$index}",
                'slug' => "institution-{$index}",
                'is_active' => true,
            ]);

            $result = $this->repository->findById($testId);

            $this->assertInstanceOf(InstitutionEntity::class, $result);
            $this->assertEquals($testId, $result->id);
            $this->assertEquals("Institution {$index}", $result->name);
        }
    }

    public function test_find_by_id_returns_inactive_institutions_correctly(): void
    {
        InstitutionModel::create([
            'id' => 'inactive-institution',
            'name' => 'Inactive Bank',
            'slug' => 'inactive-bank',
            'is_active' => false,
        ]);

        $result = $this->repository->findById('inactive-institution');

        $this->assertInstanceOf(InstitutionEntity::class, $result);
        $this->assertEquals('inactive-institution', $result->id);
        $this->assertEquals('Inactive Bank', $result->name);
        $this->assertFalse($result->isActive);
    }

    public function test_find_by_slug_returns_null_when_institution_with_slug_not_found(): void
    {
        $result = $this->repository->findBySlug('non-existent-slug');

        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_institution_entity_when_institution_with_slug_found(): void
    {
        InstitutionModel::create([
            'id' => 'slug-test-id',
            'name' => 'Slug Test Bank',
            'slug' => 'slug-test-bank',
            'is_active' => true,
        ]);

        $result = $this->repository->findBySlug('slug-test-bank');

        $this->assertInstanceOf(InstitutionEntity::class, $result);
        $this->assertEquals('slug-test-id', $result->id);
        $this->assertEquals('Slug Test Bank', $result->name);
        $this->assertEquals('slug-test-bank', $result->slug);
        $this->assertTrue($result->isActive);
    }

    public function test_find_by_slug_handles_different_slug_formats(): void
    {
        $slugData = [
            ['banco-do-brasil', 'Banco do Brasil'],
            ['caixa-economica-federal', 'Caixa Econômica Federal'],
            ['bradesco-sa', 'Bradesco S.A.'],
        ];

        foreach ($slugData as $index => [$slug, $name]) {
            InstitutionModel::create([
                'id' => "slug-format-test-{$index}",
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
            ]);

            $result = $this->repository->findBySlug($slug);

            $this->assertInstanceOf(InstitutionEntity::class, $result);
            $this->assertEquals($name, $result->name);
            $this->assertEquals($slug, $result->slug);
        }
    }

    public function test_find_by_slug_finds_inactive_institutions(): void
    {
        InstitutionModel::create([
            'id' => 'inactive-slug-institution',
            'name' => 'Inactive Slug Bank',
            'slug' => 'inactive-slug-bank',
            'is_active' => false,
        ]);

        $result = $this->repository->findBySlug('inactive-slug-bank');

        $this->assertInstanceOf(InstitutionEntity::class, $result);
        $this->assertEquals('inactive-slug-institution', $result->id);
        $this->assertEquals('Inactive Slug Bank', $result->name);
        $this->assertFalse($result->isActive);
    }

    public function test_save_creates_new_institution_when_institution_does_not_exist(): void
    {
        $institution = new InstitutionEntity(
            id: 'new-institution-id',
            institutionId: 1,
            name: 'New Bank',
            isActive: true
        );

        // Verify institution doesn't exist before save
        $this->assertNull(InstitutionModel::find('new-institution-id'));

        $this->repository->save($institution);

        // Verify institution was created
        $savedModel = InstitutionModel::find('new-institution-id');
        $this->assertInstanceOf(InstitutionModel::class, $savedModel, 'Institution should have been saved to database');

        $this->assertEquals('New Bank', $savedModel->name);
        $this->assertEquals('new-bank', $savedModel->slug);
        $this->assertTrue($savedModel->is_active);
    }

    public function test_save_updates_existing_institution_when_institution_exists(): void
    {
        // Create existing institution
        InstitutionModel::create([
            'id' => 'existing-institution-id',
            'name' => 'Original Bank',
            'slug' => 'original-bank',
            'is_active' => true,
        ]);

        // Create updated entity
        $updatedInstitution = new InstitutionEntity(
            id: 'existing-institution-id',
            institutionId: 1,
            name: 'Updated Bank',
            isActive: false
        );

        $this->repository->save($updatedInstitution);

        // Verify institution was updated
        $savedModel = InstitutionModel::find('existing-institution-id');
        $this->assertNotNull($savedModel);
        $this->assertEquals('Updated Bank', $savedModel->name);
        $this->assertEquals('updated-bank', $savedModel->slug);
        $this->assertFalse($savedModel->is_active);
    }

    public function test_save_persists_institution_with_all_required_fields(): void
    {
        $institution = new InstitutionEntity(
            id: 'complete-institution-id',
            institutionId: 2,
            name: 'Complete Bank',
            isActive: true
        );

        $this->repository->save($institution);

        $savedModel = InstitutionModel::find('complete-institution-id');
        $this->assertNotNull($savedModel);
        $this->assertEquals('complete-institution-id', $savedModel->id);
        $this->assertEquals('Complete Bank', $savedModel->name);
        $this->assertEquals('complete-bank', $savedModel->slug);
        $this->assertTrue($savedModel->is_active);
        $this->assertNotNull($savedModel->created_at);
        $this->assertNotNull($savedModel->updated_at);
    }

    public function test_save_handles_names_with_special_characters(): void
    {
        $institution = new InstitutionEntity(
            id: 'special-chars-id',
            institutionId: 3,
            name: 'Banco José & Cia.',
            isActive: true
        );

        $this->repository->save($institution);

        $savedModel = InstitutionModel::find('special-chars-id');
        $this->assertNotNull($savedModel);
        $this->assertEquals('Banco José & Cia.', $savedModel->name);
        $this->assertEquals('banco-jose-cia', $savedModel->slug);
    }

    public function test_delete_removes_institution_from_database(): void
    {
        // Create institution to delete
        InstitutionModel::create([
            'id' => 'institution-to-delete',
            'name' => 'Bank to Delete',
            'slug' => 'bank-to-delete',
            'is_active' => true,
        ]);

        // Verify institution exists
        $this->assertNotNull(InstitutionModel::find('institution-to-delete'));

        $this->repository->delete('institution-to-delete');

        // Verify institution was deleted
        $this->assertNull(InstitutionModel::find('institution-to-delete'));
    }

    public function test_delete_handles_non_existent_institution_gracefully(): void
    {
        // This should not throw an exception
        $this->repository->delete('non-existent-institution');

        // Verify no exception was thrown and operation completed
        $this->addToAssertionCount(1);
    }

    public function test_can_save_and_retrieve_institution_in_sequence(): void
    {
        $originalInstitution = new InstitutionEntity(
            id: 'integration-test-id',
            institutionId: 4,
            name: 'Integration Test Bank',
            isActive: true
        );

        // Save institution
        $this->repository->save($originalInstitution);

        // Retrieve by ID
        $retrievedById = $this->repository->findById('integration-test-id');
        $this->assertNotNull($retrievedById);
        $this->assertEquals('Integration Test Bank', $retrievedById->name);
        $this->assertTrue($retrievedById->isActive);

        // Retrieve by slug
        $retrievedBySlug = $this->repository->findBySlug('integration-test-bank');
        $this->assertNotNull($retrievedBySlug);
        $this->assertEquals('integration-test-id', $retrievedBySlug->id);
        $this->assertEquals('Integration Test Bank', $retrievedBySlug->name);
    }

    public function test_handles_save_update_retrieve_cycle(): void
    {
        // Initial save
        $institution = new InstitutionEntity(
            id: 'cycle-test-id',
            institutionId: 5,
            name: 'Initial Bank Name',
            isActive: true
        );
        $this->repository->save($institution);

        // Update
        $updatedInstitution = new InstitutionEntity(
            id: 'cycle-test-id',
            institutionId: 5,
            name: 'Updated Bank Name',
            isActive: false
        );
        $this->repository->save($updatedInstitution);

        // Retrieve and verify
        $finalInstitution = $this->repository->findById('cycle-test-id');
        $this->assertNotNull($finalInstitution);
        $this->assertEquals('Updated Bank Name', $finalInstitution->name);
        $this->assertEquals('updated-bank-name', $finalInstitution->slug);
        $this->assertFalse($finalInstitution->isActive);
    }

    public function test_handles_empty_string_id_gracefully(): void
    {
        $result = $this->repository->findById('');

        $this->assertNull($result);
    }

    public function test_handles_empty_string_slug_gracefully(): void
    {
        $result = $this->repository->findBySlug('');

        $this->assertNull($result);
    }

    public function test_handles_whitespace_in_id_gracefully(): void
    {
        $result = $this->repository->findById('   ');

        $this->assertNull($result);
    }

    public function test_handles_whitespace_in_slug_gracefully(): void
    {
        $result = $this->repository->findBySlug('   ');

        $this->assertNull($result);
    }

    public function test_returns_consistent_results_for_repeated_calls(): void
    {
        InstitutionModel::create([
            'id' => 'consistent-test-id',
            'name' => 'Consistent Test Bank',
            'slug' => 'consistent-test-bank',
            'is_active' => true,
        ]);

        $result1 = $this->repository->findById('consistent-test-id');
        $result2 = $this->repository->findById('consistent-test-id');
        $result3 = $this->repository->findBySlug('consistent-test-bank');

        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
        $this->assertNotNull($result3);
        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals($result1->id, $result3->id);
        $this->assertEquals($result1->name, $result2->name);
        $this->assertEquals($result1->name, $result3->name);
        $this->assertEquals($result1->isActive, $result2->isActive);
        $this->assertEquals($result1->isActive, $result3->isActive);
    }
}
