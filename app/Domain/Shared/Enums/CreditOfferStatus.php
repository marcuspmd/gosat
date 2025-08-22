<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum CreditOfferStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativa',
            self::INACTIVE => 'Inativa',
            self::ERROR => 'Erro',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function hasError(): bool
    {
        return $this === self::ERROR;
    }
}
