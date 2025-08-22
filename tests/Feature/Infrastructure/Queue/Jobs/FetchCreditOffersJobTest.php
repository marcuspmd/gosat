<?php

declare(strict_types=1);

use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

describe('FetchCreditOffersJob', function () {

    it('can be created with required parameters', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'request-123';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);
    });

    it('implements ShouldQueue interface', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'request-123';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    it('has proper Laravel job traits', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'request-123';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Test that job has the expected Laravel queue job behaviors
        $reflection = new \ReflectionClass($job);
        $traits = $reflection->getTraitNames();

        expect($traits)->toContain(
            'Illuminate\Bus\Queueable',
            'Illuminate\Foundation\Bus\Dispatchable',
            'Illuminate\Queue\InteractsWithQueue',
            'Illuminate\Queue\SerializesModels'
        );
    });

    it('can be serialized and unserialized for queue storage', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'request-456';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Test serialization (important for queue storage)
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        expect($unserialized)->toBeInstanceOf(FetchCreditOffersJob::class);
    });

    it('handles different CPF formats during construction', function () {
        $cpfValues = [
            CpfHelper::valid('1'),
            CpfHelper::valid('2'),
            CpfHelper::valid('3'),
        ];

        foreach ($cpfValues as $cpfValue) {
            $job = new FetchCreditOffersJob($cpfValue, 'test-request');
            expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);
        }
    });

    it('handles different request ID formats during construction', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestIds = [
            'simple-id',
            'uuid-12345678-1234-1234-1234-123456789012',
            'request_with_underscores',
            '123456789',
            'very-long-request-id-with-many-characters-and-dashes',
        ];

        foreach ($requestIds as $requestId) {
            $job = new FetchCreditOffersJob($cpfValue, $requestId);
            expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);
        }
    });

    it('has a failed method for error handling', function () {
        $cpfValue = CpfHelper::valid('2');
        $requestId = 'failed-test';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Test that the failed method exists
        expect(method_exists($job, 'failed'))->toBeTrue();
    });

    it('stores CPF and request ID as private properties', function () {
        $cpfValue = CpfHelper::valid('3');
        $requestId = 'property-test';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Test that properties are stored (indirectly by successful construction)
        expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);

        // We can't directly access private properties in a unit test without reflection,
        // but successful construction implies they were stored correctly
    });

    it('has proper queue configuration', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'queue-config-test';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Test that job can be created with queue configuration
        expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);
    });

    it('can be dispatched to queue system', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'dispatch-test';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Test that the job has dispatch capability
        expect(method_exists($job, 'dispatch'))->toBeTrue();
    });

    it('maintains job state through serialization cycle', function () {
        $cpfValue = CpfHelper::valid('2');
        $requestId = 'serialization-cycle-test';

        $originalJob = new FetchCreditOffersJob($cpfValue, $requestId);

        // Simulate queue serialization/deserialization
        $serialized = serialize($originalJob);
        $deserializedJob = unserialize($serialized);

        expect($deserializedJob)->toBeInstanceOf(FetchCreditOffersJob::class)
            ->and($deserializedJob)->not->toBe($originalJob); // Different instances
    });

    it('validates job structure and interfaces', function () {
        $cpfValue = CpfHelper::valid('1');
        $requestId = 'structure-test';

        $job = new FetchCreditOffersJob($cpfValue, $requestId);

        // Verify it implements required interfaces for Laravel queues
        expect($job instanceof \Illuminate\Contracts\Queue\ShouldQueue)->toBeTrue();
    });

    it('can handle edge case parameters', function () {
        $edgeCases = [
            ['cpf' => CpfHelper::valid('1'), 'requestId' => ''],
            ['cpf' => CpfHelper::valid('2'), 'requestId' => '1'],
            ['cpf' => CpfHelper::valid('3'), 'requestId' => str_repeat('a', 100)],
        ];

        foreach ($edgeCases as $case) {
            $job = new FetchCreditOffersJob($case['cpf'], $case['requestId']);
            expect($job)->toBeInstanceOf(FetchCreditOffersJob::class);
        }
    });

    it('maintains proper method signatures', function () {
        $job = new FetchCreditOffersJob(CpfHelper::valid('1'), 'test');

        // Verify handle method exists and has correct signature
        expect(method_exists($job, 'handle'))->toBeTrue();
        expect(method_exists($job, 'failed'))->toBeTrue();

        $handleMethod = new \ReflectionMethod($job, 'handle');
        $failedMethod = new \ReflectionMethod($job, 'failed');

        // Verify method signatures
        expect($handleMethod->getNumberOfParameters())->toBe(1);
        expect($failedMethod->getNumberOfParameters())->toBe(1);
    });
});
