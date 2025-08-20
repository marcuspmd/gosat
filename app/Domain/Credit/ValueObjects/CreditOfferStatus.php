<?php

declare(strict_types=1);

namespace App\Domain\Credit\ValueObjects;

enum CreditOfferStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::COMPLETED => 'Concluído',
            self::FAILED => 'Falhou',
            self::EXPIRED => 'Expirado',
        };
    }

    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::EXPIRED => true,
            self::PENDING, self::PROCESSING => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canRetry(): bool
    {
        return match ($this) {
            self::FAILED, self::EXPIRED => true,
            self::PENDING, self::PROCESSING, self::COMPLETED => false,
        };
    }
}
