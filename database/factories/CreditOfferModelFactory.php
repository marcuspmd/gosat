<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditOfferModelFactory extends Factory
{
    protected $model = CreditOfferModel::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerModel::factory(),
            'institution_id' => InstitutionModel::factory(),
            'modality_id' => CreditModalityModel::factory(),
            'min_amount_cents' => $this->faker->numberBetween(10000, 100000), // R$ 100 - R$ 1.000
            'max_amount_cents' => $this->faker->numberBetween(500000, 10000000), // R$ 5.000 - R$ 100.000
            'monthly_interest_rate' => $this->faker->randomFloat(4, 0.005, 0.05), // 0.5% a 5%
            'min_installments' => $this->faker->numberBetween(6, 12),
            'max_installments' => $this->faker->numberBetween(24, 60),
            'request_id' => $this->faker->uuid(),
            'error_message' => null,
        ];
    }

    public function withError(): static
    {
        return $this->state(fn () => [
            'error_message' => $this->faker->sentence(),
            // Manter valores válidos mesmo com erro para evitar constraint violations
            'min_amount_cents' => $this->faker->numberBetween(10000, 100000),
            'max_amount_cents' => $this->faker->numberBetween(500000, 10000000),
            'monthly_interest_rate' => $this->faker->randomFloat(4, 0.005, 0.05),
            'min_installments' => $this->faker->numberBetween(6, 12),
            'max_installments' => $this->faker->numberBetween(24, 60),
        ]);
    }

    public function forCustomer(CustomerModel $customer): static
    {
        return $this->state(fn () => ['customer_id' => $customer->id]);
    }

    public function withRequestId(string $requestId): static
    {
        return $this->state(fn () => ['request_id' => $requestId]);
    }
}
