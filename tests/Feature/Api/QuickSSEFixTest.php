<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Quick SSE Fix Test', function () {

    test('sse test event basic functionality', function () {
        $response = $this->postJson('/api/v1/sse/test', [
            'message' => 'Simple test',
            'type' => 'info',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);
    });

    test('sse clear events basic functionality', function () {
        $response = $this->postJson('/api/v1/sse/clear');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Events cache cleared successfully',
            ]);
    });
});
