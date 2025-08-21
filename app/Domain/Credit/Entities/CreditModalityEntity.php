<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;

final class CreditModalityEntity
{
    public string $name {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Nome da modalidade não pode estar vazio');
            }
            $this->name = trim($value);
        }
    }

    public string $standardCode {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Código da modalidade não pode estar vazio');
            }
            $this->standardCode = Str::slug(trim($value));
        }
    }

    public function __construct(
        public string $id,
        string $standardCode,
        string $name,
        public bool $isActive = true,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->name = $name;
        $this->standardCode = $standardCode;
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;
    }

    public static function fromModel(CreditModalityModel $model): self
    {
        return new self(
            id: $model->id,
            standardCode: $model->standard_code,
            name: $model->name,
            isActive: $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public function toModel(): CreditModalityModel
    {
        $model = new CreditModalityModel();
        $model->id = $this->id;
        $model->standard_code = $this->standardCode;
        $model->name = $this->name;
        $model->is_active = $this->isActive;
        $model->created_at = $this->createdAt;
        $model->updated_at = $this->updatedAt;

        return $model;
    }

    public function updateModel(CreditModalityModel $model): void
    {
        $model->standard_code = $this->standardCode;
        $model->name = $this->name;
        $model->is_active = $this->isActive;
        $model->updated_at = $this->updatedAt;
    }

    public function equals(CreditModalityEntity $other): bool
    {
        return $this->id === $other->id;
    }
}
