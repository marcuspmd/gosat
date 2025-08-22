<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;
use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use App\Infrastructure\Persistence\Eloquent\Models\InstitutionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('Simulate Credit API', function () {

    beforeEach(function () {
        Http::fake(); // Mock all HTTP requests
    });

    test('credit simulation with valid data and existing offers returns simulation result', function () {
        // Criar dados de teste
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();
        $institution = InstitutionModel::factory()->create(['name' => 'Banco Teste']);
        $modality = CreditModalityModel::factory()->create(['name' => 'Crédito Pessoal']);

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create([
                'institution_id' => $institution->id,
                'modality_id' => $modality->id,
                'min_amount_cents' => 100000, // R$ 1.000
                'max_amount_cents' => 1000000, // R$ 10.000
                'monthly_interest_rate' => 2.5,
                'min_installments' => 6,
                'max_installments' => 48,
            ]);

        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 500000, // R$ 5.000 em centavos
            'installments' => 24,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'cpf',
                    'requested_amount',
                    'requested_installments',
                    'simulations' => [
                        '*' => [
                            'financial_institution',
                            'credit_modality',
                            'monthly_payment',
                            'total_amount',
                            'monthly_interest_rate',
                            'annual_interest_rate',
                            'total_interest',
                        ],
                    ],
                    'total_simulations',
                ],
            ]);

        $response->assertJson([
            'data' => [
                'cpf' => '12345678909',
                'total_simulations' => 1,
            ],
        ]);
    });

    test('credit simulation with amount outside offer range returns no matching offers', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create([
                'min_amount_cents' => 100000, // R$ 1.000
                'max_amount_cents' => 500000, // R$ 5.000
            ]);

        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 1000000, // R$ 10.000 (fora do range)
            'installments' => 24,
        ]);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'error',
                'message',
            ])
            ->assertJson([
                'error' => 'no_offers',
            ]);
    });

    test('credit simulation with installments outside offer range returns no matching offers', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create([
                'min_installments' => 6,
                'max_installments' => 24,
            ]);

        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 300000, // R$ 3.000
            'installments' => 48, // Fora do range
        ]);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    });

    test('credit simulation without required fields returns validation error', function () {
        $response = $this->postJson('/api/v1/credit/simulate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf', 'amount', 'installments']);
    });

    test('credit simulation with invalid CPF returns validation error', function () {
        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678900', // CPF inválido
            'amount' => 500000,
            'installments' => 24,
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'message',
            ])
            ->assertJson([
                'error' => 'validation_error',
            ]);
    });

    test('credit simulation with invalid amount returns validation error', function () {
        $invalidAmounts = [
            -100, // Negativo
            0, // Zero
            50, // Muito baixo (menos de R$ 1,00)
        ];

        foreach ($invalidAmounts as $amount) {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '12345678909',
                'amount' => $amount,
                'installments' => 24,
            ]);

            expect($response->getStatusCode())->toBeIn([400, 422]);
        }
    });

    test('credit simulation with invalid installments returns validation error', function () {
        $invalidInstallments = [
            0, // Zero
            1, // Muito baixo
            121, // Muito alto (mais de 10 anos)
        ];

        foreach ($invalidInstallments as $installments) {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '12345678909',
                'amount' => 500000,
                'installments' => $installments,
            ]);

            expect($response->getStatusCode())->toBeIn([400, 422, 404]);
        }
    });

    test('credit simulation calculates correct monthly installment', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();
        $institution = InstitutionModel::factory()->create();
        $modality = CreditModalityModel::factory()->create();

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create([
                'institution_id' => $institution->id,
                'modality_id' => $modality->id,
                'min_amount_cents' => 100000,
                'max_amount_cents' => 1000000,
                'monthly_interest_rate' => 2.0, // 2% ao mês
                'min_installments' => 6,
                'max_installments' => 48,
            ]);

        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 600000, // R$ 6.000
            'installments' => 12,
        ]);

        $response->assertStatus(200);

        $offer = $response->json('data.simulations.0');

        // Verificar se os cálculos estão corretos
        expect($offer['monthly_interest_rate'])->toBe(2);
        expect($offer['annual_interest_rate'])->toBe(24); // Aproximado
        expect($offer['monthly_payment'])->toBeInt();
        expect($offer['total_amount'])->toBeInt();
        expect($offer['total_interest'])->toBeInt();

        // O valor total deve ser maior que o valor solicitado devido aos juros
        expect($offer['total_amount'])->toBeGreaterThan(60000);
    });

    test('credit simulation with multiple matching offers returns all options', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();

        // Criar múltiplas ofertas que atendem aos critérios
        $institution1 = InstitutionModel::factory()->create(['name' => 'Banco A']);
        $institution2 = InstitutionModel::factory()->create(['name' => 'Banco B']);
        $modality = CreditModalityModel::factory()->create();

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create([
                'institution_id' => $institution1->id,
                'modality_id' => $modality->id,
                'min_amount_cents' => 100000,
                'max_amount_cents' => 1000000,
                'monthly_interest_rate' => 2.0,
                'min_installments' => 6,
                'max_installments' => 48,
            ]);

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->create([
                'institution_id' => $institution2->id,
                'modality_id' => $modality->id,
                'min_amount_cents' => 100000,
                'max_amount_cents' => 1000000,
                'monthly_interest_rate' => 1.8,
                'min_installments' => 6,
                'max_installments' => 48,
            ]);

        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 500000,
            'installments' => 24,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_simulations' => 2,
                ],
            ]);

        $offers = $response->json('data.simulations');
        expect($offers)->toHaveCount(2);

        // Verificar se as duas instituições estão presentes
        $institutionNames = collect($offers)->pluck('financial_institution')->toArray();
        expect($institutionNames)->toContain('Banco A');
        expect($institutionNames)->toContain('Banco B');
    });

    test('credit simulation for non-existent customer returns no offers', function () {
        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '98765432100', // CPF que não existe
            'amount' => 500000,
            'installments' => 24,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'no_offers',
            ]);
    });

    test('credit simulation sorts offers by lowest interest rate', function () {
        $customer = CustomerModel::factory()->withSpecificCpf('12345678909')->create();
        $modality = CreditModalityModel::factory()->create();

        // Criar ofertas com taxas diferentes
        $institution1 = InstitutionModel::factory()->create(['name' => 'Banco Alto']);
        $institution2 = InstitutionModel::factory()->create(['name' => 'Banco Baixo']);
        $institution3 = InstitutionModel::factory()->create(['name' => 'Banco Médio']);

        CreditOfferModel::factory()->forCustomer($customer)->create([
            'institution_id' => $institution1->id,
            'modality_id' => $modality->id,
            'monthly_interest_rate' => 3.0,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 1000000,
            'min_installments' => 6,
            'max_installments' => 48,
        ]);

        CreditOfferModel::factory()->forCustomer($customer)->create([
            'institution_id' => $institution2->id,
            'modality_id' => $modality->id,
            'monthly_interest_rate' => 1.5,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 1000000,
            'min_installments' => 6,
            'max_installments' => 48,
        ]);

        CreditOfferModel::factory()->forCustomer($customer)->create([
            'institution_id' => $institution3->id,
            'modality_id' => $modality->id,
            'monthly_interest_rate' => 2.0,
            'min_amount_cents' => 100000,
            'max_amount_cents' => 1000000,
            'min_installments' => 6,
            'max_installments' => 48,
        ]);

        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 500000,
            'installments' => 24,
        ]);

        $response->assertStatus(200);

        $offers = $response->json('data.simulations');
        expect($offers)->toHaveCount(3);

        // Verificar ordenação por taxa de juros (menor para maior)
        expect($offers[0]['financial_institution'])->toBe('Banco Baixo'); // 1.5%
        expect($offers[1]['financial_institution'])->toBe('Banco Médio'); // 2.0%
        expect($offers[2]['financial_institution'])->toBe('Banco Alto'); // 3.0%
    });

});
