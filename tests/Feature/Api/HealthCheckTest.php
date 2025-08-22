<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Health Check API', function () {

    test('health endpoint returns healthy status with all required fields', function () {
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

        // Verificar tipos de dados
        $json = $response->json();
        expect($json['status'])->toBe('healthy');
        expect($json['service'])->toBe('credit-offer-api');
        expect($json['version'])->toBe('1.0.0');
        expect($json['timestamp'])->toBeString();
    });

    test('health endpoint response time is acceptable', function () {
        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/health');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Verificar se responde em menos de 1 segundo
        expect($responseTime)->toBeLessThan(1000);
    });

    test('health endpoint accepts GET requests only', function () {
        // Testar métodos não permitidos
        $this->postJson('/api/v1/health')->assertStatus(405);
        $this->putJson('/api/v1/health')->assertStatus(405);
        $this->deleteJson('/api/v1/health')->assertStatus(405);
        $this->patchJson('/api/v1/health')->assertStatus(405);
    });

    test('health endpoint returns consistent response format', function () {
        // Fazer múltiplas requisições para verificar consistência
        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson('/api/v1/health');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'service',
                    'version',
                    'timestamp',
                ]);
        }
    });

});
