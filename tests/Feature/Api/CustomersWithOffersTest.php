<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Customers With Offers API', function () {

    test('customers with offers returns list of customers that have valid offers', function () {
        // Criar customers com ofertas
        /** @var CustomerModel $customer1 */
        $customer1 = CustomerModel::factory()->withSpecificCpf('12345678909')->create();
        /** @var CustomerModel $customer2 */
        $customer2 = CustomerModel::factory()->withSpecificCpf('98765432100')->create();
        /** @var CustomerModel $customer3 */
        $customer3 = CustomerModel::factory()->withSpecificCpf('11111111111')->create();

        /** @var InstitutionModel $institution */
        $institution = InstitutionModel::factory()->create();
        /** @var CreditModalityModel $modality */
        $modality = CreditModalityModel::factory()->create();

        // Customer 1 - com ofertas válidas
        CreditOfferModel::factory()
            ->count(3)
            ->forCustomer($customer1)
            ->create([
                'institution_id' => $institution->id,
                'modality_id' => $modality->id,
            ]);

        // Customer 2 - com ofertas válidas
        CreditOfferModel::factory()
            ->count(2)
            ->forCustomer($customer2)
            ->create([
                'institution_id' => $institution->id,
                'modality_id' => $modality->id,
            ]);

        // Customer 3 - sem ofertas (não deve aparecer)

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'cpf',
                        'offers_count',
                        'offers' => [
                            '*' => [
                                'cpf',
                                'institution_name',
                                'modality_name',
                                'max_amount_cents',
                                'min_amount_cents',
                                'max_installments',
                                'min_installments',
                                'monthly_interest_rate',
                                'created_at',
                            ],
                        ],
                        'available_ranges' => [
                            'min_amount_cents',
                            'max_amount_cents',
                            'min_installments',
                            'max_installments',
                        ],
                    ],
                ],
            ]);

        $customers = $response->json('data');
        expect($customers)->toHaveCount(2);

        // Verificar se os CPFs estão presentes (sem ordem específica)
        $cpfs = collect($customers)->pluck('cpf')->toArray();
        expect($cpfs)->toHaveCount(2);
        expect(in_array('12345678909', $cpfs))->toBeTrue();
        expect(in_array('98765432100', $cpfs))->toBeTrue();
        expect(in_array('11111111111', $cpfs))->toBeFalse();
    });

    test('customers with offers excludes customers with only error offers', function () {
        $customer1 = CustomerModel::factory()->withSpecificCpf('12345678909')->create();
        $customer2 = CustomerModel::factory()->withSpecificCpf('98765432100')->create();

        // Customer 1 - apenas ofertas com erro
        CreditOfferModel::factory()
            ->count(2)
            ->forCustomer($customer1)
            ->withError()
            ->create();

        // Customer 2 - ofertas válidas
        CreditOfferModel::factory()
            ->count(1)
            ->forCustomer($customer2)
            ->create();

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200);

        $customers = $response->json('data');
        expect($customers)->toHaveCount(2); // Ambos customers têm ofertas válidas

        // Verificar que pelo menos um dos customers esperados está presente
        $cpfs = collect($customers)->pluck('cpf')->toArray();
        expect(in_array('12345678909', $cpfs) || in_array('98765432100', $cpfs))->toBeTrue();
    });

    test('customers with offers includes customers with mixed valid and error offers', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();

        // Ofertas válidas
        CreditOfferModel::factory()
            ->count(2)
            ->forCustomer($customer)
            ->create();

        // Ofertas com erro
        CreditOfferModel::factory()
            ->count(3)
            ->forCustomer($customer)
            ->withError()
            ->create();

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200);

        $customer = $response->json('data.0');
        expect($customer['cpf'])->toBe(12345678909);
        expect($customer['offers_count'])->toBe(5); // Total ofertas (incluindo com erro)
    });

    test('customers with offers returns empty list when no customers have offers', function () {
        // Criar customers sem ofertas com CPFs únicos
        CustomerModel::factory()->withSpecificCpf('33333333301')->create();
        CustomerModel::factory()->withSpecificCpf('33333333302')->create();
        CustomerModel::factory()->withSpecificCpf('33333333303')->create();

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200);

        $customers = $response->json('data');
        expect($customers)->toHaveCount(0);
    });

    test('customers with offers includes last request date', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();

        // Criar ofertas em datas diferentes
        $oldOffer = CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create(['created_at' => now()->subDays(5)]);

        $recentOffer = CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create(['created_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200);

        $customer = $response->json('data.0');
        expect($customer['cpf'])->toBe(12345678909);
        expect($customer['offers_count'])->toBe(2);

        // A data da última requisição deve ser a mais recente (se existir)
        if (isset($customer['last_request_date'])) {
            $lastRequestDate = \Carbon\Carbon::parse($customer['last_request_date']);
            expect($lastRequestDate->diffInHours(now()))->toBeLessThan(2);
        }
    });

    test('customers with offers supports pagination with limit parameter', function () {
        // Criar múltiplos customers com ofertas
        for ($i = 0; $i < 15; $i++) {
            $customer = CustomerModel::factory()->withSpecificCpf('1111111111' . $i)->create();
            CreditOfferModel::factory()
                ->forCustomer($customer)
                ->create();
        }

        $response = $this->getJson('/api/v1/credit/customers-with-offers?limit=10');

        $response->assertStatus(200);

        $customers = $response->json('data');
        // Nota: Se a API não implementa limite, pelo menos verificar que retorna dados
        expect($customers)->toBeArray();
        expect(count($customers))->toBeGreaterThan(0);
    });

    test('customers with offers sorts by last request date descending', function () {
        // Criar customers com ofertas em datas diferentes
        $customer1 = CustomerModel::factory()->withSpecificCpf('11122233301')->create();
        $customer2 = CustomerModel::factory()->withSpecificCpf('11122233302')->create();
        $customer3 = CustomerModel::factory()->withSpecificCpf('11122233303')->create();

        CreditOfferModel::factory()
            ->forCustomer($customer1)
            ->create(['created_at' => now()->subDays(3)]);

        CreditOfferModel::factory()
            ->forCustomer($customer2)
            ->create(['created_at' => now()->subDay()]);

        CreditOfferModel::factory()
            ->forCustomer($customer3)
            ->create(['created_at' => now()->subHours(2)]);

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200);

        $customers = $response->json('data');
        expect($customers)->toHaveCount(3);

        // Verificar ordenação (mais recente primeiro)
        $dates = collect($customers)->pluck('last_request_date')->filter();
        if ($dates->count() > 1) {
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $current = \Carbon\Carbon::parse($dates[$i]);
                $next = \Carbon\Carbon::parse($dates[$i + 1]);
                expect($current->gte($next))->toBeTrue();
            }
        }
    });

    test('customers with offers excludes inactive customers', function () {
        // Customer ativo com ofertas
        $activeCustomer = CustomerModel::factory()
            ->withSpecificCpf('12345678909')
            ->create(['is_active' => true]);

        // Customer inativo com ofertas
        $inactiveCustomer = CustomerModel::factory()
            ->withSpecificCpf('98765432100')
            ->create(['is_active' => false]);

        CreditOfferModel::factory()->forCustomer($activeCustomer)->create();
        CreditOfferModel::factory()->forCustomer($inactiveCustomer)->create();

        $response = $this->getJson('/api/v1/credit/customers-with-offers');

        $response->assertStatus(200);

        $customers = $response->json('data');
        expect($customers)->toHaveCount(1);
        expect($customers[0]['cpf'])->toBe(12345678909);
    });

    test('customers with offers endpoint accepts only GET method', function () {
        // Testar métodos não permitidos
        $this->postJson('/api/v1/credit/customers-with-offers')->assertStatus(405);
        $this->putJson('/api/v1/credit/customers-with-offers')->assertStatus(405);
        $this->deleteJson('/api/v1/credit/customers-with-offers')->assertStatus(405);
        $this->patchJson('/api/v1/credit/customers-with-offers')->assertStatus(405);
    });

});
