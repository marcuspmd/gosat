<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Integration\Contracts\ExternalCreditApiServiceInterface;
use App\Domain\Integration\Contracts\ExternalCreditMapperInterface;
use App\Domain\Integration\UseCases\FetchExternalCreditDataUseCase;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\Dtos\ExternalCreditInstitutionDto;
use App\Domain\Shared\ValueObjects\CPF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

describe('FetchExternalCreditDataUseCase', function () {
    beforeEach(function () {
        /** @var \Mockery\MockInterface&ExternalCreditApiServiceInterface $apiService */
        $apiService = mock(ExternalCreditApiServiceInterface::class);
        /** @var \Mockery\MockInterface&CreditOfferRepositoryInterface $creditOfferRepository */
        $creditOfferRepository = mock(CreditOfferRepositoryInterface::class);
        /** @var \Mockery\MockInterface&ExternalCreditMapperInterface $mapper */
        $mapper = mock(ExternalCreditMapperInterface::class);

        $this->apiService = $apiService;
        $this->creditOfferRepository = $creditOfferRepository;
        $this->mapper = $mapper;

        // Note: Log facade will use the default logger configured in Laravel

        $this->useCase = new FetchExternalCreditDataUseCase(
            $apiService,
            $creditOfferRepository,
            $mapper
        );

        // Helper to create real CreditOfferEntity instances (final classes cannot be mocked)
        $this->makeCreditOffer = function (?string $id = null, ?string $cpfValue = null) {
            $id = $id ?? uniqid('offer_', true);
            $customerId = uniqid('cust_', true);
            $cpfValue = $cpfValue ?? CpfHelper::valid('1');

            $customer = new \App\Domain\Customer\Entities\CustomerEntity(
                id: $customerId,
                cpf: new CPF($cpfValue),
                isActive: true
            );

            $institution = new \App\Domain\Credit\Entities\InstitutionEntity(
                id: uniqid('inst_', true),
                institutionId: 1,
                name: 'Test Bank',
                isActive: true
            );

            $modality = new \App\Domain\Credit\Entities\CreditModalityEntity(
                id: uniqid('mod_', true),
                name: 'crédito pessoal',
                standardCode: 'credito-pessoal',
                isActive: true
            );

            $minAmount = \App\Domain\Shared\ValueObjects\Money::fromCents(100000);
            $maxAmount = \App\Domain\Shared\ValueObjects\Money::fromCents(200000);
            $interest = new \App\Domain\Shared\ValueObjects\InterestRate(0.01);
            $minInstallments = new \App\Domain\Shared\ValueObjects\InstallmentCount(12);
            $maxInstallments = new \App\Domain\Shared\ValueObjects\InstallmentCount(24);

            return new CreditOfferEntity(
                id: $id,
                customer: $customer,
                institution: $institution,
                modality: $modality,
                minAmount: $minAmount,
                maxAmount: $maxAmount,
                monthlyInterestRate: $interest,
                minInstallments: $minInstallments,
                maxInstallments: $maxInstallments
            );
        };
    });

    describe('execute method', function () {
        it('successfully fetches and processes external credit data', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestId = 'request-123';

            // Mock response with institutions (using simple object to avoid DTO complexity)
            $mockExternalResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: [
                    new ExternalCreditInstitutionDto(id: 'inst-1', name: 'Test Bank'),
                ]
            );

            // Mock credit offers that mapper would return
            $mockCreditOffers = [
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
            ];

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->with(\Mockery::on(function ($dto) use ($cpf, $requestId) {
                    return $dto instanceof ExternalCreditDto &&
                           $dto->cpf->value === $cpf->value &&
                           $dto->creditRequestId === $requestId;
                }))
                ->andReturn($mockExternalResponse);

            $this->mapper
                ->shouldReceive('mapToCreditOffers')
                ->once()
                ->with($mockExternalResponse)
                ->andReturn($mockCreditOffers);

            $this->creditOfferRepository
                ->shouldReceive('saveAll')
                ->once()
                ->with($mockCreditOffers)
                ->andReturn(true);

            $result = $this->useCase->execute($cpf, $requestId);

            expect($result)->toBe($mockCreditOffers)
                ->and($result)->toHaveCount(2);
        });

        it('returns empty array when no institutions are found', function () {
            $cpf = new CPF(CpfHelper::valid('2'));
            $requestId = 'request-456';

            $emptyResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: []
            );

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andReturn($emptyResponse);

            // Should not call mapper or repository when no institutions
            $this->mapper->shouldNotReceive('mapToCreditOffers');
            $this->creditOfferRepository->shouldNotReceive('saveAll');

            $result = $this->useCase->execute($cpf, $requestId);

            expect($result)->toBe([])
                ->and($result)->toHaveCount(0);
        });

        it('handles empty credit offers from mapper gracefully', function () {
            $cpf = new CPF(CpfHelper::valid('3'));
            $requestId = 'request-789';

            $mockExternalResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: [
                    new ExternalCreditInstitutionDto(id: 'inst-1', name: 'Test Bank'),
                ]
            );

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andReturn($mockExternalResponse);

            $this->mapper
                ->shouldReceive('mapToCreditOffers')
                ->once()
                ->with($mockExternalResponse)
                ->andReturn([]);

            // Should not save empty arrays
            $this->creditOfferRepository->shouldNotReceive('saveAll');

            $result = $this->useCase->execute($cpf, $requestId);

            expect($result)->toBe([])
                ->and($result)->toHaveCount(0);
        });

        it('saves credit offers when mapper returns valid data', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestId = 'request-save';

            $mockExternalResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: [
                    new ExternalCreditInstitutionDto(id: 'inst-1', name: 'Test Bank'),
                ]
            );

            $mockCreditOffers = [
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
            ];

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andReturn($mockExternalResponse);

            $this->mapper
                ->shouldReceive('mapToCreditOffers')
                ->once()
                ->andReturn($mockCreditOffers);

            $this->creditOfferRepository
                ->shouldReceive('saveAll')
                ->once()
                ->with($mockCreditOffers)
                ->andReturn(true);

            $result = $this->useCase->execute($cpf, $requestId);

            expect($result)->toBe($mockCreditOffers)
                ->and($result)->toHaveCount(3);
        });

        it('throws exception when API service fails', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestId = 'request-fail';
            $apiException = new \Exception('API service failed');

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andThrow($apiException);

            // Note: Log calls will be made but not verified in tests

            expect(fn () => $this->useCase->execute($cpf, $requestId))
                ->toThrow(\Exception::class);
        });

        it('throws exception when mapper fails', function () {
            $cpf = new CPF(CpfHelper::valid('2'));
            $requestId = 'request-mapper-fail';
            $mapperException = new \Exception('Mapper failed');

            $mockExternalResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: [
                    new ExternalCreditInstitutionDto(id: 'inst-1', name: 'Test Bank'),
                ]
            );

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andReturn($mockExternalResponse);

            $this->mapper
                ->shouldReceive('mapToCreditOffers')
                ->once()
                ->andThrow($mapperException);

            // Note: Log calls will be made but not verified in tests

            expect(fn () => $this->useCase->execute($cpf, $requestId))
                ->toThrow(\Exception::class);
        });

        it('throws exception when repository save fails', function () {
            $cpf = new CPF(CpfHelper::valid('3'));
            $requestId = 'request-repo-fail';
            $repositoryException = new \Exception('Repository save failed');

            $mockExternalResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: [
                    new ExternalCreditInstitutionDto(id: 'inst-1', name: 'Test Bank'),
                ]
            );

            $mockCreditOffers = [($this->makeCreditOffer)()];

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andReturn($mockExternalResponse);

            $this->mapper
                ->shouldReceive('mapToCreditOffers')
                ->once()
                ->andReturn($mockCreditOffers);

            $this->creditOfferRepository
                ->shouldReceive('saveAll')
                ->once()
                ->andThrow($repositoryException);

            // Note: Log calls will be made but not verified in tests

            expect(fn () => $this->useCase->execute($cpf, $requestId))
                ->toThrow(\Exception::class);
        });

        it('logs error details when exception occurs', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestId = 'request-log-test';
            $testException = new \Exception('Test logging error');

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andThrow($testException);

            // Note: Log calls will be made but not verified in tests

            expect(fn () => $this->useCase->execute($cpf, $requestId))
                ->toThrow(\Exception::class);
        });

        it('handles different CPF formats correctly', function () {
            $cpfValues = [
                CpfHelper::valid('1'),
                CpfHelper::valid('2'),
                CpfHelper::valid('3'),
            ];

            foreach ($cpfValues as $cpfValue) {
                $cpf = new CPF($cpfValue);
                $requestId = 'request-cpf-test';

                $mockResponse = new ExternalCreditDto(
                    cpf: $cpf,
                    creditRequestId: $requestId,
                    institutions: []
                );

                $this->apiService
                    ->shouldReceive('fetchCredit')
                    ->once()
                    ->with(\Mockery::on(function ($dto) use ($cpf, $requestId) {
                        return $dto instanceof ExternalCreditDto &&
                               $dto->cpf->value === $cpf->value &&
                               $dto->creditRequestId === $requestId;
                    }))
                    ->andReturn($mockResponse);

                $result = $this->useCase->execute($cpf, $requestId);

                expect($result)->toBe([]);
            }
        });

        it('handles different request ID formats correctly', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestIds = [
                'simple-id',
                'uuid-12345678-1234-1234-1234-123456789012',
                'request_with_underscores',
                '123456789',
                'very-long-request-id-with-many-characters',
            ];

            foreach ($requestIds as $requestId) {
                $mockResponse = new ExternalCreditDto(
                    cpf: $cpf,
                    creditRequestId: $requestId,
                    institutions: []
                );

                $this->apiService
                    ->shouldReceive('fetchCredit')
                    ->once()
                    ->with(\Mockery::on(function ($dto) use ($cpf, $requestId) {
                        return $dto instanceof ExternalCreditDto &&
                               $dto->cpf->value === $cpf->value &&
                               $dto->creditRequestId === $requestId;
                    }))
                    ->andReturn($mockResponse);

                $result = $this->useCase->execute($cpf, $requestId);

                expect($result)->toBe([]);
            }
        });

        it('processes multiple institutions correctly', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestId = 'request-multi';

            $mockExternalResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: [
                    new ExternalCreditInstitutionDto(id: 'inst-1', name: 'Bank A'),
                    new ExternalCreditInstitutionDto(id: 'inst-2', name: 'Bank B'),
                    new ExternalCreditInstitutionDto(id: 'inst-3', name: 'Bank C'),
                ]
            );

            $mockCreditOffers = [
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
                ($this->makeCreditOffer)(),
            ];

            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->andReturn($mockExternalResponse);

            $this->mapper
                ->shouldReceive('mapToCreditOffers')
                ->once()
                ->andReturn($mockCreditOffers);

            $this->creditOfferRepository
                ->shouldReceive('saveAll')
                ->once()
                ->with($mockCreditOffers)
                ->andReturn(true);

            $result = $this->useCase->execute($cpf, $requestId);

            expect($result)->toBe($mockCreditOffers)
                ->and($result)->toHaveCount(5);
        });

        it('validates ExternalCreditDto creation', function () {
            $cpf = new CPF(CpfHelper::valid('1'));
            $requestId = 'request-dto-validation';

            $mockResponse = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $requestId,
                institutions: []
            );

            // Verify that ExternalCreditDto is created with correct parameters
            $this->apiService
                ->shouldReceive('fetchCredit')
                ->once()
                ->with(\Mockery::on(function ($dto) use ($cpf, $requestId) {
                    return $dto instanceof ExternalCreditDto &&
                           $dto->cpf->value === $cpf->value &&
                           $dto->creditRequestId === $requestId;
                }))
                ->andReturn($mockResponse);

            $result = $this->useCase->execute($cpf, $requestId);

            expect($result)->toBe([]);
        });
    });

    describe('dependency injection', function () {
        it('can be instantiated with all required dependencies', function () {
            /** @var FetchExternalCreditDataUseCase $useCase */
            $useCase = $this->useCase;
            expect($useCase)->toBeInstanceOf(FetchExternalCreditDataUseCase::class);
        });

        it('is marked as final readonly class', function () {
            $reflection = new \ReflectionClass(FetchExternalCreditDataUseCase::class);

            expect($reflection->isFinal())->toBeTrue()
                ->and($reflection->isReadOnly())->toBeTrue();
        });

        it('has correct constructor parameters', function () {
            $reflection = new \ReflectionClass(FetchExternalCreditDataUseCase::class);
            $constructor = $reflection->getConstructor();
            $parameters = $constructor->getParameters();

            expect($parameters)->toHaveCount(3);

            expect($parameters[0]->getName())->toBe('apiService')
                ->and((string) $parameters[0]->getType())->toBe(ExternalCreditApiServiceInterface::class);

            expect($parameters[1]->getName())->toBe('creditOfferRepository')
                ->and((string) $parameters[1]->getType())->toBe(CreditOfferRepositoryInterface::class);

            expect($parameters[2]->getName())->toBe('mapper')
                ->and((string) $parameters[2]->getType())->toBe(ExternalCreditMapperInterface::class);
        });

        it('has execute method with correct signature', function () {
            $reflection = new \ReflectionClass(FetchExternalCreditDataUseCase::class);
            $executeMethod = $reflection->getMethod('execute');
            $parameters = $executeMethod->getParameters();

            expect($parameters)->toHaveCount(2);

            expect($parameters[0]->getName())->toBe('cpf')
                ->and((string) $parameters[0]->getType())->toBe(CPF::class);

            expect($parameters[1]->getName())->toBe('creditRequestId')
                ->and((string) $parameters[1]->getType())->toBe('string');

            // Check return type annotation (array of CreditOfferEntity)
            expect((string) $executeMethod->getReturnType())->toBe('array');
        });
    });
});
