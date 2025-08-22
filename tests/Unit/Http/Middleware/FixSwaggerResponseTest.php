<?php

declare(strict_types=1);

use App\Http\Middleware\FixSwaggerResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;

describe('FixSwaggerResponse', function () {
    beforeEach(function () {
        $this->middleware = new FixSwaggerResponse;
    });

    it('forces status 200 for swagger docs JSON response', function () {
        $request = new Request;

        // Mock route with name 'api.docs.json'
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getName')->andReturn('api.docs.json');
        $request->setRouteResolver(fn () => $route);

        $nextResponse = new Response('{"swagger": "2.0"}', 404, ['Content-Type' => 'application/json']);

        $response = $this->middleware->handle($request, function () use ($nextResponse) {
            return $nextResponse;
        });

        expect($response->getStatusCode())->toBe(200);
    });

    it('does not modify response for non-swagger routes', function () {
        $request = new Request;

        // Mock route with different name
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getName')->andReturn('other.route');
        $request->setRouteResolver(fn () => $route);

        $nextResponse = new Response('{"data": "test"}', 404, ['Content-Type' => 'application/json']);

        $response = $this->middleware->handle($request, function () use ($nextResponse) {
            return $nextResponse;
        });

        expect($response->getStatusCode())->toBe(404);
    });

    it('does not modify response when content type is not JSON', function () {
        $request = new Request;

        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getName')->andReturn('api.docs.json');
        $request->setRouteResolver(fn () => $route);

        $nextResponse = new Response('Some HTML content', 404, ['Content-Type' => 'text/html']);

        $response = $this->middleware->handle($request, function () use ($nextResponse) {
            return $nextResponse;
        });

        expect($response->getStatusCode())->toBe(404);
    });

    it('does not modify response when content is empty', function () {
        $request = new Request;

        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getName')->andReturn('api.docs.json');
        $request->setRouteResolver(fn () => $route);

        $nextResponse = new Response('', 404, ['Content-Type' => 'application/json']);

        $response = $this->middleware->handle($request, function () use ($nextResponse) {
            return $nextResponse;
        });

        expect($response->getStatusCode())->toBe(404);
    });

    it('does not modify response when route is null', function () {
        $request = new Request;
        $request->setRouteResolver(fn () => null);

        $nextResponse = new Response('{"swagger": "2.0"}', 404, ['Content-Type' => 'application/json']);

        $response = $this->middleware->handle($request, function () use ($nextResponse) {
            return $nextResponse;
        });

        expect($response->getStatusCode())->toBe(404);
    });

    it('preserves original response content and headers', function () {
        $request = new Request;

        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getName')->andReturn('api.docs.json');
        $request->setRouteResolver(fn () => $route);

        $originalContent = '{"swagger": "2.0", "info": {"title": "API"}}';
        $nextResponse = new Response($originalContent, 500, [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'test-value',
        ]);

        $response = $this->middleware->handle($request, function () use ($nextResponse) {
            return $nextResponse;
        });

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe($originalContent)
            ->and($response->headers->get('X-Custom-Header'))->toBe('test-value')
            ->and($response->headers->get('Content-Type'))->toBe('application/json');
    });
});
