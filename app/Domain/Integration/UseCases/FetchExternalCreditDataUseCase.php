<?php

declare(strict_types=1);

namespace App\Domain\Integration\UseCases;

use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Integration\Services\ExternalCreditApiService;
use App\Domain\Shared\ValueObjects\CPF;
use InvalidArgumentException;

final readonly class FetchExternalCreditDataUseCase
{
    public function __construct(
        private ExternalCreditApiService $apiService,
        private CreditOfferRepositoryInterface $creditOfferRepository
    ) {}

    public function execute(CPF $cpf, string $requestId): array
    {
        if (empty(trim($requestId))) {
            throw new InvalidArgumentException('Request ID é obrigatório');
        }

        try {
            // Buscar dados da API externa
            $externalCredits = $this->apiService->fetchCredit($cpf);

            if (empty($externalCredits)) {
                return [];
            }

            $creditOffers = [];

            foreach ($externalCredits as $credit) {
                try {
                    // TODO: Implementar ModalityNormalizationService
                    // Por enquanto, criar ofertas simples
                    // $creditOffer = $this->createBasicOffer($cpf, $requestId, $credit);

                    // if ($creditOffer !== null) {
                    //     $creditOffers[] = $creditOffer;
                    // }

                } catch (\Exception $e) {
                    // Log individual offer normalization failures but continue processing others
                    continue;
                }
            }

            // Salvar todas as ofertas normalizadas
            if (! empty($creditOffers)) {
                $this->creditOfferRepository->saveAll($creditOffers);
            }

            return $creditOffers;

        } catch (\Exception $e) {
            // Marcar request como falho em caso de erro geral
            $this->creditOfferRepository->markRequestAsFailed(
                $requestId,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
