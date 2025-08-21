<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\QueueServiceInterface;
use App\Application\DTOs\CreditRequestDTO;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use Ramsey\Uuid\Uuid;

final readonly class CreditOfferApplicationService
{
    public function __construct(
        private QueueServiceInterface $queueService,
    ) {
    }

    public function processCreditsRequest(string $cpfString): CreditRequestDTO
    {
        $cpf = new CPF($cpfString);
        $creditRequestId = Uuid::uuid4()->toString();

        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpf, $creditRequestId)
        );

        return new CreditRequestDTO(
            $creditRequestId,
            $cpf->value,
            'processing',
            'Consulta em andamento. Use o request_id para verificar o status.'
        );
    }
}
