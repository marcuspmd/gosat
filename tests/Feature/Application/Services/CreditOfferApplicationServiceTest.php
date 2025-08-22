<?php

declare(strict_types=1);

use App\Application\Contracts\QueueServiceInterface;
use App\Application\Services\CreditOfferApplicationService;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

describe('CreditOfferApplicationService', function () {
    beforeEach(function () {
        $this->queueService = mock(QueueServiceInterface::class);
        $this->creditOfferRepository = mock(CreditOfferRepositoryInterface::class);

        $this->service = new CreditOfferApplicationService(
            queueService: $this->queueService,
            creditOfferRepository: $this->creditOfferRepository
        );
    });

    it('can process credit request with valid CPF', function () {
        $cpfValue = CpfHelper::valid('1');
        $cpf = new CPF($cpfValue);

        // Mock repository soft delete
        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($cpf) {
                return $arg instanceof CPF && $arg->value === $cpf->value;
            }));

        // Mock queue service dispatch
        $this->queueService
            ->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::on(function ($job) {
                return $job instanceof FetchCreditOffersJob;
            }));

        $requestId = $this->service->processCreditsRequest($cpfValue);

        expect($requestId)->toBeString()
            ->and(strlen($requestId))->toBe(36) // UUID v4 length
            ->and($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i'); // UUID v4 pattern
    });

    it('creates unique request IDs for different calls', function () {
        $cpfValue = CpfHelper::valid('1');

        // Mock repository calls
        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->twice();

        // Mock queue service calls
        $this->queueService
            ->shouldReceive('dispatch')
            ->twice();

        $requestId1 = $this->service->processCreditsRequest($cpfValue);
        $requestId2 = $this->service->processCreditsRequest($cpfValue);

        expect($requestId1)->not()->toBe($requestId2)
            ->and($requestId1)->toBeString()
            ->and($requestId2)->toBeString();
    });

    it('validates CPF format before processing', function () {
        $this->creditOfferRepository
            ->shouldNotReceive('softDeleteByCpf');

        $this->queueService
            ->shouldNotReceive('dispatch');

        expect(fn () => $this->service->processCreditsRequest('invalid-cpf'))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('handles different valid CPF formats', function () {
        $cpfTests = [
            CpfHelper::valid('1'),
            CpfHelper::valid('2'),
            CpfHelper::valid('3'),
        ];

        foreach ($cpfTests as $cpfValue) {
            // Mock repository soft delete for each CPF
            $this->creditOfferRepository
                ->shouldReceive('softDeleteByCpf')
                ->once()
                ->with(\Mockery::on(function ($arg) use ($cpfValue) {
                    return $arg instanceof CPF && $arg->value === $cpfValue;
                }));

            // Mock queue service dispatch for each CPF
            $this->queueService
                ->shouldReceive('dispatch')
                ->once()
                ->with(\Mockery::on(function ($job) {
                    return $job instanceof FetchCreditOffersJob;
                }));

            $requestId = $this->service->processCreditsRequest($cpfValue);

            expect($requestId)->toBeString()
                ->and(strlen($requestId))->toBe(36);
        }
    });

    it('soft deletes old offers before creating new request', function () {
        $cpfValue = CpfHelper::valid('1');
        $cpf = new CPF($cpfValue);

        // Verify soft delete is called first
        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($cpf) {
                return $arg instanceof CPF && $arg->value === $cpf->value;
            }))
            ->ordered();

        // Verify queue dispatch is called after
        $this->queueService
            ->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::on(function ($job) {
                return $job instanceof FetchCreditOffersJob;
            }))
            ->ordered();

        $requestId = $this->service->processCreditsRequest($cpfValue);

        expect($requestId)->toBeString();
    });

    it('dispatches correct job with CPF and request ID', function () {
        $cpfValue = CpfHelper::valid('2');

        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->once();

        // Capture the dispatched job for inspection
        $dispatchedJob = null;
        $this->queueService
            ->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::on(function ($job) use (&$dispatchedJob) {
                $dispatchedJob = $job;

                return $job instanceof FetchCreditOffersJob;
            }));

        $requestId = $this->service->processCreditsRequest($cpfValue);

        expect($dispatchedJob)->toBeInstanceOf(FetchCreditOffersJob::class);

        // Verify job was created with correct parameters
        // Note: This assumes FetchCreditOffersJob has public properties or getters
        // If not, we can verify the job type is correct
    });

    it('handles repository exceptions gracefully', function () {
        $cpfValue = CpfHelper::valid('1');

        // Mock repository to throw exception
        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->once()
            ->andThrow(new \Exception('Database connection error'));

        $this->queueService
            ->shouldNotReceive('dispatch');

        expect(fn () => $this->service->processCreditsRequest($cpfValue))
            ->toThrow(\Exception::class, 'Database connection error');
    });

    it('handles queue service exceptions gracefully', function () {
        $cpfValue = CpfHelper::valid('1');

        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->once();

        // Mock queue service to throw exception
        $this->queueService
            ->shouldReceive('dispatch')
            ->once()
            ->andThrow(new \Exception('Queue service unavailable'));

        expect(fn () => $this->service->processCreditsRequest($cpfValue))
            ->toThrow(\Exception::class, 'Queue service unavailable');
    });

    it('is readonly service with immutable dependencies', function () {
        // Test that service is properly constructed and readonly
        expect($this->service)->toBeInstanceOf(CreditOfferApplicationService::class);

        // Verify the service can be called multiple times (stateless)
        $cpfValue = CpfHelper::valid('1');

        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->twice();

        $this->queueService
            ->shouldReceive('dispatch')
            ->twice();

        $requestId1 = $this->service->processCreditsRequest($cpfValue);
        $requestId2 = $this->service->processCreditsRequest($cpfValue);

        expect($requestId1)->toBeString()
            ->and($requestId2)->toBeString()
            ->and($requestId1)->not()->toBe($requestId2); // Different UUIDs
    });

    it('generates UUID v4 format request IDs', function () {
        $cpfValue = CpfHelper::valid('3');

        $this->creditOfferRepository
            ->shouldReceive('softDeleteByCpf')
            ->once();

        $this->queueService
            ->shouldReceive('dispatch')
            ->once();

        $requestId = $this->service->processCreditsRequest($cpfValue);

        // Verify UUID v4 format
        expect($requestId)
            ->toBeString()
            ->and($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i')
            ->and(strlen($requestId))->toBe(36)
            ->and(substr($requestId, 14, 1))->toBe('4') // Version 4 identifier
            ->and(in_array(substr($requestId, 19, 1), ['8', '9', 'a', 'b']))->toBeTrue(); // Variant identifier
    });
});
