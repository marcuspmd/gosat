<?php

declare(strict_types=1);

namespace App\Domain\Credit\Services;

use App\Domain\Credit\Entities\CreditOfferEntity;

final readonly class CreditOfferRankingService
{
    public function rankOffers(array $offers): array
    {
        if (empty($offers)) {
            return [];
        }

        // Filtrar apenas ofertas válidas
        $validOffers = array_filter($offers, function ($offer) {
            return $offer instanceof CreditOfferEntity && $offer->status->isSuccessful();
        });

        if (empty($validOffers)) {
            return [];
        }

        // Aplicar algoritmo de ranking
        $rankedOffers = $this->applyRankingAlgorithm($validOffers);

        // Limitar a 3 melhores ofertas
        return array_slice($rankedOffers, 0, 3);
    }

    private function applyRankingAlgorithm(array $offers): array
    {
        // Calcular scores para cada oferta
        $offersWithScores = array_map(function (CreditOfferEntity $offer) {
            return [
                'offer' => $offer,
                'score' => $this->calculateOfferScore($offer),
            ];
        }, $offers);

        // Ordenar por score (maior = melhor)
        usort($offersWithScores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Retornar apenas as ofertas ordenadas
        return array_map(function ($item) {
            return $item['offer'];
        }, $offersWithScores);
    }

    private function calculateOfferScore(CreditOfferEntity $offer): float
    {
        $score = 0.0;

        // Peso 1: Taxa de juros (menor = melhor) - 70% do score
        $interestRateScore = $this->calculateInterestRateScore($offer);
        $score += $interestRateScore * 0.7;

        // Peso 2: Valor disponível (maior = melhor) - 30% do score
        $amountScore = $this->calculateAmountScore($offer);
        $score += $amountScore * 0.3;

        return $score;
    }

    private function calculateInterestRateScore(CreditOfferEntity $offer): float
    {
        $rate = $offer->monthlyInterestRate->monthlyRate;

        // Taxa típica do mercado para comparação
        $typicalRanges = $offer->modality->standardCode->typicalInterestRange();
        $minRate = $typicalRanges['min'];
        $maxRate = $typicalRanges['max'];

        // Normalizar taxa (0 = pior taxa possível, 1 = melhor taxa possível)
        if ($maxRate === $minRate) {
            return 0.5;
        }

        $normalizedScore = 1 - (($rate - $minRate) / ($maxRate - $minRate));

        return max(0, min(1, $normalizedScore));
    }

    private function calculateAmountScore(CreditOfferEntity $offer): float
    {
        // Assumindo que valores maiores são melhores para flexibilidade
        // Normalizar baseado em valores típicos do mercado (R$ 1.000 a R$ 500.000)
        $minAmount = 100000; // R$ 1000,00 em centavos
        $maxAmount = 50000000; // R$ 500.000,00 em centavos

        $offerMaxAmount = $offer->maxAmount->amountInCents;

        if ($maxAmount === $minAmount) {
            return 0.5;
        }

        $score = ($offerMaxAmount - $minAmount) / ($maxAmount - $minAmount);

        return max(0, min(1, $score));
    }

    public function rankByTotalCost(array $offers): array
    {
        if (empty($offers)) {
            return [];
        }

        $validOffers = array_filter($offers, function ($offer) {
            return $offer instanceof CreditOfferEntity && $offer->status->isSuccessful();
        });

        usort($validOffers, function (CreditOfferEntity $a, CreditOfferEntity $b) {
            return $a->totalAmount->amountInCents <=> $b->totalAmount->amountInCents;
        });

        return $validOffers;
    }
}
