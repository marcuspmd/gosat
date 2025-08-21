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

    public function getStoredOffersByCpf(CPF $cpf, array $filters = []): array
    {
        $offers = $this->creditOfferRepository->findByCpf($cpf);

        if (empty($offers)) {
            return [];
        }

        // Aplicar filtros se fornecidos
        $filteredOffers = $this->applyFilters($offers, $filters);

        // Aplicar ordenação baseada nos filtros
        return $this->applySorting($filteredOffers, $filters);
    }

    private function applyFilters(array $offers, array $filters): array
    {
        if (empty($filters)) {
            return $offers;
        }

        return array_filter($offers, function ($offer) use ($filters) {
            // Filtro por valor mínimo
            if (isset($filters['min_amount']) && $offer['min_amount_cents'] < ($filters['min_amount'] * 100)) {
                return false;
            }

            // Filtro por valor máximo
            if (isset($filters['max_amount']) && $offer['max_amount_cents'] > ($filters['max_amount'] * 100)) {
                return false;
            }

            // Filtro por taxa mínima
            if (isset($filters['min_rate']) && $offer['monthly_interest_rate'] < $filters['min_rate']) {
                return false;
            }

            // Filtro por taxa máxima
            if (isset($filters['max_rate']) && $offer['monthly_interest_rate'] > $filters['max_rate']) {
                return false;
            }

            // Filtro por instituição
            if (isset($filters['institution']) && $offer['institution_name'] !== $filters['institution']) {
                return false;
            }

            // Filtro por modalidade
            if (isset($filters['modality']) && $offer['modality_name'] !== $filters['modality']) {
                return false;
            }

            return true;
        });
    }

    private function applySorting(array $offers, array $filters): array
    {
        $sortBy = $filters['sort_by'] ?? 'monthly_interest_rate';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        usort($offers, function ($a, $b) use ($sortBy, $sortOrder) {
            $valueA = $a[$sortBy] ?? 0;
            $valueB = $b[$sortBy] ?? 0;

            if ($sortOrder === 'desc') {
                return $valueB <=> $valueA;
            }

            return $valueA <=> $valueB;
        });

        return $offers;
    }

    public function getOfferById(string $offerId): ?array
    {
        if (empty(trim($offerId))) {
            throw new InvalidArgumentException('ID da oferta é obrigatório');
        }

        return $this->creditOfferRepository->findById($offerId);
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
