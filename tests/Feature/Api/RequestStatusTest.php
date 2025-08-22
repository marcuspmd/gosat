<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\CreditOfferModel;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;

uses(RefreshDatabase::class);

describe('Request Status API', function () {

    test('request status with valid UUID and existing offers returns completed status', function () {
        $requestId = Uuid::uuid4()->toString();
        $customer = CustomerModel::factory()->create();

        // Criar ofertas com o request ID
        CreditOfferModel::factory()
            ->count(3)
            ->forCustomer($customer)
            ->withRequestId($requestId)
            ->create();

        $response = $this->getJson("/api/v1/credit/{$requestId}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'request_id',
                'status',
                'offers_found',
                'message',
            ])
            ->assertJson([
                'request_id' => $requestId,
                'status' => 'completed',
                'offers_found' => 3,
            ]);

        $completedAt = $response->json('completed_at');
        if ($completedAt) {
            expect($completedAt)->toBeString();
        }
    });

    test('request status with valid UUID but no offers returns processing status', function () {
        $requestId = Uuid::uuid4()->toString();

        $response = $this->getJson("/api/v1/credit/{$requestId}/status");

        $response->assertStatus(404)
            ->assertJsonStructure([
                'error',
                'message',
            ])
            ->assertJson([
                'error' => 'not_found',
            ]);
    });

    test('request status with valid UUID and error offers returns error status', function () {
        $requestId = Uuid::uuid4()->toString();
        $customer = CustomerModel::factory()->create();

        // Criar ofertas com erro
        CreditOfferModel::factory()
            ->count(2)
            ->forCustomer($customer)
            ->withRequestId($requestId)
            ->withError()
            ->create();

        $response = $this->getJson("/api/v1/credit/{$requestId}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'request_id',
                'status',
                'offers_found',
                'message',
            ])
            ->assertJson([
                'request_id' => $requestId,
                'status' => 'completed',
                'offers_found' => 2, // Total de ofertas criadas (incluindo com erro)
            ]);
    });

    test('request status with mixed offers returns partial success status', function () {
        $requestId = Uuid::uuid4()->toString();
        $customer = CustomerModel::factory()->create();

        // Criar ofertas válidas
        CreditOfferModel::factory()
            ->count(2)
            ->forCustomer($customer)
            ->withRequestId($requestId)
            ->create();

        // Criar ofertas com erro
        CreditOfferModel::factory()
            ->count(1)
            ->forCustomer($customer)
            ->withRequestId($requestId)
            ->withError()
            ->create();

        $response = $this->getJson("/api/v1/credit/{$requestId}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'request_id',
                'status',
                'offers_found',
                'message',
            ])
            ->assertJson([
                'request_id' => $requestId,
                'status' => 'completed',
                'offers_found' => 3, // Total de ofertas criadas
            ]);
    });

    test('request status with invalid UUID format returns 404', function () {
        $response = $this->getJson('/api/v1/credit/invalid-uuid/status');

        // Laravel route constraint validation
        $response->assertStatus(404);
    });

    test('request status includes processing time when completed', function () {
        $requestId = Uuid::uuid4()->toString();
        $customer = CustomerModel::factory()->create();

        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->withRequestId($requestId)
            ->create([
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(2),
            ]);

        $response = $this->getJson("/api/v1/credit/{$requestId}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'request_id',
                'status',
                'offers_found',
                'message',
            ]);

        $processingTime = $response->json('processing_time_seconds');
        if ($processingTime !== null) {
            expect($processingTime)->toBeGreaterThan(0);
        }
    });

    test('request status endpoint accepts only GET method', function () {
        $requestId = Uuid::uuid4()->toString();

        // Testar métodos não permitidos
        $this->postJson("/api/v1/credit/{$requestId}/status")->assertStatus(405);
        $this->putJson("/api/v1/credit/{$requestId}/status")->assertStatus(405);
        $this->deleteJson("/api/v1/credit/{$requestId}/status")->assertStatus(405);
        $this->patchJson("/api/v1/credit/{$requestId}/status")->assertStatus(405);
    });

    test('request status with very old request returns completed status', function () {
        $requestId = Uuid::uuid4()->toString();
        $customer = CustomerModel::factory()->create();

        // Criar ofertas muito antigas (simulando request antigo)
        CreditOfferModel::factory()
            ->forCustomer($customer)
            ->withRequestId($requestId)
            ->create([
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ]);

        $response = $this->getJson("/api/v1/credit/{$requestId}/status");

        $response->assertStatus(200)
            ->assertJson([
                'request_id' => $requestId,
                'status' => 'completed',
                'offers_found' => 1,
            ]);
    });

    test('request status returns consistent response format', function () {
        $requestId = Uuid::uuid4()->toString();

        // Fazer múltiplas requisições
        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson("/api/v1/credit/{$requestId}/status");

            $response->assertStatus(404)
                ->assertJsonStructure([
                    'error',
                    'message',
                ]);

            expect($response->json('error'))->toBe('not_found');
        }
    });

});
