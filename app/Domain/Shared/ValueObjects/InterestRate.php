<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final class InterestRate
{
    public float $monthlyRate {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('Taxa de juros não pode ser negativa');
            }

            $this->monthlyRate = $value;
        }
    }

    public float $annualRate {
        get => pow(1 + $this->monthlyRate, 12) - 1;
    }

    public string $formattedMonthly {
        get => number_format($this->monthlyRate * 100, 4, ',', '.') . '% a.m.';
    }

    public string $formattedAnnual {
        get => number_format($this->annualRate * 100, 2, ',', '.') . '% a.a.';
    }

    public function __construct(float $monthlyRate)
    {
        $this->monthlyRate = $monthlyRate;
    }

    public static function fromAnnual(float $annualRate): self
    {
        if ($annualRate < 0) {
            throw new InvalidArgumentException('Taxa de juros anual não pode ser negativa');
        }

        $monthlyRate = pow(1 + $annualRate, 1 / 12) - 1;

        return new self($monthlyRate);
    }

    public static function fromPercentage(float $percentage): self
    {
        return new self($percentage / 100);
    }

    public function compound(int $periods): float
    {
        if ($periods < 0) {
            throw new InvalidArgumentException('Número de períodos não pode ser negativo');
        }

        return pow(1 + $this->monthlyRate, $periods);
    }

    public function equals(InterestRate $other): bool
    {
        return abs($this->monthlyRate - $other->monthlyRate) < 0.000001;
    }

    public function isGreaterThan(InterestRate $other): bool
    {
        return $this->monthlyRate > $other->monthlyRate;
    }

    public function isLessThan(InterestRate $other): bool
    {
        return $this->monthlyRate < $other->monthlyRate;
    }
}
