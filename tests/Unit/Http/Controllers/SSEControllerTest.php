<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Api\SSEController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

describe('SSEController', function () {
    beforeEach(function () {
        $this->controller = new SSEController();
    });

    it('returns SSE stream response with correct headers', function () {
        $request = new Request();

        $response = $this->controller->stream($request);

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->headers->get('Content-Type'))->toBe('text/event-stream')
            ->and($response->headers->get('Cache-Control'))->toBe('no-cache, private')
            ->and($response->headers->get('Connection'))->toBe('keep-alive')
            ->and($response->headers->get('X-Accel-Buffering'))->toBe('no');
    });

    it('returns response with status 200', function () {
        $request = new Request();

        $response = $this->controller->stream($request);

        expect($response->getStatusCode())->toBe(200);
    });

    it('returns response with streaming capability', function () {
        $request = new Request();

        $response = $this->controller->stream($request);

        // StreamedResponse doesn't have getContent that returns callable
        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    it('handles request with different parameters gracefully', function () {
        $request = Request::create('/sse', 'GET', ['param' => 'value']);

        $response = $this->controller->stream($request);

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toBe('text/event-stream');
    });

    it('maintains correct response type for SSE', function () {
        $request = new Request();

        $response = $this->controller->stream($request);

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->headers->get('Content-Type'))->toBe('text/event-stream')
            ->and($response->headers->get('Cache-Control'))->toBe('no-cache, private');
    });
});
