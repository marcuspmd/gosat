<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditModalityModelFactory extends Factory
{
    protected $model = CreditModalityModel::class;

    public function definition(): array
    {
        $modalities = [
            'Crédito Pessoal',
            'Crédito Consignado',
            'Cartão de Crédito',
            'Conta Corrente',
            'Crédito Imobiliário',
        ];

        return [
            'name' => $this->faker->randomElement($modalities),
            'standard_code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{2}'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn () => ['standard_code' => $code]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn () => ['name' => $name]);
    }
}
