<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Integration\UseCases\FetchExternalCreditDataUseCase;
use App\Domain\Shared\ValueObjects\CPF;
use App\Infrastructure\Http\Controllers\Api\SSEController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchCreditOffersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    public function __construct(
        private CPF $cpf,
        private string $creditRequestId
    ) {
        $this->onQueue('credit_offers');
    }

    public function handle(FetchExternalCreditDataUseCase $useCase): void
    {
        try {
            Log::info('Iniciando busca de ofertas de crédito', [
                'cpf' => $this->cpf->masked(),
                'attempt' => $this->attempts(),
            ]);

            // Broadcast job started event
            SSEController::broadcastEvent('job.started', [
                'cpf' => $this->cpf->masked(),
                'message' => 'Iniciando busca de ofertas de crédito...',
            ]);

            $offers = $useCase->execute($this->cpf, $this->creditRequestId);

            Log::info('Ofertas de crédito processadas com sucesso', [
                'cpf' => $this->cpf->masked(),
                'offers_count' => count($offers),
                'attempt' => $this->attempts(),
            ]);

            // Broadcast job completed event
            SSEController::broadcastEvent('job.completed', [
                'cpf' => $this->cpf->masked(),
                'ofertas_count' => count($offers),
                'ofertas' => $offers,
                'message' => count($offers) > 0
                    ? 'Consulta finalizada com sucesso!'
                    : 'Nenhuma oferta encontrada para este CPF.',
            ]);

        } catch (\Exception $e) {
            Log::warning('Erro ao buscar ofertas de crédito', [
                'cpf' => $this->cpf->masked(),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha definitiva na busca de ofertas de crédito', [
            'cpf' => $this->cpf->masked(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
            'failed_at' => now()->toISOString(),
        ]);

        // Broadcast final failure event
        SSEController::broadcastEvent('job.failed', [
            'cpf' => $this->cpf->masked(),
            'error' => $exception->getMessage(),
            'message' => 'Erro ao buscar ofertas de crédito. Tente novamente.',
        ]);
    }
}
