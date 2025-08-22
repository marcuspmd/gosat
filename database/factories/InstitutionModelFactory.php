<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionModelFactory extends Factory
{
    protected $model = InstitutionModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(),
            'is_active' => $this->faker->boolean(95), // 95% ativo
        ];
    }

    /**
     * Indicate that the institution should have a specific code/slug.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $code,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withSlug(string $slug): static
    {
        return $this->state(fn () => ['slug' => $slug]);
    }
}
