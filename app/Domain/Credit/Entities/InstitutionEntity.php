<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use Illuminate\Support\Str;

final class InstitutionEntity
{
    public string $name {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Nome da instituição não pode estar vazio');
            }
            $this->name = trim($value);
        }
    }

    public string $slug {
        get => Str::slug($this->name);
    }

    public bool $isActive {
        set {
            $this->isActive = $value;
        }
        get => $this->isActive;
    }

    public function __construct(
        public string $id,
        public int $institutionId,
        string $name,
        bool $isActive = true,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->name = $name;
        $this->isActive = $isActive;
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;
    }

    public function copyWith(
        ?int $institutionId = null,
        ?string $name = null,
        ?bool $isActive = null
    ): self {
        return new self(
            $this->id,
            $institutionId ?? $this->institutionId,
            $name ?? $this->name,
            $isActive ?? $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function equals(InstitutionEntity $other): bool
    {
        return $this->id === $other->id;
    }

    public static function fromModel(InstitutionModel $model): self
    {
        return new self(
            id: $model->id,
            institutionId: (int) $model->id, // Assuming we use the model ID as institution ID
            name: $model->name,
            isActive: $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public function toModel(): InstitutionModel
    {
        $model = new InstitutionModel();
        $model->id = $this->id;
        $model->name = $this->name;
        $model->slug = $this->slug;
        $model->is_active = $this->isActive;
        $model->created_at = $this->createdAt;
        $model->updated_at = $this->updatedAt;
        
        return $model;
    }

    public function updateModel(InstitutionModel $model): void
    {
        $model->name = $this->name;
        $model->slug = $this->slug;
        $model->is_active = $this->isActive;
        $model->updated_at = $this->updatedAt;
    }
}
