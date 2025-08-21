<?php

declare(strict_types=1);

namespace App\Domain\Integration\UseCases;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Domain\Credit\Repositories\StandardModalityRepositoryInterface;
use App\Domain\Integration\Services\ExternalCreditApiService;
use App\Domain\Integration\Mappers\ExternalCreditMapper;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Persistence\Eloquent\Models\CreditModalityModel;

final readonly class FetchExternalCreditDataUseCase
{
    public function __construct(
        private ExternalCreditApiService $apiService,
        private CreditOfferRepositoryInterface $creditOfferRepository,
        private CreditModalityRepositoryInterface $creditModalityRepository,
        private InstitutionRepositoryInterface $institutionRepository,
        private ExternalCreditMapper $mapper
    ) {
    }

    /**
     * @param CPF $cpf
     * @param string $creditRequestId
     * @return CreditOfferEntity[]
     */
    public function execute(
        CPF $cpf,
        string $creditRequestId
    ): array {
        try {
            $creditRequest = new ExternalCreditDto(
                cpf: $cpf,
                creditRequestId: $creditRequestId,
            );

            $externalCredits = $this->apiService->fetchCredit($creditRequest);

            if (empty($externalCredits->institutions)) {
                return [];
            }

            $creditOffers = $this->mapper->mapToCreditOffers($externalCredits);

            if (!empty($creditOffers)) {
                $this->creditOfferRepository->saveAll($creditOffers);
            }

            $this->creditOfferRepository->markRequestAsCompleted([]);

            return $creditOffers;

        } catch (\Exception $e) {
            $this->creditOfferRepository->markRequestAsFailed(
                $creditRequestId,
                $e->getMessage()
            );

            throw $e;
        }
    }

}
