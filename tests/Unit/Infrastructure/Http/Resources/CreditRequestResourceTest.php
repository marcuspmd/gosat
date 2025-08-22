<?php

declare(strict_types=1);

use App\Application\DTOs\CreditRequestDTO;
use App\Infrastructure\Http\Resources\CreditRequestResource;
use Illuminate\Http\Request;

describe('CreditRequestResource', function () {
    beforeEach(function () {
        $this->creditRequestDTO = new CreditRequestDTO(
            requestId: 'test-request-123',
            cpf: '11144477735',
            status: 'processing',
            message: 'Request is being processed'
        );
    });

    it('can be instantiated with CreditRequestDTO', function () {
        $resource = new CreditRequestResource($this->creditRequestDTO);

        expect($resource)->toBeInstanceOf(CreditRequestResource::class);
    });

    it('converts DTO to array with correct structure', function () {
        $resource = new CreditRequestResource($this->creditRequestDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array)->toBeArray()
            ->toHaveKeys([
                'request_id',
                'cpf',
                'status',
                'message',
                'created_at',
            ]);
    });

    it('includes correct DTO information', function () {
        $resource = new CreditRequestResource($this->creditRequestDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['request_id'])->toBe('test-request-123')
            ->and($array['cpf'])->toBe('11144477735')
            ->and($array['status'])->toBe('processing')
            ->and($array['message'])->toBe('Request is being processed');
    });

    it('includes formatted timestamp', function () {
        $resource = new CreditRequestResource($this->creditRequestDTO);
        $request = new Request;

        $array = $resource->toArray($request);

        expect($array['created_at'])->toBeString()
            ->and($array['created_at'])->toContain('T')
            ->and($array['created_at'])->toContain(':');
    });

    it('handles different status values', function () {
        $statuses = ['processing', 'completed', 'failed', 'pending'];

        foreach ($statuses as $status) {
            $dto = new CreditRequestDTO(
                requestId: 'test-request-' . $status,
                cpf: '11144477735',
                status: $status,
                message: "Request is {$status}"
            );

            $resource = new CreditRequestResource($dto);
            $array = $resource->toArray(new Request);

            expect($array['status'])->toBe($status)
                ->and($array['message'])->toBe("Request is {$status}");
        }
    });

    it('handles empty message', function () {
        $dto = new CreditRequestDTO(
            requestId: 'test-request-empty',
            cpf: '11144477735',
            status: 'processing',
            message: ''
        );

        $resource = new CreditRequestResource($dto);
        $array = $resource->toArray(new Request);

        expect($array['message'])->toBe('');
    });

    it('handles different CPF formats', function () {
        $cpfs = ['11144477735', '12345678909', '98765432100'];

        foreach ($cpfs as $cpf) {
            $dto = new CreditRequestDTO(
                requestId: 'test-request-' . $cpf,
                cpf: $cpf,
                status: 'processing',
                message: 'Testing CPF format'
            );

            $resource = new CreditRequestResource($dto);
            $array = $resource->toArray(new Request);

            expect($array['cpf'])->toBe($cpf);
        }
    });

    it('handles long request IDs', function () {
        $longRequestId = str_repeat('a', 100);

        $dto = new CreditRequestDTO(
            requestId: $longRequestId,
            cpf: '11144477735',
            status: 'processing',
            message: 'Testing long request ID'
        );

        $resource = new CreditRequestResource($dto);
        $array = $resource->toArray(new Request);

        expect($array['request_id'])->toBe($longRequestId);
    });

    it('handles special characters in message', function () {
        $specialMessage = 'Request with special chars: áéíóú çñü @#$%^&*()';

        $dto = new CreditRequestDTO(
            requestId: 'test-special',
            cpf: '11144477735',
            status: 'processing',
            message: $specialMessage
        );

        $resource = new CreditRequestResource($dto);
        $array = $resource->toArray(new Request);

        expect($array['message'])->toBe($specialMessage);
    });

    it('creates timestamp that is recent', function () {
        $beforeTime = time();

        $resource = new CreditRequestResource($this->creditRequestDTO);
        $array = $resource->toArray(new Request);

        $afterTime = time();
        $timestamp = strtotime($array['created_at']);

        expect($timestamp)->toBeGreaterThanOrEqual($beforeTime)
            ->and($timestamp)->toBeLessThanOrEqual($afterTime);
    });

    it('maintains data integrity through resource conversion', function () {
        $originalData = [
            'requestId' => 'integrity-test-123',
            'cpf' => '98765432100',
            'status' => 'completed',
            'message' => 'Data integrity test message',
        ];

        $dto = new CreditRequestDTO(
            requestId: $originalData['requestId'],
            cpf: $originalData['cpf'],
            status: $originalData['status'],
            message: $originalData['message']
        );

        $resource = new CreditRequestResource($dto);
        $array = $resource->toArray(new Request);

        expect($array['request_id'])->toBe($originalData['requestId'])
            ->and($array['cpf'])->toBe($originalData['cpf'])
            ->and($array['status'])->toBe($originalData['status'])
            ->and($array['message'])->toBe($originalData['message']);
    });
});
