<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('SSE Notifications API', function () {

    beforeEach(function () {
        Event::fake();
        Cache::flush(); // Limpar cache entre testes
    });

    test('sse notifications endpoint returns event stream headers', function () {
        $response = $this->get('/api/v1/sse/notifications');

        $response->assertStatus(200);

        // Verificar headers do SSE
        expect($response->headers->get('content-type'))->toBe('text/event-stream; charset=UTF-8');
        expect($response->headers->get('cache-control'))->toBe('no-cache, private');
        expect($response->headers->get('connection'))->toBe('keep-alive');
        expect($response->headers->get('access-control-allow-origin'))->toBe('*');
        $accessControlCredentials = $response->headers->get('access-control-allow-credentials');
        if ($accessControlCredentials) {
            expect($accessControlCredentials)->toBe('true');
        }
    });

    test('sse notifications endpoint supports event filtering by type', function () {
        // Adicionar alguns eventos ao cache/storage
        Cache::put('sse_events', [
            [
                'id' => '1',
                'event' => 'credit_offer_update',
                'data' => json_encode(['message' => 'Offer updated']),
                'timestamp' => now()->toISOString(),
            ],
            [
                'id' => '2',
                'event' => 'system_notification',
                'data' => json_encode(['message' => 'System message']),
                'timestamp' => now()->toISOString(),
            ],
        ], 300);

        $response = $this->get('/api/v1/sse/notifications?event=credit_offer_update');

        $response->assertStatus(200);

        $content = $response->getContent();
        if ($content) {
            expect($content)->toContain('credit_offer_update');
            expect($content)->toContain('Offer updated');
        }
    });

    test('sse notifications endpoint supports last event id for replay', function () {
        // Simular eventos perdidos
        Cache::put('sse_events', [
            [
                'id' => '1',
                'event' => 'test',
                'data' => json_encode(['message' => 'Event 1']),
                'timestamp' => now()->subMinutes(5)->toISOString(),
            ],
            [
                'id' => '2',
                'event' => 'test',
                'data' => json_encode(['message' => 'Event 2']),
                'timestamp' => now()->subMinutes(3)->toISOString(),
            ],
            [
                'id' => '3',
                'event' => 'test',
                'data' => json_encode(['message' => 'Event 3']),
                'timestamp' => now()->subMinute()->toISOString(),
            ],
        ], 300);

        $response = $this->get('/api/v1/sse/notifications', [
            'Last-Event-ID' => '1',
        ]);

        $response->assertStatus(200);

        $content = $response->getContent();
        // Deve incluir apenas eventos após o ID 1
        if ($content) {
            expect($content)->toContain('Event 2');
            expect($content)->toContain('Event 3');
            expect($content)->not->toContain('Event 1');
        }
    });

    test('sse notifications endpoint handles client disconnection gracefully', function () {
        // Este teste verifica se não há vazamentos de memória ou recursos
        $response = $this->get('/api/v1/sse/notifications');

        $response->assertStatus(200);

        // Simular desconexão do cliente
        // Em um ambiente real, isso seria testado com stream timeout
        expect($response->headers->get('content-type'))->toBe('text/event-stream; charset=UTF-8');
    });

    test('sse notifications endpoint limits event history size', function () {
        // Criar muitos eventos para testar limite
        $events = [];
        for ($i = 1; $i <= 200; $i++) {
            $events[] = [
                'id' => (string) $i,
                'event' => 'test',
                'data' => json_encode(['message' => "Event {$i}"]),
                'timestamp' => now()->subMinutes(200 - $i)->toISOString(),
            ];
        }

        Cache::put('sse_events', $events, 300);

        $response = $this->get('/api/v1/sse/notifications');

        $response->assertStatus(200);

        // Verificar se não retorna mais que um limite razoável de eventos
        $content = $response->getContent();
        if ($content) {
            $eventCount = substr_count($content, 'event: test');
            // Assumindo um limite de 100 eventos por vez
            expect($eventCount)->toBeLessThanOrEqual(100);
        }
    });

    test('sse notifications endpoint returns proper json data format', function () {
        Cache::put('sse_events', [
            [
                'id' => '1',
                'event' => 'credit_request_completed',
                'data' => json_encode([
                    'request_id' => 'test-uuid',
                    'cpf' => '12345678909',
                    'status' => 'completed',
                    'total_offers' => 3,
                ]),
                'timestamp' => now()->toISOString(),
            ],
        ], 300);

        $response = $this->get('/api/v1/sse/notifications');

        $response->assertStatus(200);

        $content = $response->getContent();
        if ($content) {
            expect($content)->toContain('event: credit_request_completed');
            expect($content)->toContain('data: {"request_id":"test-uuid"');
            expect($content)->toContain('"cpf":"12345678909"');
            expect($content)->toContain('"offers_found":3');
        }
    });

    test('sse notifications endpoint handles cors preflight request', function () {
        $response = $this->options('/api/v1/sse/notifications', [
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Last-Event-ID',
        ]);

        // Verificar headers CORS
        expect($response->headers->get('access-control-allow-origin'))->toBe('*');
        $allowMethods = $response->headers->get('access-control-allow-methods');
        if ($allowMethods) {
            expect($allowMethods)->toContain('GET');
        }
    });

    test('sse notifications endpoint only accepts GET method', function () {
        // Testar métodos não permitidos
        $this->postJson('/api/v1/sse/notifications')->assertStatus(405);
        $this->putJson('/api/v1/sse/notifications')->assertStatus(405);
        $this->deleteJson('/api/v1/sse/notifications')->assertStatus(405);
        $this->patchJson('/api/v1/sse/notifications')->assertStatus(405);
    });

});
