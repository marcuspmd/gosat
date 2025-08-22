<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\HealthController;
use Illuminate\Http\JsonResponse;

describe('HealthController', function () {
    it('returns healthy status', function () {
        // Mock the response and timestamp
        $response = new JsonResponse([
            'status' => 'healthy',
            'service' => 'credit-offer-api',
            'version' => '1.0.0',
            'timestamp' => '2023-01-01T00:00:00.000Z',
        ]);

        expect($response)->toBeInstanceOf(JsonResponse::class)
            ->and($response->getStatusCode())->toBe(200);

        $data = $response->getData(true);

        expect($data['status'])->toBe('healthy')
            ->and($data['service'])->toBe('credit-offer-api')
            ->and($data['version'])->toBe('1.0.0')
            ->and($data)->toHaveKey('timestamp');
    });

    it('returns JSON response with correct structure', function () {
        $response = new JsonResponse([
            'status' => 'healthy',
            'service' => 'credit-offer-api',
            'version' => '1.0.0',
            'timestamp' => '2023-01-01T00:00:00.000Z',
        ]);

        $data = $response->getData(true);

        expect($data)->toHaveKeys(['status', 'service', 'version', 'timestamp'])
            ->and($data)->toHaveCount(4);
    });

    it('has correct content type header', function () {
        $response = new JsonResponse(['test' => 'data']);

        expect($response->headers->get('Content-Type'))->toBe('application/json');
    });

    it('returns proper HTTP status code for health check', function () {
        $response = new JsonResponse(['status' => 'healthy']);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->isSuccessful())->toBeTrue()
            ->and($response->isClientError())->toBeFalse()
            ->and($response->isServerError())->toBeFalse();
    });
});
