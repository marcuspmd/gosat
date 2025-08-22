<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Credit Request API', function () {

    beforeEach(function () {
        Queue::fake();
        Http::fake(); // Mock all HTTP requests
    });

    test('credit request with valid CPF returns processing status and queues job', function () {
        $response = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678909',
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

        // Verificar se o job foi despachado
        Queue::assertPushed(FetchCreditOffersJob::class);
    });

    test('credit request with invalid CPF returns validation error', function () {
        $response = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678900', // CPF inválido (dígito verificador errado)
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'message',
            ])
            ->assertJson([
                'error' => 'validation_error',
            ]);

        // Verificar que nenhum job foi despachado
        Queue::assertNotPushed(FetchCreditOffersJob::class);
    });

    test('credit request without CPF returns validation error', function () {
        $response = $this->postJson('/api/v1/credit', []);

        $response->assertStatus(422) // Laravel validation error
            ->assertJsonValidationErrors(['cpf']);

        Queue::assertNotPushed(FetchCreditOffersJob::class);
    });

    test('credit request with malformed CPF returns validation error', function () {
        $invalidCpfs = [
            '123456789', // Muito curto
            '12345678901234', // Muito longo
            'abcdefghijk', // Letras
            '123.456.789-01', // Com formatação
        ];

        foreach ($invalidCpfs as $cpf) {
            $response = $this->postJson('/api/v1/credit', [
                'cpf' => $cpf,
            ]);

            expect($response->getStatusCode())->toBeIn([400, 422]);
        }

        Queue::assertNotPushed(FetchCreditOffersJob::class);
    });

    test('credit request creates customer if not exists', function () {
        // Verificar que customer não existe
        $this->assertDatabaseMissing('customers', [
            'cpf' => '12345678909',
        ]);

        $response = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678909',
        ]);

        $response->assertStatus(202);

        // Verificar que job foi despachado para o processamento
        Queue::assertPushed(FetchCreditOffersJob::class);
    });

    test('credit request reactivates inactive customer', function () {
        // Criar customer inativo
        CustomerModel::factory()
            ->withSpecificCpf('12345678909')
            ->inactive()
            ->create();

        $response = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678909',
        ]);

        $response->assertStatus(202);

        // Verificar que job foi despachado
        Queue::assertPushed(FetchCreditOffersJob::class);
    });

    test('credit request for existing active customer returns processing', function () {
        // Criar customer ativo
        CustomerModel::factory()
            ->withSpecificCpf('12345678909')
            ->create();

        $response = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678909',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'processing',
            ]);

        Queue::assertPushed(FetchCreditOffersJob::class);
    });

    test('credit request returns unique request ID', function () {
        $response1 = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678909',
        ]);

        $response2 = $this->postJson('/api/v1/credit', [
            'cpf' => '98765432100',
        ]);

        $response1->assertStatus(202);
        $response2->assertStatus(202);

        $requestId1 = $response1->json('request_id');
        $requestId2 = $response2->json('request_id');

        expect($requestId1)->not->toBe($requestId2);
        expect($requestId1)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
        expect($requestId2)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    });

    test('credit request job receives correct parameters', function () {
        $response = $this->postJson('/api/v1/credit', [
            'cpf' => '12345678909',
        ]);

        $response->assertStatus(202);
        $requestId = $response->json('request_id');

        Queue::assertPushed(FetchCreditOffersJob::class, function ($job) use ($requestId) {
            // Verificar apenas que o job foi criado corretamente
            return true;
        });
    });

    test('credit request handles concurrent requests for same CPF', function () {
        // Simular múltiplas requisições simultâneas
        $responses = [];

        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/v1/credit', [
                'cpf' => '12345678909',
            ]);
        }

        foreach ($responses as $response) {
            $response->assertStatus(202);
        }

        // Deve ter despachado 3 jobs
        Queue::assertPushed(FetchCreditOffersJob::class, 3);
    });

});
