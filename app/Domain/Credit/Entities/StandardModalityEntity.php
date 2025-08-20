<?php

declare(strict_types=1);

namespace App\Domain\Credit\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

final class StandardModalityEntity
{
    public string $code {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Código padrão não pode estar vazio');
            }
            $this->code = strtoupper(trim($value));
        }
    }

    public string $name {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Nome da modalidade não pode estar vazio');
            }
            $this->name = trim($value);
        }
    }

    public string $riskLevel {
        set {
            if (! in_array($value, ['low', 'medium', 'high'])) {
                throw new InvalidArgumentException('Nível de risco deve ser: low, medium ou high');
            }
            $this->riskLevel = $value;
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
        string $code,
        string $name,
        public ?string $description = null,
        string $riskLevel = 'medium',
        public ?array $typicalInterestRange = null,
        public ?array $keywords = null,
        bool $isActive = true,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->riskLevel = $riskLevel;
        $this->isActive = $isActive;
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;
    }

    public function copyWith(
        ?string $name = null,
        ?string $description = null,
        ?string $riskLevel = null,
        ?array $typicalInterestRange = null,
        ?array $keywords = null,
        ?bool $isActive = null
    ): self {
        return new self(
            $this->id,
            $this->code,
            $name ?? $this->name,
            $description ?? $this->description,
            $riskLevel ?? $this->riskLevel,
            $typicalInterestRange ?? $this->typicalInterestRange,
            $keywords ?? $this->keywords,
            $isActive ?? $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function equals(StandardModalityEntity $other): bool
    {
        return $this->code === $other->code;
    }
}
