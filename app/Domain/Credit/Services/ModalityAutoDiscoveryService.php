<?php

declare(strict_types=1);

namespace App\Domain\Credit\Services;

use App\Domain\Credit\Entities\StandardModalityEntity;
use App\Domain\Credit\Repositories\StandardModalityRepositoryInterface;
use App\Domain\Integration\Entities\ModalityMappingEntity;
use App\Domain\Integration\Repositories\ModalityMappingRepositoryInterface;
use Ramsey\Uuid\Uuid;

final readonly class ModalityAutoDiscoveryService
{
    private const DEFAULT_KEYWORDS = [
        'PERSONAL_CREDIT' => ['pessoal', 'personal', 'emprestimo', 'loan'],
        'PAYROLL_CREDIT' => ['consignado', 'payroll', 'folha', 'desconto'],
        'VEHICLE_FINANCING' => ['veiculo', 'vehicle', 'auto', 'carro', 'moto', 'financiamento'],
        'REAL_ESTATE_FINANCING' => ['imobiliario', 'real estate', 'casa', 'imovel', 'habitacao'],
        'CREDIT_CARD' => ['cartao', 'card', 'credito'],
        'OVERDRAFT' => ['especial', 'overdraft', 'cheque', 'limite'],
        'REVOLVING_CREDIT' => ['rotativo', 'revolving', 'renovavel', 'pre-aprovado'],
    ];

    private const INTEREST_RANGES = [
        'PERSONAL_CREDIT' => ['min' => 0.02, 'max' => 0.15],
        'PAYROLL_CREDIT' => ['min' => 0.01, 'max' => 0.03],
        'VEHICLE_FINANCING' => ['min' => 0.008, 'max' => 0.025],
        'REAL_ESTATE_FINANCING' => ['min' => 0.006, 'max' => 0.015],
        'CREDIT_CARD' => ['min' => 0.08, 'max' => 0.20],
        'OVERDRAFT' => ['min' => 0.10, 'max' => 0.25],
        'REVOLVING_CREDIT' => ['min' => 0.05, 'max' => 0.18],
    ];

    public function __construct(
        private StandardModalityRepositoryInterface $standardModalityRepository,
        private ModalityMappingRepositoryInterface $modalityMappingRepository
    ) {}

    public function discoverOrCreateMapping(
        string $institutionId,
        string $externalCode,
        string $modalityName,
        ?string $institutionExternalId = null
    ): ModalityMappingEntity {
        // Primeiro, tentar encontrar mapeamento existente
        $existingMapping = $this->findExistingMapping($institutionId, $externalCode, $institutionExternalId);

        if ($existingMapping) {
            return $existingMapping;
        }

        // Tentar encontrar modalidade padrão baseada no nome
        $standardModality = $this->findOrCreateStandardModality($modalityName);

        // Criar novo mapeamento
        $mapping = new ModalityMappingEntity(
            id: Uuid::uuid4()->toString(),
            institutionId: $institutionId,
            externalCode: $externalCode,
            standardModality: $standardModality,
            modalityName: $modalityName,
            institutionExternalId: $institutionExternalId,
            originalModalityName: $modalityName,
            metadata: [
                'auto_discovered' => true,
                'confidence_score' => $this->calculateConfidenceScore($modalityName, $standardModality),
                'discovery_method' => 'keyword_matching',
            ]
        );

        $this->modalityMappingRepository->save($mapping);

        return $mapping;
    }

    public function findOrCreateStandardModality(string $modalityName): StandardModalityEntity
    {
        $existingModalities = $this->standardModalityRepository->findActive();

        foreach ($existingModalities as $modality) {
            if ($modality->matchesKeyword($modalityName)) {
                return $modality;
            }
        }

        return $this->createNewStandardModality($modalityName);
    }

    public function suggestStandardModalityCode(string $modalityName): string
    {
        $normalizedName = $this->normalizeModalityName($modalityName);

        foreach (self::DEFAULT_KEYWORDS as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedName, $keyword)) {
                    return $code;
                }
            }
        }

        // Se não encontrou padrão conhecido, gerar código baseado no nome
        return $this->generateCodeFromName($modalityName);
    }

    public function calculateConfidenceScore(string $modalityName, StandardModalityEntity $standardModality): float
    {
        $normalizedName = $this->normalizeModalityName($modalityName);
        $matchingKeywords = 0;
        $totalKeywords = count($standardModality->keywords ?: []);

        if ($totalKeywords === 0) {
            return 0.5; // Confiança média quando não há palavras-chave
        }

        foreach ($standardModality->keywords as $keyword) {
            if (str_contains($normalizedName, strtolower($keyword))) {
                $matchingKeywords++;
            }
        }

        return min(1.0, $matchingKeywords / $totalKeywords);
    }

    private function findExistingMapping(
        string $institutionId,
        string $externalCode,
    ): ?ModalityMappingEntity {
        $mapping = $this->modalityMappingRepository->findByInstitutionAndExternalCode($institutionId, $externalCode);

        if ($mapping) {
            return $mapping;
        }

        return null;
    }

    private function createNewStandardModality(string $modalityName): StandardModalityEntity
    {
        $suggestedCode = $this->suggestStandardModalityCode($modalityName);
        $interestRange = $this->determineInterestRange($suggestedCode);
        $keywords = $this->extractKeywords($modalityName);

        $modality = new StandardModalityEntity(
            id: Uuid::uuid4()->toString(),
            code: $suggestedCode,
            name: $this->generateStandardName($modalityName),
            description: "Modalidade auto-descoberta: {$modalityName}",
            typicalInterestRange: $interestRange,
            keywords: $keywords
        );

        $this->standardModalityRepository->save($modality);

        return $modality;
    }

    private function normalizeModalityName(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $name)));
    }

    private function determineInterestRange(string $code): array
    {
        return self::INTEREST_RANGES[$code] ?? ['min' => 0.01, 'max' => 0.10];
    }

    private function extractKeywords(string $modalityName): array
    {
        $normalizedName = $this->normalizeModalityName($modalityName);
        $words = array_filter(explode(' ', $normalizedName), fn ($word) => strlen($word) > 2);

        return array_unique($words);
    }

    private function generateCodeFromName(string $modalityName): string
    {
        $words = explode(' ', strtoupper($this->normalizeModalityName($modalityName)));
        $code = '';

        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $code .= substr($word, 0, 3) . '_';
            }
        }

        return rtrim($code, '_') ?: 'UNKNOWN_MODALITY';
    }

    private function generateStandardName(string $modalityName): string
    {
        return ucwords(strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $modalityName)));
    }
}
