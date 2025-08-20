<?php

declare(strict_types=1);

namespace App\Domain\Credit\UseCases;

use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Credit\Services\CreditOfferRankingService;
use App\Domain\Credit\ValueObjects\CreditOfferStatus;
use App\Domain\Shared\ValueObjects\CPF;
use InvalidArgumentException;

final readonly class GetCreditOffersUseCase
{
    public function __construct(
        private CreditOfferRepositoryInterface $creditOfferRepository,
        private CreditOfferRankingService $rankingService
    ) {}

    public function execute(string $requestId): array
    {
        if (empty(trim($requestId))) {
            throw new InvalidArgumentException('Request ID é obrigatório');
        }

        $offers = $this->creditOfferRepository->findByRequestId($requestId);

        if (empty($offers)) {
            return [];
        }

        // Filtrar apenas ofertas concluídas com sucesso
        $completedOffers = array_filter($offers, function ($offer) {
            return $offer->status === CreditOfferStatus::COMPLETED;
        });

        if (empty($completedOffers)) {
            return [];
        }

        // Aplicar ranking às ofertas
        return $this->rankingService->rankOffers($completedOffers);
    }

    public function executeByCpf(CPF $cpf, int $limit = 3): array
    {
        $offers = $this->creditOfferRepository->findCompletedOffers($cpf);

        if (empty($offers)) {
            return [];
        }

        $rankedOffers = $this->rankingService->rankOffers($offers);

        return array_slice($rankedOffers, 0, $limit);
    }

    public function getOfferStatus(string $requestId): ?CreditOfferStatus
    {
        if (empty(trim($requestId))) {
            throw new InvalidArgumentException('Request ID é obrigatório');
        }

        $offers = $this->creditOfferRepository->findByRequestId($requestId);

        if (empty($offers)) {
            return null;
        }

        // Verificar se todas as ofertas estão concluídas
        $allCompleted = true;
        $hasFailures = false;

        foreach ($offers as $offer) {
            if ($offer->status === CreditOfferStatus::PROCESSING ||
                $offer->status === CreditOfferStatus::PENDING) {
                $allCompleted = false;
            }

            if ($offer->status === CreditOfferStatus::FAILED) {
                $hasFailures = true;
            }
        }

        if ($allCompleted) {
            return $hasFailures ? CreditOfferStatus::FAILED : CreditOfferStatus::COMPLETED;
        }

        return CreditOfferStatus::PROCESSING;
    }
}
