<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\QueueServiceInterface;
use App\Domain\Credit\Repositories\CreditOfferRepositoryInterface;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Queue\Jobs\FetchCreditOffersJob;
use Ramsey\Uuid\Uuid;

final readonly class CreditOfferApplicationService
{
    public function __construct(
        private QueueServiceInterface $queueService,
        private CreditOfferRepositoryInterface $creditOfferRepository,
    ) {}

    public function processCreditsRequest(string $cpfString): string
    {
        $cpf = new CPF($cpfString);
        $creditRequestId = Uuid::uuid4()->toString();

        // Soft delete old offers for this CPF to ensure fresh data
        $this->creditOfferRepository->softDeleteByCpf($cpf);

        // Send immediate "processing" event before dispatching job
        \App\Infrastructure\Http\Controllers\Api\SSEController::broadcastEvent('request.queued', [
            'cpf' => $cpf->masked(),
            'request_id' => $creditRequestId,
            'message' => 'Consulta adicionada à fila de processamento...',
        ]);

        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpf->value, $creditRequestId)
        );

        return $creditRequestId;
    }
}
