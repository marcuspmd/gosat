<?php

declare(strict_types=1);

use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\Dtos\ExternalCreditInstitutionDto;
use App\Domain\Shared\Dtos\ExternalCreditModalityDto;
use App\Domain\Shared\Dtos\ExternalCreditOfferDto;
use App\Domain\Shared\ValueObjects\CPF;
use Tests\Helpers\CpfHelper;

describe('ExternalCreditDto', function () {
    it('can be created with minimal parameters', function () {
        $cpf = new CPF(CpfHelper::valid('1'));

        $dto = new ExternalCreditDto(
            cpf: $cpf,
            creditRequestId: 'req-123'
        );

        expect($dto->cpf)->toBe($cpf)
            ->and($dto->creditRequestId)->toBe('req-123')
            ->and($dto->institutions)->toBe([]);
    });

    it('can be created with institutions', function () {
        $cpf = new CPF(CpfHelper::valid('2'));
        $institution = new ExternalCreditInstitutionDto(
            id: '1',
            name: 'Banco Teste'
        );

        $dto = new ExternalCreditDto(
            cpf: $cpf,
            creditRequestId: 'req-456',
            institutions: [$institution]
        );

        expect($dto->cpf)->toBe($cpf)
            ->and($dto->creditRequestId)->toBe('req-456')
            ->and($dto->institutions)->toHaveCount(1)
            ->and($dto->institutions[0])->toBe($institution);
    });

    it('can handle multiple institutions', function () {
        $cpf = new CPF(CpfHelper::valid('3'));
        $institution1 = new ExternalCreditInstitutionDto(id: '1', name: 'Banco 1');
        $institution2 = new ExternalCreditInstitutionDto(id: '2', name: 'Banco 2');

        $dto = new ExternalCreditDto(
            cpf: $cpf,
            creditRequestId: 'req-789',
            institutions: [$institution1, $institution2]
        );

        expect($dto->institutions)->toHaveCount(2)
            ->and($dto->institutions[0]->name)->toBe('Banco 1')
            ->and($dto->institutions[1]->name)->toBe('Banco 2');
    });
});

describe('ExternalCreditInstitutionDto', function () {
    it('can be created with default values', function () {
        $dto = new ExternalCreditInstitutionDto;

        expect($dto->id)->toBe('')
            ->and($dto->name)->toBe('')
            ->and($dto->slug)->toBe('')
            ->and($dto->modalities)->toBe([]);
    });

    it('can be created with all parameters', function () {
        $modality = new ExternalCreditModalityDto(
            id: 'mod-1',
            name: 'Crédito Pessoal',
            slug: 'credito-pessoal',
            offer: new ExternalCreditOfferDto
        );

        $dto = new ExternalCreditInstitutionDto(
            id: 'inst-1',
            name: 'Banco Teste',
            slug: 'banco-teste',
            modalities: [$modality]
        );

        expect($dto->id)->toBe('inst-1')
            ->and($dto->name)->toBe('Banco Teste')
            ->and($dto->slug)->toBe('banco-teste')
            ->and($dto->modalities)->toHaveCount(1)
            ->and($dto->modalities[0])->toBe($modality);
    });

    it('can handle multiple modalities', function () {
        $modality1 = new ExternalCreditModalityDto(
            id: 'mod-1',
            name: 'Crédito Pessoal',
            slug: 'credito-pessoal',
            offer: new ExternalCreditOfferDto
        );

        $modality2 = new ExternalCreditModalityDto(
            id: 'mod-2',
            name: 'Crédito Consignado',
            slug: 'credito-consignado',
            offer: new ExternalCreditOfferDto
        );

        $dto = new ExternalCreditInstitutionDto(
            id: 'inst-2',
            name: 'Financeira Teste',
            modalities: [$modality1, $modality2]
        );

        expect($dto->modalities)->toHaveCount(2)
            ->and($dto->modalities[0]->name)->toBe('Crédito Pessoal')
            ->and($dto->modalities[1]->name)->toBe('Crédito Consignado');
    });
});

describe('ExternalCreditModalityDto', function () {
    it('can be created with all required parameters', function () {
        $offer = new ExternalCreditOfferDto(
            minInstallments: 6,
            maxInstallments: 36,
            interestRate: 0.025,
            minAmountInCents: 100000,
            maxAmountInCents: 1000000
        );

        $dto = new ExternalCreditModalityDto(
            id: 'mod-123',
            name: 'Crédito Pessoal',
            slug: 'credito-pessoal',
            offer: $offer
        );

        expect($dto->id)->toBe('mod-123')
            ->and($dto->name)->toBe('Crédito Pessoal')
            ->and($dto->slug)->toBe('credito-pessoal')
            ->and($dto->offer)->toBe($offer);
    });

    it('preserves offer data correctly', function () {
        $offer = new ExternalCreditOfferDto(
            minInstallments: 12,
            maxInstallments: 48,
            interestRate: 0.015,
            minAmountInCents: 50000,
            maxAmountInCents: 500000
        );

        $dto = new ExternalCreditModalityDto(
            id: 'mod-456',
            name: 'Financiamento',
            slug: 'financiamento',
            offer: $offer
        );

        expect($dto->offer->minInstallments)->toBe(12)
            ->and($dto->offer->maxInstallments)->toBe(48)
            ->and($dto->offer->interestRate)->toBe(0.015)
            ->and($dto->offer->minAmountInCents)->toBe(50000)
            ->and($dto->offer->maxAmountInCents)->toBe(500000);
    });
});

describe('ExternalCreditOfferDto', function () {
    it('can be created with default values', function () {
        $dto = new ExternalCreditOfferDto;

        expect($dto->minInstallments)->toBe(1)
            ->and($dto->maxInstallments)->toBe(1)
            ->and($dto->interestRate)->toBe(0.0)
            ->and($dto->minAmountInCents)->toBe(0)
            ->and($dto->maxAmountInCents)->toBe(0);
    });

    it('can be created with custom values', function () {
        $dto = new ExternalCreditOfferDto(
            minInstallments: 6,
            maxInstallments: 36,
            interestRate: 0.035,
            minAmountInCents: 200000,
            maxAmountInCents: 2000000
        );

        expect($dto->minInstallments)->toBe(6)
            ->and($dto->maxInstallments)->toBe(36)
            ->and($dto->interestRate)->toBe(0.035)
            ->and($dto->minAmountInCents)->toBe(200000)
            ->and($dto->maxAmountInCents)->toBe(2000000);
    });

    it('handles high values correctly', function () {
        $dto = new ExternalCreditOfferDto(
            minInstallments: 120,
            maxInstallments: 360,
            interestRate: 0.15,
            minAmountInCents: 10000000, // R$ 100,000.00
            maxAmountInCents: 100000000 // R$ 1,000,000.00
        );

        expect($dto->minInstallments)->toBe(120)
            ->and($dto->maxInstallments)->toBe(360)
            ->and($dto->interestRate)->toBe(0.15)
            ->and($dto->minAmountInCents)->toBe(10000000)
            ->and($dto->maxAmountInCents)->toBe(100000000);
    });

    it('handles zero values correctly', function () {
        $dto = new ExternalCreditOfferDto(
            minInstallments: 0,
            maxInstallments: 0,
            interestRate: 0.0,
            minAmountInCents: 0,
            maxAmountInCents: 0
        );

        expect($dto->minInstallments)->toBe(0)
            ->and($dto->maxInstallments)->toBe(0)
            ->and($dto->interestRate)->toBe(0.0)
            ->and($dto->minAmountInCents)->toBe(0)
            ->and($dto->maxAmountInCents)->toBe(0);
    });

    it('handles fractional interest rates correctly', function () {
        $dto = new ExternalCreditOfferDto(
            interestRate: 0.00123456789
        );

        expect($dto->interestRate)->toBe(0.00123456789);
    });
});
