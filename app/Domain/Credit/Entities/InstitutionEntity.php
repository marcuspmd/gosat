<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

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
        get => strtolower(str_replace([' ', '-'], '_', preg_replace('/[^a-zA-Z0-9\s-]/', '', $this->name)));
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
        public ?string $website = null,
        public ?string $logoUrl = null,
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
        ?string $website = null,
        ?string $logoUrl = null,
        ?bool $isActive = null
    ): self {
        return new self(
            $this->id,
            $institutionId ?? $this->institutionId,
            $name ?? $this->name,
            $website ?? $this->website,
            $logoUrl ?? $this->logoUrl,
            $isActive ?? $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function equals(InstitutionEntity $other): bool
    {
        return $this->id === $other->id;
    }
}
