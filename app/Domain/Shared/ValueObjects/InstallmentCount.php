<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final class InstallmentCount
{
    public int $value {
        set {
            if ($value < 1) {
                throw new InvalidArgumentException('Número de parcelas deve ser maior que zero');
            }

            $this->value = $value;
        }
    }

    public string $formatted {
        get => $this->value . 'x';
    }

    public int $years {
        get => (int) ceil($this->value / 12);
    }

    public string $periodDescription {
        get {
            if ($this->value === 1) {
                return 'À vista';
            }

            if ($this->value < 12) {
                return $this->value . ' ' . ($this->value === 1 ? 'mês' : 'meses');
            }

            $years = $this->years;
            $remainingMonths = $this->value % 12;

            $description = $years . ' ' . ($years === 1 ? 'ano' : 'anos');

            if ($remainingMonths > 0) {
                $description .= ' e ' . $remainingMonths . ' ' . ($remainingMonths === 1 ? 'mês' : 'meses');
            }

            return $description;
        }
    }

    public function __construct(int $installments)
    {
        $this->value = $installments;
    }

    public function isShortTerm(): bool
    {
        return $this->value <= 12;
    }

    public function isMediumTerm(): bool
    {
        return $this->value > 12 && $this->value <= 36;
    }

    public function isLongTerm(): bool
    {
        return $this->value > 36;
    }

    public function equals(InstallmentCount $other): bool
    {
        return $this->value === $other->value;
    }

    public function isGreaterThan(InstallmentCount $other): bool
    {
        return $this->value > $other->value;
    }

    public function isLessThan(InstallmentCount $other): bool
    {
        return $this->value < $other->value;
    }
}
