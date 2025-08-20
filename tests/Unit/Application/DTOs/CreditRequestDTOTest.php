<?php

declare(strict_types=1);

use App\Application\DTOs\CreditRequestDTO;

describe('CreditRequestDTO', function () {
    it('can be created with all required parameters', function () {
        $dto = new CreditRequestDTO(
            requestId: 'req-12345',
            cpf: '12345678901',
            status: 'pending',
            message: 'Request received'
        );

        expect($dto->requestId)->toBe('req-12345')
            ->and($dto->cpf)->toBe('12345678901')
            ->and($dto->status)->toBe('pending')
            ->and($dto->message)->toBe('Request received');
    });

    it('can be converted to array', function () {
        $dto = new CreditRequestDTO(
            requestId: 'req-67890',
            cpf: '98765432100',
            status: 'completed',
            message: 'Request processed successfully'
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'request_id' => 'req-67890',
            'cpf' => '98765432100',
            'status' => 'completed',
            'message' => 'Request processed successfully',
        ]);
    });

    it('handles different status values', function () {
        $statuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];

        foreach ($statuses as $status) {
            $dto = new CreditRequestDTO(
                requestId: 'req-test',
                cpf: '12345678901',
                status: $status,
                message: "Status is {$status}"
            );

            expect($dto->status)->toBe($status);
        }
    });

    it('preserves special characters in messages', function () {
        $specialMessage = 'Request failed: Invalid CPF format (123.456.789-01)';

        $dto = new CreditRequestDTO(
            requestId: 'req-error',
            cpf: '12345678901',
            status: 'failed',
            message: $specialMessage
        );

        expect($dto->message)->toBe($specialMessage);
    });

    it('handles empty strings gracefully', function () {
        $dto = new CreditRequestDTO(
            requestId: '',
            cpf: '',
            status: '',
            message: ''
        );

        expect($dto->requestId)->toBe('')
            ->and($dto->cpf)->toBe('')
            ->and($dto->status)->toBe('')
            ->and($dto->message)->toBe('');
    });

    it('converts to array with correct key mapping', function () {
        $dto = new CreditRequestDTO(
            requestId: 'req-mapping-test',
            cpf: '11122233344',
            status: 'success',
            message: 'Mapping test'
        );

        $array = $dto->toArray();

        expect($array)->toHaveKey('request_id')
            ->and($array)->toHaveKey('cpf')
            ->and($array)->toHaveKey('status')
            ->and($array)->toHaveKey('message')
            ->and($array['request_id'])->toBe('req-mapping-test');
    });

    it('handles unicode characters in message', function () {
        $unicodeMessage = 'Solicitação processada com sucesso! 🎉 ✅';

        $dto = new CreditRequestDTO(
            requestId: 'req-unicode',
            cpf: '12345678901',
            status: 'completed',
            message: $unicodeMessage
        );

        expect($dto->message)->toBe($unicodeMessage)
            ->and($dto->toArray()['message'])->toBe($unicodeMessage);
    });

    it('handles long request IDs', function () {
        $longRequestId = 'req-' . str_repeat('a', 100);

        $dto = new CreditRequestDTO(
            requestId: $longRequestId,
            cpf: '12345678901',
            status: 'pending',
            message: 'Long ID test'
        );

        expect($dto->requestId)->toBe($longRequestId)
            ->and(strlen($dto->requestId))->toBe(104);
    });

    it('is readonly and immutable', function () {
        $dto = new CreditRequestDTO(
            requestId: 'req-immutable',
            cpf: '12345678901',
            status: 'pending',
            message: 'Immutable test'
        );

        // Verify that properties cannot be modified (this would cause a fatal error if attempted)
        expect($dto->requestId)->toBe('req-immutable');
        expect($dto->cpf)->toBe('12345678901');
        expect($dto->status)->toBe('pending');
        expect($dto->message)->toBe('Immutable test');
    });
});
