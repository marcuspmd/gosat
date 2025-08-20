<?php

declare(strict_types=1);

namespace App\Domain\Integration\Entities;

use App\Domain\Credit\Entities\StandardModalityEntity;
use DateTimeImmutable;
use InvalidArgumentException;

final class ModalityMappingEntity
{
    public string $externalCode {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Código externo não pode estar vazio');
            }
            $this->externalCode = trim($value);
        }
    }

    public string $modalityName {
        set {
            if (empty(trim($value))) {
                throw new InvalidArgumentException('Nome da modalidade não pode estar vazio');
            }
            $this->modalityName = trim($value);
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
        public string $institutionId,
        string $externalCode,
        public StandardModalityEntity $standardModality,
        string $modalityName,
        public ?string $institutionExternalId = null,
        public ?string $originalModalityName = null,
        bool $isActive = true,
        public ?DateTimeImmutable $lastSeenAt = null,
        public ?array $metadata = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null
    ) {
        $this->externalCode = $externalCode;
        $this->modalityName = $modalityName;
        $this->isActive = $isActive;
        $this->lastSeenAt ??= new DateTimeImmutable;
        $this->createdAt ??= new DateTimeImmutable;
        $this->updatedAt ??= new DateTimeImmutable;
    }

    private function copyWith(
        ?string $modalityName = null,
        ?StandardModalityEntity $standardModality = null,
        ?string $institutionExternalId = null,
        ?string $originalModalityName = null,
        ?bool $isActive = null,
        ?array $metadata = null
    ): self {
        return new self(
            $this->id,
            $this->institutionId,
            $this->externalCode,
            $standardModality ?? $this->standardModality,
            $modalityName ?? $this->modalityName,
            $institutionExternalId ?? $this->institutionExternalId,
            $originalModalityName ?? $this->originalModalityName,
            $isActive ?? $this->isActive,
            new DateTimeImmutable,
            $metadata ?? $this->metadata,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function matches(string $institutionId, string $externalCode): bool
    {
        return $this->institutionId === $institutionId && $this->externalCode === $externalCode;
    }

    public function matchesExternal(string $institutionExternalId, string $externalCode): bool
    {
        return $this->institutionExternalId === $institutionExternalId && $this->externalCode === $externalCode;
    }

    public function isRecentlySeen(int $daysThreshold = 30): bool
    {
        if (! $this->lastSeenAt) {
            return false;
        }

        $threshold = new DateTimeImmutable("-{$daysThreshold} days");

        return $this->lastSeenAt >= $threshold;
    }

    public function equals(ModalityMappingEntity $other): bool
    {
        return $this->id === $other->id;
    }
}
