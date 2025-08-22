<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\HealthController;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('HealthController', function () {
    it('returns healthy status with correct structure', function () {
        $controller = new HealthController;

        $response = $controller->health();

        expect($response->status())->toBe(200);

        $data = $response->getData(true);
        expect($data)->toHaveKey('status', 'healthy');
        expect($data)->toHaveKey('service', 'credit-offer-api');
        expect($data)->toHaveKey('version', '1.0.0');
        expect($data)->toHaveKey('timestamp');
        expect($data['timestamp'])->toBeString();
    });

    it('responds to GET /api/v1/health via routes', function () {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'service',
                'version',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'healthy',
                'service' => 'credit-offer-api',
                'version' => '1.0.0',
            ]);
    });

    it('returns valid ISO timestamp format', function () {
        $controller = new HealthController;

        $response = $controller->health();
        $data = $response->getData(true);

        // Validate ISO 8601 timestamp format
        $timestamp = $data['timestamp'];
        expect($timestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.*Z$/');
    });

    it('has correct content type', function () {
        $response = $this->get('/api/v1/health');

        $response->assertHeader('Content-Type', 'application/json');
    });
});
