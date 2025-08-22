<?php

declare(strict_types=1);

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Integration\Mappers\ExternalCreditMapper;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\Dtos\ExternalCreditInstitutionDto;
use App\Domain\Shared\Dtos\ExternalCreditModalityDto;
use App\Domain\Shared\Dtos\ExternalCreditOfferDto;
use App\Domain\Shared\ValueObjects\CPF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('ExternalCreditMapper', function () {

    beforeEach(function () {
        // Mock Log facade
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
        
        $this->institutionRepository = \Mockery::mock(InstitutionRepositoryInterface::class);
        $this->creditModalityRepository = \Mockery::mock(CreditModalityRepositoryInterface::class);
        $this->creditOfferRepository = \Mockery::mock(CreditOfferRepositoryInterface::class);
        $this->customerRepository = \Mockery::mock(CustomerRepositoryInterface::class);

        $this->mapper = new ExternalCreditMapper(
            $this->institutionRepository,
            $this->creditModalityRepository,
            $this->creditOfferRepository,
            $this->customerRepository
        );
    });

    describe('mapToCreditOffers', function () {

        it('handles empty institutions array', function () {
            $externalDto = new ExternalCreditDto(
                cpf: new CPF('11144477735'),
                creditRequestId: 'req-test-123',
                institutions: []
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toBeArray()
                ->and($result)->toHaveCount(0);
        });

        it('creates credit offers from valid external data', function () {
            $cpf = new CPF('11144477735');
            $customer = new CustomerEntity('customer-id', $cpf);
            $institution = new InstitutionEntity('inst-id', 1, 'Test Bank', true);
            $modality = new CreditModalityEntity('mod-id', 'personal-loan', 'Personal Loan', true);

            // Mock customer repository
            $this->customerRepository->shouldReceive('findByCpf')->with($cpf)->once()->andReturn($customer);

            // Mock institution repository
            $this->institutionRepository->shouldReceive('findBySlug')->with('test-bank')->once()->andReturn($institution);

            // Mock modality repository
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('personal-loan')->once()->andReturn($modality);

            // Mock credit offer repository save
            $this->creditOfferRepository->shouldReceive('save')->once();

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000, // R$ 1.000,00
                                    maxAmountInCents: 5000000, // R$ 50.000,00
                                    interestRate: 0.0299, // 2.99% a.m.
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toBeArray()
                ->and($result)->toHaveCount(1)
                ->and($result[0])->toBeInstanceOf(\App\Domain\Credit\Entities\CreditOfferEntity::class);
        });

        it('creates new customer when customer does not exist', function () {
            $cpf = new CPF('11144477735');
            $institution = new InstitutionEntity('inst-id', 1, 'Test Bank', true);
            $modality = new CreditModalityEntity('mod-id', 'personal-loan', 'Personal Loan', true);

            // Mock customer repository - customer not found, then save new customer
            $this->customerRepository->shouldReceive('findByCpf')->with($cpf)->once()->andReturn(null);
            $this->customerRepository->shouldReceive('save')->once();

            // Mock institution repository
            $this->institutionRepository->shouldReceive('findBySlug')->with('test-bank')->once()->andReturn($institution);

            // Mock modality repository
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('personal-loan')->once()->andReturn($modality);

            // Mock credit offer repository save
            $this->creditOfferRepository->shouldReceive('save')->once();

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(1);
        });

        it('creates new institution when institution does not exist', function () {
            $cpf = new CPF('11144477735');
            $customer = new CustomerEntity('customer-id', $cpf);
            $modality = new CreditModalityEntity('mod-id', 'personal-loan', 'Personal Loan', true);

            // Mock customer repository
            $this->customerRepository->shouldReceive('findByCpf')->with($cpf)->once()->andReturn($customer);

            // Mock institution repository - institution not found, then save new institution
            $this->institutionRepository->shouldReceive('findBySlug')->with('new-bank')->once()->andReturn(null);
            $this->institutionRepository->shouldReceive('save')->once();

            // Mock modality repository
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('personal-loan')->once()->andReturn($modality);

            // Mock credit offer repository save
            $this->creditOfferRepository->shouldReceive('save')->once();

            // Mock Log facade
            Log::shouldReceive('info')->times(3);

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'New Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(1);
        });

        it('creates new modality when modality does not exist', function () {
            $cpf = new CPF('11144477735');
            $customer = new CustomerEntity('customer-id', $cpf);
            $institution = new InstitutionEntity('inst-id', 1, 'Test Bank', true);

            // Mock customer repository
            $this->customerRepository->shouldReceive('findByCpf')->with($cpf)->once()->andReturn($customer);

            // Mock institution repository
            $this->institutionRepository->shouldReceive('findBySlug')->with('test-bank')->once()->andReturn($institution);

            // Mock modality repository - modality not found, then save new modality
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('new-loan')->once()->andReturn(null);
            $this->creditModalityRepository->shouldReceive('save')->once();

            // Mock credit offer repository save
            $this->creditOfferRepository->shouldReceive('save')->once();

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'New Loan',
                                slug: 'new-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(1);
        });

        it('skips invalid offers with zero or negative max amount', function () {
            $cpf = new CPF('11144477735');

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Invalid Loan',
                                slug: 'invalid-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 0, // Invalid
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(0);
        });

        it('skips invalid offers with zero max installments', function () {
            $cpf = new CPF('11144477735');

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Invalid Loan',
                                slug: 'invalid-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 0 // Invalid
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(0);
        });

        it('skips invalid offers with zero min installments', function () {
            $cpf = new CPF('11144477735');

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Invalid Loan',
                                slug: 'invalid-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 0, // Invalid
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(0);
        });

        it('skips invalid offers with negative min amount', function () {
            $cpf = new CPF('11144477735');

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Invalid Loan',
                                slug: 'invalid-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: -1000, // Invalid
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(0);
        });

        it('processes multiple institutions and modalities', function () {
            $cpf = new CPF('11144477735');
            $customer = new CustomerEntity('customer-id', $cpf);
            $institution1 = new InstitutionEntity('inst-id-1', 1, 'Bank A', true);
            $institution2 = new InstitutionEntity('inst-id-2', 2, 'Bank B', true);
            $modality1 = new CreditModalityEntity('mod-id-1', 'personal-loan', 'Personal Loan', true);
            $modality2 = new CreditModalityEntity('mod-id-2', 'home-equity', 'Home Equity', true);

            // Mock customer repository
            $this->customerRepository->shouldReceive('findByCpf')->with($cpf)->times(3)->andReturn($customer);

            // Mock institution repository
            $this->institutionRepository->shouldReceive('findBySlug')->with('bank-a')->once()->andReturn($institution1);
            $this->institutionRepository->shouldReceive('findBySlug')->with('bank-b')->times(2)->andReturn($institution2);

            // Mock modality repository
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('personal-loan')->once()->andReturn($modality1);
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('home-equity')->times(2)->andReturn($modality2);

            // Mock credit offer repository save
            $this->creditOfferRepository->shouldReceive('save')->times(3);

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Bank A',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    ),
                    new ExternalCreditInstitutionDto(
                        id: '2',
                        name: 'Bank B',
                        modalities: [
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Home Equity',
                                slug: 'home-equity',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 200000,
                                    maxAmountInCents: 10000000,
                                    interestRate: 0.0199,
                                    minInstallments: 24,
                                    maxInstallments: 120
                                )
                            ),
                            new ExternalCreditModalityDto(
                                id: '2',
                                name: 'Home Equity',
                                slug: 'home-equity',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 300000,
                                    maxAmountInCents: 15000000,
                                    interestRate: 0.0179,
                                    minInstallments: 36,
                                    maxInstallments: 180
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(3);
        });

        it('handles mix of valid and invalid offers', function () {
            $cpf = new CPF('11144477735');
            $customer = new CustomerEntity('customer-id', $cpf);
            $institution = new InstitutionEntity('inst-id', 1, 'Test Bank', true);
            $modality = new CreditModalityEntity('mod-id', 'personal-loan', 'Personal Loan', true);

            // Mock customer repository (only called for valid offers)
            $this->customerRepository->shouldReceive('findByCpf')->with($cpf)->once()->andReturn($customer);

            // Mock institution repository (only called for valid offers)
            $this->institutionRepository->shouldReceive('findBySlug')->with('test-bank')->once()->andReturn($institution);

            // Mock modality repository (only called for valid offers)
            $this->creditModalityRepository->shouldReceive('findBySlug')->with('personal-loan')->once()->andReturn($modality);

            // Mock credit offer repository save (only called for valid offers)
            $this->creditOfferRepository->shouldReceive('save')->once();

            $externalDto = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: 'req-test-123',
                institutions: [
                    new ExternalCreditInstitutionDto(
                        id: '1',
                        name: 'Test Bank',
                        modalities: [
                            // Valid offer
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            ),
                            // Invalid offer (zero max amount)
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 0,
                                    interestRate: 0.0299,
                                    minInstallments: 12,
                                    maxInstallments: 60
                                )
                            ),
                            // Invalid offer (zero min installments)
                            new ExternalCreditModalityDto(
                                id: '1',
                                name: 'Personal Loan',
                                slug: 'personal-loan',
                                offer: new ExternalCreditOfferDto(
                                    minAmountInCents: 100000,
                                    maxAmountInCents: 5000000,
                                    interestRate: 0.0299,
                                    minInstallments: 0,
                                    maxInstallments: 60
                                )
                            )
                        ]
                    )
                ]
            );

            $result = $this->mapper->mapToCreditOffers($externalDto);

            expect($result)->toHaveCount(1); // Only the valid offer should be processed
        });
    });
});

afterEach(function () {
    \Mockery::close();
});
