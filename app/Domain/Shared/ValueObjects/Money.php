<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final class Money
{
    public int $amountInCents {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('Valor monetário não pode ser negativo');
            }
            $this->amountInCents = $value;
        }
    }

    public float $value {
        get => $this->amountInCents / 100;
    }

    public string $formatted {
        get => 'R$ ' . number_format($this->value, 2, ',', '.');
    }

    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Valor monetário não pode ser negativo');
        }

        $this->amountInCents = (int) round($value * 100);
    }

    public static function fromCents(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Valor em centavos não pode ser negativo');
        }

        $instance = new self(0);
        $instance->amountInCents = $cents;

        return $instance;
    }

    public function add(Money $other): Money
    {
        return self::fromCents($this->amountInCents + $other->amountInCents);
    }

    public function subtract(Money $other): Money
    {
        $result = $this->amountInCents - $other->amountInCents;

        if ($result < 0) {
            throw new InvalidArgumentException('Resultado da subtração não pode ser negativo');
        }

        return self::fromCents($result);
    }

    public function multiply(float $multiplier): Money
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplicador não pode ser negativo');
        }

        return self::fromCents((int) round($this->amountInCents * $multiplier));
    }

    public function divide(float $divisor): Money
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Divisor deve ser maior que zero');
        }

        return self::fromCents((int) round($this->amountInCents / $divisor));
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amountInCents > $other->amountInCents;
    }

    public function isLessThan(Money $other): bool
    {
        return $this->amountInCents < $other->amountInCents;
    }

    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents;
    }
}
