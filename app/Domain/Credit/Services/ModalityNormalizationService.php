<?php

declare(strict_types=1);

namespace App\Domain\Credit\Services;

use App\Domain\Credit\Entities\CreditOfferEntity;
use App\Domain\Credit\Repositories\CreditModalityRepositoryInterface;
use App\Domain\Credit\Repositories\InstitutionRepositoryInterface;
use App\Domain\Credit\ValueObjects\CreditOfferStatus;
use App\Domain\Integration\Repositories\ModalityMappingRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\InterestRate;
use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class ModalityNormalizationService
{
    public function __construct(
        private ModalityMappingRepositoryInterface $modalityMappingRepository,
        private InstitutionRepositoryInterface $institutionRepository,
        private CreditModalityRepositoryInterface $modalityRepository,
        private ModalityAutoDiscoveryService $autoDiscoveryService
    ) {}

    public function normalizeOffer(CPF $cpf, string $requestId, array $externalOfferData): ?CreditOfferEntity
    {
        try {
            // Validar dados obrigatórios
            $this->validateExternalData($externalOfferData);

            // Buscar ou criar mapeamento de modalidade automaticamente
            $modalityMapping = $this->autoDiscoveryService->discoverOrCreateMapping(
                (string) $externalOfferData['instituicao_id'],
                $externalOfferData['modalidade_codigo'],
                $externalOfferData['modalidade_nome'] ?? $externalOfferData['modalidade_codigo'],
                $externalOfferData['instituicao_external_id'] ?? null
            );

            // Buscar entidades relacionadas
            $institution = $this->institutionRepository->findById(
                (string) $externalOfferData['instituicao_id']
            );

            if ($institution === null) {
                throw new InvalidArgumentException(
                    sprintf('Instituição não encontrada: %d', $externalOfferData['instituicao_id'])
                );
            }

            $modality = $this->modalityRepository->findByStandardModality($modalityMapping->standardModality);

            if ($modality === null) {
                throw new InvalidArgumentException(
                    sprintf('Modalidade padrão não encontrada: %s', $modalityMapping->standardModality->code)
                );
            }

            // Normalizar valores monetários
            $minAmount = new Money($externalOfferData['valor_minimo']);
            $maxAmount = new Money($externalOfferData['valor_maximo']);
            $approvedAmount = new Money($externalOfferData['valor_aprovado'] ?? $externalOfferData['valor_maximo']);

            // Normalizar parcelas
            $minInstallments = new InstallmentCount($externalOfferData['parcelas_minimas']);
            $maxInstallments = new InstallmentCount($externalOfferData['parcelas_maximas']);
            $installments = new InstallmentCount($externalOfferData['parcelas_aprovadas'] ?? $externalOfferData['parcelas_maximas']);

            // Normalizar taxa de juros
            $monthlyInterestRate = new InterestRate($externalOfferData['taxa_juros_mensal']);

            // Criar entidade CreditOffer
            return new CreditOfferEntity(
                id: Uuid::uuid4()->toString(),
                requestId: $requestId,
                cpf: $cpf,
                institution: $institution,
                modality: $modality,
                minAmount: $minAmount,
                maxAmount: $maxAmount,
                approvedAmount: $approvedAmount,
                monthlyInterestRate: $monthlyInterestRate,
                minInstallments: $minInstallments,
                maxInstallments: $maxInstallments,
                installments: $installments,
                status: CreditOfferStatus::COMPLETED
            );

        } catch (\Exception $e) {
            // Log do erro mas não interrompe o processamento de outras ofertas
            return null;
        }
    }

    public function normalizeMultipleOffers(CPF $cpf, string $requestId, array $externalOffersData): array
    {
        $normalizedOffers = [];

        foreach ($externalOffersData as $externalOffer) {
            $normalizedOffer = $this->normalizeOffer($cpf, $requestId, $externalOffer);

            if ($normalizedOffer !== null) {
                $normalizedOffers[] = $normalizedOffer;
            }
        }

        return $normalizedOffers;
    }

    private function validateExternalData(array $data): void
    {
        $requiredFields = [
            'instituicao_id',
            'modalidade_codigo',
            'valor_minimo',
            'valor_maximo',
            'parcelas_minimas',
            'parcelas_maximas',
            'taxa_juros_mensal',
        ];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException(sprintf('Campo obrigatório não encontrado: %s', $field));
            }
        }

        // Validações de tipos e valores
        if (! is_numeric($data['instituicao_id']) || $data['instituicao_id'] <= 0) {
            throw new InvalidArgumentException('ID da instituição deve ser um número positivo');
        }

        if (! is_numeric($data['valor_minimo']) || $data['valor_minimo'] < 0) {
            throw new InvalidArgumentException('Valor mínimo deve ser um número não-negativo');
        }

        if (! is_numeric($data['valor_maximo']) || $data['valor_maximo'] < 0) {
            throw new InvalidArgumentException('Valor máximo deve ser um número não-negativo');
        }

        if ($data['valor_minimo'] > $data['valor_maximo']) {
            throw new InvalidArgumentException('Valor mínimo não pode ser maior que o valor máximo');
        }

        if (! is_numeric($data['parcelas_minimas']) || $data['parcelas_minimas'] < 1) {
            throw new InvalidArgumentException('Parcelas mínimas deve ser um número positivo');
        }

        if (! is_numeric($data['parcelas_maximas']) || $data['parcelas_maximas'] < 1) {
            throw new InvalidArgumentException('Parcelas máximas deve ser um número positivo');
        }

        if ($data['parcelas_minimas'] > $data['parcelas_maximas']) {
            throw new InvalidArgumentException('Parcelas mínimas não podem ser maiores que as máximas');
        }

        if (! is_numeric($data['taxa_juros_mensal']) || $data['taxa_juros_mensal'] < 0) {
            throw new InvalidArgumentException('Taxa de juros mensal deve ser um número não-negativo');
        }
    }
}
