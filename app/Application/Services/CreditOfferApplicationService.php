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
    ) {
    }

    public function processCreditsRequest(string $cpfString): string
    {
        $cpf = new CPF($cpfString);
        $creditRequestId = Uuid::uuid4()->toString();

        // Soft delete old offers for this CPF to ensure fresh data
        $this->creditOfferRepository->softDeleteByCpf($cpf);

        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpf, $creditRequestId)
        );

        return $creditRequestId;
    }
}
