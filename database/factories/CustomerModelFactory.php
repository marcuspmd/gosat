<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerModelFactory extends Factory
{
    protected $model = CustomerModel::class;

    public function definition(): array
    {
        return [
            'cpf' => $this->generateValidCpf(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withSpecificCpf(string $cpf): static
    {
        return $this->state(fn () => ['cpf' => $cpf]);
    }

    private function generateValidCpf(): string
    {
        // Gerar um CPF válido simples para testes
        $cpf = sprintf('%011d', $this->faker->numberBetween(10000000000, 99999999999));

        // Para testes, vamos usar CPFs fixos válidos
        $validCpfs = [
            '12345678909',
            '98765432100',
            '11111111111',
            '22222222222',
            '33333333333',
        ];

        return $this->faker->randomElement($validCpfs);
    }
}
