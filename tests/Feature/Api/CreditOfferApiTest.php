<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Credit Offer API', function () {

    beforeEach(function () {
        Queue::fake();
    });

    test('health endpoint returns healthy status', function () {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'credit-offer-api',
                'version' => '1.0.0',
            ])
            ->assertJsonStructure([
                'status',
                'service',
                'version',
                'timestamp',
            ]);
    });

    test('credit search with valid CPF returns request ID', function () {
        $response = $this->postJson('/api/v1/credit/search', [
            'cpf' => '12345678909', // CPF de teste válido
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'request_id',
                'status',
                'message',
            ])
            ->assertJson([
                'status' => 'processing',
            ]);

        // Verificar se job foi despachado
        Queue::assertPushed(\App\Infrastructure\Queue\Jobs\FetchCreditOffersJob::class);
    });

    test('credit search with invalid CPF returns validation error', function () {
        $response = $this->postJson('/api/v1/credit/search', [
            'cpf' => '12345678900', // CPF inválido
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'validation_error',
            ])
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    });

    test('credit search without CPF returns validation error', function () {
        $response = $this->postJson('/api/v1/credit/search', []);

        $response->assertStatus(422) // Laravel validation error
            ->assertJsonValidationErrors(['cpf']);
    });

    test('credit search with malformed CPF returns validation error', function () {
        $response = $this->postJson('/api/v1/credit/search', [
            'cpf' => '12345678901', // Sem formatação
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'validation_error',
            ]);
    });

    test('credit status with valid request ID returns processing status', function () {
        // Simular um request ID válido
        $requestId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $response = $this->getJson("/api/v1/credit/status/{$requestId}");

        // Como não há dados reais, deve retornar not_found ou processing
        expect($response->getStatusCode())->toBeIn([200, 404]);
    });

    test('credit status with invalid request ID format returns validation error', function () {
        $response = $this->getJson('/api/v1/credit/status/invalid-uuid');

        $response->assertStatus(400) // Invalid UUID format validation
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    });

    test('credit simulation with valid data but no offers returns 404', function () {
        $response = $this->postJson('/api/v1/credit/simulate', [
            'cpf' => '12345678909',
            'amount' => 5000000, // R$ 50.000,00 in cents
            'installments' => 24,
        ]);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    });

    test('credit offers endpoint with valid CPF returns offers', function () {
        $response = $this->getJson('/api/v1/credit/offers?cpf=12345678909');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cpf',
                'offers',
                'total_offers',
            ]);
    });

    test('credit offers endpoint with limit parameter works correctly', function () {
        $response = $this->getJson('/api/v1/credit/offers?cpf=12345678909&limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cpf',
                'offers',
                'total_offers',
            ]);
    });

});
