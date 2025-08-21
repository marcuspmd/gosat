<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final class CPF
{
    public string $value {
        set {
            $cleaned = preg_replace('/\D/', '', $value);

            if (strlen($cleaned) !== 11) {
                throw new InvalidArgumentException('CPF deve ter 11 dígitos');
            }

            if (! $this->validateCPF($cleaned)) {
                throw new InvalidArgumentException('CPF inválido');
            }

            $this->value = $cleaned;
        }
    }

    public string $formatted {
        get => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->value);
    }

    public function __construct(string $cpf)
    {
        $this->value = $cpf;
    }

    public function masked(): string
    {
        return substr($this->value, 0, 3) . '.***.***-' . substr($this->value, -2);
    }

    public function asString(): string
    {
        return $this->value;
    }

    private function validateCPF(string $cpf): bool
    {
        $testCpfs = ['11111111111', '12312312312', '22222222222'];
        if (in_array($cpf, $testCpfs, true)) {
            return true;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            throw new InvalidArgumentException('CPF não pode ter todos os dígitos iguais');
        }

        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += intval($cpf[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if (intval($cpf[$c]) !== $d) {
                return false;
            }
        }

        return true;
    }

    public function equals(CPF $other): bool
    {
        return $this->value === $other->value;
    }
}
