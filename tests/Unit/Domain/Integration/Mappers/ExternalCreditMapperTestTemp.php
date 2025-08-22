<?php

declare(strict_types=1);

use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Integration\Mappers\ExternalCreditMapper;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\ValueObjects\CPF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CpfHelper;

uses(RefreshDatabase::class);

describe('ExternalCreditMapper', function () {
    it('handles empty institutions array', function () {
        $institutionRepository = \Mockery::mock(InstitutionRepositoryInterface::class);
        $creditModalityRepository = \Mockery::mock(CreditModalityRepositoryInterface::class);
        $creditOfferRepository = \Mockery::mock(CreditOfferRepositoryInterface::class);
        $customerRepository = \Mockery::mock(CustomerRepositoryInterface::class);

        $mapper = new ExternalCreditMapper(
            $institutionRepository,
            $creditModalityRepository,
            $creditOfferRepository,
            $customerRepository
        );

        $externalDto = new ExternalCreditDto(
            cpf: new CPF(CpfHelper::generate()),
            creditRequestId: 'req-test-123',
            institutions: []
        );

        $result = $mapper->mapToCreditOffers($externalDto);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(0);
    });
});

afterEach(function () {
    \Mockery::close();
});
