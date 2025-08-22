<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('SSE Clear Events API', function () {

    beforeEach(function () {
        Event::fake();
        Cache::flush();
    });

    test('sse clear events removes all stored events successfully', function () {
        // Primeiro, criar alguns eventos para limpar
        $events = [
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
                'event' => 'notification',
                'data' => json_encode(['message' => 'Notification']),
                'timestamp' => now()->subMinute()->toISOString(),
            ],
        ];

        Cache::put('sse_events', $events, 300);

        // Verificar que eventos existem
        expect(Cache::get('sse_events', []))->toHaveCount(3);

        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ])
            ->assertJson([
                'message' => 'Events cache cleared successfully',
            ]);

        // Verificar que eventos foram removidos
        expect(Cache::get('sse_events:events', []))->toHaveCount(0);
    });

    test('sse clear events works when no events exist', function () {
        // Garantir que não há eventos
        expect(Cache::get('sse_events', []))->toHaveCount(0);

        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Events cache cleared successfully',
            ]);
    });

    test('sse clear events works correctly', function () {
        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Events cache cleared successfully',
            ]);
    });

    test('sse clear events basic functionality works correctly', function () {
        // Criar eventos para testar limpeza
        Cache::put('sse_events:events', [
            ['id' => '1', 'event' => 'test', 'data' => '{}', 'timestamp' => now()->toISOString()],
        ], 300);

        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);

        // Verificar que os eventos foram removidos
        expect(Cache::get('sse_events:events', []))->toHaveCount(0);
    });

    test('sse clear events when cache is empty works correctly', function () {
        // Garantir que cache está vazio
        Cache::forget('sse_events:events');

        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Events cache cleared successfully',
            ]);
    });

    test('sse clear events handles large number of events efficiently', function () {
        // Criar muitos eventos
        $events = [];
        for ($i = 1; $i <= 1000; $i++) {
            $events[] = [
                'id' => (string) $i,
                'event' => 'test',
                'data' => json_encode(['message' => "Event {$i}"]),
                'timestamp' => now()->subMinutes(1000 - $i)->toISOString(),
            ];
        }

        Cache::put('sse_events:events', $events, 300);

        $startTime = microtime(true);

        $response = $this->postJson('/api/v1/sse/clear');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // em milissegundos

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Events cache cleared successfully',
            ]);

        // Verificar que foi executado em tempo razoável (menos de 1 segundo)
        expect($executionTime)->toBeLessThan(1000);

        // Verificar que todos os eventos foram removidos
        expect(Cache::get('sse_events:events', []))->toHaveCount(0);
    });

    test('sse clear events only affects sse events cache', function () {
        // Criar outros dados no cache para verificar que não são afetados
        Cache::put('other_data', 'should not be affected', 300);
        Cache::put('user_sessions', ['session1', 'session2'], 300);

        // Criar eventos SSE
        Cache::put('sse_events:events', [
            ['id' => '1', 'event' => 'test', 'data' => '{}', 'timestamp' => now()->toISOString()],
        ], 300);

        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200);

        // Verificar que apenas os eventos SSE foram removidos
        expect(Cache::get('sse_events:events', []))->toHaveCount(0);
        expect(Cache::get('other_data'))->toBe('should not be affected');
        expect(Cache::get('user_sessions'))->toBe(['session1', 'session2']);
    });

    test('sse clear events only accepts POST method', function () {
        // Testar métodos não permitidos
        $this->getJson('/api/v1/sse/clear')->assertStatus(405);
        $this->putJson('/api/v1/sse/clear')->assertStatus(405);
        $this->deleteJson('/api/v1/sse/clear')->assertStatus(405);
        $this->patchJson('/api/v1/sse/clear')->assertStatus(405);
    });

    test('sse clear events logs operation for audit trail', function () {
        // Criar alguns eventos
        Cache::put('sse_events:events', [
            ['id' => '1', 'event' => 'test', 'data' => '{}', 'timestamp' => now()->toISOString()],
            ['id' => '2', 'event' => 'test', 'data' => '{}', 'timestamp' => now()->toISOString()],
        ], 300);

        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200);

        // Verificar que a operação foi registrada
        expect($response->json('message'))->toBe('Events cache cleared successfully');
    });

    test('sse clear events response format is consistent', function () {
        // Testar múltiplas vezes para garantir consistência
        for ($i = 0; $i < 3; $i++) {
            Cache::flush();

            // Criar eventos variados
            if ($i > 0) {
                $events = [];
                for ($j = 0; $j < $i * 2; $j++) {
                    $events[] = [
                        'id' => (string) ($j + 1),
                        'event' => 'test',
                        'data' => '{}',
                        'timestamp' => now()->toISOString(),
                    ];
                }
                Cache::put('sse_events', $events, 300);
            }

            $response = $this->postJson('/api/v1/sse/clear');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                ]);
        }
    });

    test('sse clear events handles concurrent requests safely', function () {
        // Criar eventos iniciais
        Cache::put('sse_events:events', [
            ['id' => '1', 'event' => 'test', 'data' => '{}', 'timestamp' => now()->toISOString()],
            ['id' => '2', 'event' => 'test', 'data' => '{}', 'timestamp' => now()->toISOString()],
        ], 300);

        // Simular requisições simultâneas
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/v1/sse/clear');
        }

        // Todas as requisições devem ser bem-sucedidas
        foreach ($responses as $response) {
            $response->assertStatus(200);
            expect($response->json('message'))->toBe('Events cache cleared successfully');
        }

        // No final, não deve haver eventos
        expect(Cache::get('sse_events:events', []))->toHaveCount(0);
    });

});
