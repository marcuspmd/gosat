<?php

declare(strict_types=1);

use App\Application\Contracts\QueueServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CreditOfferController', function () {

    beforeEach(function () {
        // Prevent the actual queue job from running during HTTP tests
        $this->app->instance(QueueServiceInterface::class, Mockery::mock(QueueServiceInterface::class, function ($m) {
            $m->shouldReceive('dispatch')->andReturnNull();
            $m->shouldReceive('dispatchAfter')->andReturnNull();
            $m->shouldReceive('dispatchToQueue')->andReturnNull();
        }));
    });

    describe('POST /api/v1/credit - creditRequest', function () {

        it('processes valid credit request successfully', function () {
            $response = $this->postJson('/api/v1/credit', [
                'cpf' => '11144477735',
            ]);

            $response->assertStatus(202)
                ->assertJsonStructure([
                    'request_id',
                    'status',
                    'message',
                ])
                ->assertJson([
                    'status' => 'processing',
                    'message' => 'Request in progress. Use the request_id to check status.',
                ]);

            expect($response->json('request_id'))->toBeString()
                ->and(strlen($response->json('request_id')))->toBeGreaterThan(10);
        });

        it('validates CPF format requirement', function () {
            $response = $this->postJson('/api/v1/credit', [
                'cpf' => '123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cpf']);
        });

        it('validates CPF must be present', function () {
            $response = $this->postJson('/api/v1/credit', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cpf']);
        });

        it('validates CPF must be exactly 11 digits', function () {
            $response = $this->postJson('/api/v1/credit', [
                'cpf' => '1234567890123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cpf']);
        });

        it('handles InvalidArgumentException from invalid CPF', function () {
            // Using all zeros CPF which will throw InvalidArgumentException
            $response = $this->postJson('/api/v1/credit', [
                'cpf' => '00000000000',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'error' => 'validation_error',
                ]);
        });
    });

    describe('GET /api/v1/credit/{requestId}/status - getRequestStatus', function () {

        it('returns 404 for invalid UUID format', function () {
            $requestId = 'invalid-uuid';

            $response = $this->getJson("/api/v1/credit/{$requestId}/status");

            $response->assertStatus(404);
        });

        it('returns not found for non-existent request', function () {
            $requestId = '99999999-9999-9999-9999-999999999999';

            $response = $this->getJson("/api/v1/credit/{$requestId}/status");

            $response->assertStatus(404)
                ->assertJson([
                    'error' => 'not_found',
                    'message' => 'Request not found',
                ]);
        });

    });

    describe('GET /api/v1/credit - getCreditOffers', function () {

        it('validates CPF parameter is required', function () {
            $response = $this->getJson('/api/v1/credit');

            $response->assertStatus(422);
        });

        it('returns empty offers when no offers found for CPF', function () {
            $response = $this->getJson('/api/v1/credit?cpf=98765432100');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'cpf',
                    'offers',
                    'total_offers',
                    'limit',
                ])
                ->assertJson([
                    'total_offers' => 0,
                    'offers' => [],
                ]);
        });

        it('handles InvalidArgumentException in getCreditOffers', function () {
            // Using an invalid CPF that would trigger CPF class validation
            $response = $this->getJson('/api/v1/credit?cpf=00000000000');

            $response->assertStatus(400)
                ->assertJson([
                    'error' => 'validation_error',
                ]);
        });

    });

    describe('GET /api/v1/credit/customers-with-offers - getAllCustomersWithOffers', function () {

        it('returns response structure for customers with offers', function () {
            $response = $this->getJson('/api/v1/credit/customers-with-offers');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                ]);
        });

        it('handles page parameter correctly', function () {
            $response = $this->getJson('/api/v1/credit/customers-with-offers?page=2');

            $response->assertStatus(200);
        });

        it('handles per_page parameter correctly', function () {
            $response = $this->getJson('/api/v1/credit/customers-with-offers?per_page=5');

            $response->assertStatus(200);
        });

    });

    describe('POST /api/v1/credit/simulate - simulateCredit', function () {

        it('validates required parameters for simulation', function () {
            $response = $this->postJson('/api/v1/credit/simulate', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cpf', 'amount']);
        });

        it('validates CPF format in simulation', function () {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => 'invalid',
                'amount' => 1000.00,
                'installments' => 12,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cpf']);
        });

        it('validates amount must be positive', function () {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '11144477735',
                'amount' => -1000.00,
                'installments' => 12,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);
        });

        it('validates installments must be positive', function () {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '11144477735',
                'amount' => 1000.00,
                'installments' => 0,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['installments']);
        });

        it('returns 404 when no simulation data available', function () {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '11144477735',
                'amount' => 1000,
                'installments' => 12,
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'error' => 'no_offers',
                    'message' => 'No offers available for the provided parameters',
                ]);
        });

        it('handles InvalidArgumentException in simulateCredit', function () {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '00000000000', // Invalid CPF
                'amount' => 1000,
                'installments' => 12,
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'error' => 'validation_error',
                ]);
        });

        it('processes simulation with modality parameter', function () {
            $response = $this->postJson('/api/v1/credit/simulate', [
                'cpf' => '11144477735',
                'amount' => 1000,
                'installments' => 12,
                'modality' => 'Personal Credit', // This tests the optional modality parameter
            ]);

            // Should return 404 since no offers exist in test DB, but tests the code path
            $response->assertStatus(404);
        });
    });

    describe('Response Headers', function () {

        it('returns proper content type headers', function () {
            $response = $this->postJson('/api/v1/credit', [
                'cpf' => '11144477735',
            ]);

            $response->assertHeader('Content-Type', 'application/json');
        });
    });
});
