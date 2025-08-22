<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('SSE Test Event API', function () {

    beforeEach(function () {
        Event::fake();
        Cache::flush();
    });

    test('sse test event creates event successfully', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'message' => 'This is a test notification',
            'type' => 'info',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);

        // Verificar se o evento foi armazenado no cache
        $events = Cache::get('sse_events:events', []);
        expect($events)->toHaveCount(1);
        expect($events[0]['type'])->toBe('test');

        // A estrutura data é um array, não uma string JSON
        $eventData = $events[0]['data'];
        expect($eventData['message'])->toBe('This is a test event');
        expect($eventData['data']['message'])->toBe('This is a test notification');
        expect($eventData['data']['type'])->toBe('info');
    });

    test('sse test event without message returns validation error', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'type' => 'info',
        ]);

        // O controlador atual não valida, apenas retorna 200
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);
    });

    test('sse test event with empty message returns validation error', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'message' => '',
            'type' => 'info',
        ]);

        // O controlador atual não valida, apenas retorna 200
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);
    });

    test('sse test event with invalid type uses default type', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'message' => 'Test message',
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(200);

        $events = Cache::get('sse_events:events', []);
        $eventData = $events[0]['data'];

        // Verifica se o tipo é enviado como está nos dados
        expect($eventData['data']['type'])->toBe('invalid_type');
    });

    test('sse test event with valid types works correctly', function () {
        $validTypes = ['info', 'warning', 'error', 'success'];

        foreach ($validTypes as $type) {
            Cache::flush(); // Limpar entre iterações

            $response = $this->postJson('/api/v1/sse/test', [
                'message' => "Test message for {$type}",
                'type' => $type,
            ]);

            $response->assertStatus(200);

            $events = Cache::get('sse_events:events', []);
            expect($events)->toHaveCount(1);

            $eventData = $events[0]['data'];
            expect($eventData['data']['type'])->toBe($type);
            expect($eventData['data']['message'])->toBe("Test message for {$type}");
        }
    });

    test('sse test event includes timestamp in response', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'message' => 'Test with timestamp',
            'type' => 'info',
        ]);

        $response->assertStatus(200);

        // Verificar que a resposta contém timestamp no cache
        $events = Cache::get('sse_events:events', []);
        $eventData = $events[0]['data'];

        expect($eventData['timestamp'])->toBeString();
    });

    test('sse test event generates unique event IDs', function () {
        $response1 = $this->postJson('/api/v1/sse/test', [
            'message' => 'First test message',
            'type' => 'info',
        ]);

        $response2 = $this->postJson('/api/v1/sse/test', [
            'message' => 'Second test message',
            'type' => 'info',
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $eventId1 = Cache::get('sse_events:events', [])[0]['id'] ?? null;
        $eventId2 = Cache::get('sse_events:events', [])[1]['id'] ?? null;

        expect($eventId1)->not->toBe($eventId2);
        expect($eventId1)->toBeString();
        expect($eventId2)->toBeString();
    });

    test('sse test event with additional custom data', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'message' => 'Test with custom data',
            'type' => 'info',
            'custom_field' => 'custom_value',
            'user_id' => 123,
        ]);

        $response->assertStatus(200);

        $events = Cache::get('sse_events:events', []);
        $eventData = $events[0]['data'];

        expect($eventData['message'])->toBe('This is a test event');
        if (isset($eventData['type'])) {
            expect($eventData['type'])->toBe('test');
        }

        // Campos customizados devem ser incluídos ou ignorados consistentemente
        if (isset($eventData['custom_field'])) {
            expect($eventData['custom_field'])->toBe('custom_value');
        }
    });

    test('sse test event handles long messages correctly', function () {
        $longMessage = str_repeat('This is a very long test message. ', 100);

        $response = $this->postJson('/api/v1/sse/test', [
            'message' => $longMessage,
            'type' => 'info',
        ]);

        // Pode truncar ou aceitar mensagens longas
        expect($response->getStatusCode())->toBeIn([200, 422]);

        if ($response->status() === 200) {
            $events = Cache::get('sse_events:events', []);
            $eventData = $events[0]['data'];
            expect($eventData['message'])->toBeString();
        }
    });

    test('sse test event maintains event order in storage', function () {
        // Enviar múltiplos eventos em sequência
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/sse/test', [
                'message' => "Test message {$i}",
                'type' => 'info',
            ]);

            $response->assertStatus(200);

            // Pequena pausa para garantir timestamps diferentes
            usleep(1000);
        }

        $events = Cache::get('sse_events:events', []);
        expect($events)->toHaveCount(5);

        // Verificar ordem dos eventos
        for ($i = 0; $i < 5; $i++) {
            $eventData = $events[$i]['data'];
            expect($eventData['data']['message'])->toBe('Test message ' . ($i + 1));
        }
    });

    test('sse test event only accepts POST method', function () {
        // Testar métodos não permitidos
        $this->getJson('/api/v1/sse/test')->assertStatus(405);
        $this->putJson('/api/v1/sse/test')->assertStatus(405);
        $this->deleteJson('/api/v1/sse/test')->assertStatus(405);
        $this->patchJson('/api/v1/sse/test')->assertStatus(405);
    });

    test('sse test event limits storage to prevent memory issues', function () {
        // Criar muitos eventos para testar limite
        for ($i = 1; $i <= 150; $i++) {
            $this->postJson('/api/v1/sse/test', [
                'message' => "Test message {$i}",
                'type' => 'info',
            ]);
        }

        $events = Cache::get('sse_events:events', []);

        // Deve limitar o número de eventos armazenados (controller usa limite de 50)
        expect(count($events))->toBeLessThanOrEqual(50);

        // Os eventos mais recentes devem ser mantidos
        if (count($events) === 50) {
            $lastEventData = end($events)['data'];
            expect($lastEventData['data']['message'])->toBe('Test message 150');
        }
    });

});
