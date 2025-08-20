<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\QueueServiceInterface;
use App\Application\DTOs\CreditRequestDTO;
use App\Application\DTOs\CreditSimulationRequestDTO;
use App\Application\DTOs\CreditSimulationResponseDTO;
use App\Domain\Credit\UseCases\GetCreditOffersUseCase;
use App\Domain\Credit\UseCases\SimulateCreditOfferUseCase;
use App\Domain\Shared\ValueObjects\CPF;
use App\Domain\Shared\ValueObjects\InstallmentCount;
use App\Domain\Shared\ValueObjects\Money;
use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class CreditOfferApplicationService
{
    public function __construct(
        private QueueServiceInterface $queueService,
        private GetCreditOffersUseCase $getCreditOffersUseCase,
        private SimulateCreditOfferUseCase $simulateCreditOfferUseCase
    ) {}

    public function processCreditsRequest(string $cpfString): CreditRequestDTO
    {
        if (empty(trim($cpfString))) {
            throw new InvalidArgumentException('CPF é obrigatório');
        }

        $cpf = new CPF($cpfString);
        $requestId = Uuid::uuid4()->toString();

        // Despachar job para busca assíncrona
        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpf->value, $requestId)
        );

        return new CreditRequestDTO(
            $requestId,
            $cpf->value,
            'processing',
            'Consulta em andamento. Use o request_id para verificar o status.'
        );
    }

    public function getCreditOfferStatus(string $requestId): array
    {
        if (empty(trim($requestId))) {
            throw new InvalidArgumentException('Request ID é obrigatório');
        }

        $status = $this->getCreditOffersUseCase->getOfferStatus($requestId);
        $offers = [];

        if ($status === 'completed' || $status === 'completed_with_failures') {
            $offers = $this->getCreditOffersUseCase->execute($requestId);
        }

        return [
            'request_id' => $requestId,
            'status' => $status,
            'offers' => $offers,
            'total_offers' => count($offers),
        ];
    }

    public function simulateCreditOffer(CreditSimulationRequestDTO $request): CreditSimulationResponseDTO
    {
        $this->validateSimulationRequest($request);

        $cpf = new CPF($request->cpf);
        $requestedAmount = new Money($request->amount);
        $desiredInstallments = new InstallmentCount($request->installments);

        // Buscar ofertas disponíveis para o CPF
        $offers = $this->getCreditOffersUseCase->executeByCpf($cpf);

        if (empty($offers)) {
            return new CreditSimulationResponseDTO(
                $request->cpf,
                $request->amount,
                $request->installments,
                [],
                'Nenhuma oferta disponível para este CPF'
            );
        }

        // Executar simulação para todas as ofertas compatíveis
        $simulations = $this->simulateCreditOfferUseCase->executeMultiple(
            $offers,
            $requestedAmount,
            $desiredInstallments
        );

        return new CreditSimulationResponseDTO(
            $request->cpf,
            $request->amount,
            $request->installments,
            $simulations,
            count($simulations) > 0 ? 'success' : 'no_compatible_offers'
        );
    }

    public function getCreditOffersByCpf(string $cpfString, int $limit = 3): array
    {
        if (empty(trim($cpfString))) {
            throw new InvalidArgumentException('CPF é obrigatório');
        }

        $cpf = new CPF($cpfString);

        return $this->getCreditOffersUseCase->executeByCpf($cpf, $limit);
    }

    private function validateSimulationRequest(CreditSimulationRequestDTO $request): void
    {
        if (empty(trim($request->cpf))) {
            throw new InvalidArgumentException('CPF é obrigatório');
        }

        if ($request->installments < 1) {
            throw new InvalidArgumentException('Número de parcelas deve ser maior que 0');
        }
    }
}
