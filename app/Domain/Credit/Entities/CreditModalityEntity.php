<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use App\Domain\Credit\ValueObjects\StandardModalityCode;
use DateTimeImmutable;
use InvalidArgumentException;

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

    public bool $isActive {
        set {
            $this->isActive = $value;
        }
        get => $this->isActive;
    }

    public function __construct(
        public string $id,
        public StandardModalityCode $standardCode,
        string $name,
        public ?string $description = null,
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
        ?string $description = null,
        ?bool $isActive = null
    ): self {
        return new self(
            $this->id,
            $this->standardCode,
            $this->name,
            $description ?? $this->description,
            $isActive ?? $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function equals(CreditModalityEntity $other): bool
    {
        return $this->id === $other->id;
    }
}
