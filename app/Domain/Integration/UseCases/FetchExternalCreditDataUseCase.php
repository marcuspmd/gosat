<?php

declare(strict_types=1);

namespace App\Domain\Integration\UseCases;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Integration\Contracts\ExternalCreditApiServiceInterface;
use App\Domain\Integration\Contracts\ExternalCreditMapperInterface;
use App\Domain\Shared\Dtos\ExternalCreditDto;
use App\Domain\Shared\ValueObjects\CPF;
use Illuminate\Support\Facades\Log;

final readonly class FetchExternalCreditDataUseCase
{
    public function __construct(
        private ExternalCreditApiServiceInterface $apiService,
        private CreditOfferRepositoryInterface $creditOfferRepository,
        private ExternalCreditMapperInterface $mapper
    ) {
    }

    /**
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

            if (! empty($creditOffers)) {
                $this->creditOfferRepository->saveAll($creditOffers);
            }

            return $creditOffers;

        } catch (\Exception $e) {
            Log::error('Error fetching external credit data', [
                'cpf' => $cpf->value,
                'creditRequestId' => $creditRequestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
