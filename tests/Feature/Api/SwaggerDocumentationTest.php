<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Swagger Documentation API', function () {

    test('swagger docs endpoint returns html documentation page', function () {
        $response = $this->get('/api/docs');

        $response->assertStatus(200);

        // Verificar se retorna HTML
        expect($response->headers->get('content-type'))->toContain('text/html');
    });

    test('swagger docs json endpoint returns swagger json specification', function () {
        $response = $this->getJson('/api/docs.json');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'openapi',
                'info' => [
                    'title',
                    'version',
                ],
                'paths',
            ]);

        // Verificar se tem informações básicas do Swagger
        $json = $response->json();
        expect($json['openapi'])->toStartWith('3.');
        expect($json['info']['title'])->toBeString();
    });

    test('swagger generate documentation endpoint creates new documentation', function () {
        $response = $this->postJson('/api/docs/generate');

        // Pode retornar 200 (sucesso) ou 201 (criado)
        expect($response->getStatusCode())->toBeIn([200, 201]);

        $response->assertJsonStructure([
            'message',
        ]);
    });

    test('swagger docs endpoint handles missing documentation gracefully', function () {
        // Este teste verifica se a rota funciona mesmo sem documentação completa
        $response = $this->get('/api/docs');

        // Deve sempre retornar uma página, mesmo que vazia
        expect($response->getStatusCode())->toBeIn([200, 404]);
    });

});
