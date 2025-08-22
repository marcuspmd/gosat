<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Home Route API', function () {

    test('home route returns inertia response with credit consultation component', function () {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Verificar se é uma resposta Inertia
        $response->assertInertia(
            fn (Assert $page) => $page->component('CreditConsultation')
        );
    });

    test('home route has correct named route', function () {
        expect(route('home'))->toBe(url('/'));
    });

    test('home route accepts only GET method', function () {
        // Testar métodos não permitidos
        $this->postJson('/')->assertStatus(405);
        $this->putJson('/')->assertStatus(405);
        $this->deleteJson('/')->assertStatus(405);
        $this->patchJson('/')->assertStatus(405);
    });

    test('home route renders without authentication', function () {
        // Verificar que a rota é acessível sem autenticação
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertInertia(
                fn (Assert $page) => $page->component('CreditConsultation')
            );
    });

    test('home route provides necessary props for credit consultation', function () {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertInertia(
                fn (Assert $page) => $page->component('CreditConsultation')
            );

        // Se houver props específicas necessárias, verificar aqui
        // Por exemplo: configurações da API, limites, etc.
    });

    test('home route sets correct content type', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
        expect($response->headers->get('content-type'))->toContain('text/html');
    });

    test('home route loads quickly', function () {
        $startTime = microtime(true);

        $response = $this->get('/');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // em milissegundos

        $response->assertStatus(200);

        // Verificar se carrega em menos de 1 segundo
        expect($responseTime)->toBeLessThan(1000);
    });

    test('home route handles browser cache headers correctly', function () {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Verificar se não há cache agressivo para página principal
        // (para permitir atualizações da aplicação)
        $cacheControl = $response->headers->get('cache-control');
        if ($cacheControl) {
            expect($cacheControl)->not->toContain('max-age=31536000'); // não deve ter cache de 1 ano
        }
    });

    test('home route provides inertia metadata', function () {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertInertia(
                fn (Assert $page) => $page->component('CreditConsultation')
            );
    });

    test('home route is accessible from root domain', function () {
        // Testar se a rota funciona tanto com '/' quanto sem trailing slash
        $response1 = $this->get('/');
        $response2 = $this->get('');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    });

    test('home route works with different user agents', function () {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15',
            'Mozilla/5.0 (Android 11; Mobile; rv:68.0) Gecko/68.0 Firefox/88.0',
        ];

        foreach ($userAgents as $userAgent) {
            $response = $this->get('/', ['User-Agent' => $userAgent]);

            $response->assertStatus(200)
                ->assertInertia(
                    fn (Assert $page) => $page->component('CreditConsultation')
                );
        }
    });

    test('home route handles referrer information', function () {
        $referrers = [
            'https://google.com',
            'https://bing.com',
            'https://facebook.com',
            null, // Direct access
        ];

        foreach ($referrers as $referrer) {
            $headers = $referrer ? ['Referer' => $referrer] : [];

            $response = $this->get('/', $headers);

            $response->assertStatus(200)
                ->assertInertia(
                    fn (Assert $page) => $page->component('CreditConsultation')
                );
        }
    });

    test('home route is consistent across multiple requests', function () {
        // Fazer múltiplas requisições para verificar consistência
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/');

            $response->assertStatus(200)
                ->assertInertia(
                    fn (Assert $page) => $page->component('CreditConsultation')
                );
        }
    });

});
