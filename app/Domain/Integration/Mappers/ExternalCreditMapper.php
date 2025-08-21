<?php

declare(strict_types=1);

namespace App\Domain\Integration\Mappers;

use App\Domain\Credit\Entities\CreditModalityEntity;
use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Entities\InstitutionEntity;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Domain\Customer\Entities\CustomerEntity;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\Dtos\ExternalCreditInstitutionDto;
use App\Domain\Shared\Dtos\ExternalCreditModalityDto;
use App\Domain\Shared\Dtos\ExternalCreditOfferDto;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ExternalCreditMapper
{
    public function __construct(
        private InstitutionRepositoryInterface $institutionRepository,
        private CreditModalityRepositoryInterface $creditModalityRepository,
        private CreditOfferRepositoryInterface $creditOfferRepository,
        private CustomerRepositoryInterface $customerRepository,
    ) {}

    /**
     * @return CreditOfferEntity[]
     */
    public function mapToCreditOffers(
        ExternalCreditDto $dto,
    ): array {
        $creditOffers = [];

        foreach ($dto->institutions as $institutionDto) {
            foreach ($institutionDto->modalities as $modalityDto) {

                if (! $this->isValidOffer($modalityDto->offer)) {
                    continue;
                }

                $creditOffers[] = $this->createCreditOffer(
                    cpf: $dto->cpf,
                    institutionDto: $institutionDto,
                    modalityDto: $modalityDto,
                    requestId: $dto->creditRequestId
                );
            }
        }

        return $creditOffers;
    }

    private function isValidOffer(
        ExternalCreditOfferDto $offer
    ): bool {
        return $offer->maxAmountInCents > 0
            && $offer->minAmountInCents >= 0
            && $offer->maxInstallments > 0
            && $offer->minInstallments > 0;
    }

    private function createCreditOffer(
        CPF $cpf,
        ExternalCreditInstitutionDto $institutionDto,
        ExternalCreditModalityDto $modalityDto,
        string $requestId
    ): CreditOfferEntity {

        // Find or create customer
        $customer = $this->customerRepository->findByCpf($cpf);
        if (! $customer) {
            $customer = new CustomerEntity(
                id: Str::uuid()->toString(),
                cpf: $cpf
            );
            $this->customerRepository->save($customer);
        }

        $creditOffer = new CreditOfferEntity(
            id: Str::uuid()->toString(),
            customer: $customer,
            institution: $this->createInstitution($institutionDto),
            modality: $this->createModality($modalityDto),
            minAmount: Money::fromCents($modalityDto->offer->minAmountInCents),
            maxAmount: Money::fromCents($modalityDto->offer->maxAmountInCents),
            monthlyInterestRate: new InterestRate($modalityDto->offer->interestRate),
            minInstallments: new InstallmentCount($modalityDto->offer->minInstallments),
            maxInstallments: new InstallmentCount($modalityDto->offer->maxInstallments),
            requestId: $requestId
        );

        $this->creditOfferRepository->save($creditOffer);

        return $creditOffer;
    }

    private function createInstitution(
        ExternalCreditInstitutionDto $institutionDto
    ): InstitutionEntity {

        $slug = Str::slug($institutionDto->name);

        Log::info('Tentando criar/buscar instituição', [
            'institution_name' => $institutionDto->name,
            'generated_slug' => $slug,
            'institution_id_from_dto' => $institutionDto->id,
        ]);

        $institutionEntity = $this->institutionRepository->findBySlug($slug);

        if ($institutionEntity) {
            Log::info('Instituição encontrada, retornando existente', [
                'found_institution_id' => $institutionEntity->id,
                'found_institution_name' => $institutionEntity->name,
            ]);

            return $institutionEntity;
        }

        Log::info('Instituição não encontrada, criando nova');

        $institutionEntity = new InstitutionEntity(
            id: Str::uuid()->toString(),
            institutionId: (int) $institutionDto->id,
            name: $institutionDto->name,
            isActive: true
        );

        $this->institutionRepository->save($institutionEntity);

        Log::info('Nova instituição criada', [
            'new_institution_id' => $institutionEntity->id,
            'new_institution_slug' => $institutionEntity->slug,
        ]);

        return $institutionEntity;
    }

    private function createModality(
        ExternalCreditModalityDto $modalityDto
    ): CreditModalityEntity {

        $modalityEntity = $this->creditModalityRepository->findBySlug(
            Str::slug($modalityDto->name)
        );

        if ($modalityEntity) {
            return $modalityEntity;
        }

        $modalityEntity = new CreditModalityEntity(
            id: Str::uuid()->toString(),
            standardCode: $modalityDto->name,
            name: $modalityDto->name,
            isActive: true
        );

        $this->creditModalityRepository->save($modalityEntity);

        return $modalityEntity;
    }
}
