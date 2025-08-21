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
        private string $cpf,
        private string $creditRequestId
    ) {
        $this->onQueue('credit_offers');
    }

    public function handle(FetchExternalCreditDataUseCase $useCase): void
    {
        try {
            $cpfVO = new CPF($this->cpf);

            Log::info('Iniciando busca de ofertas de crédito', [
                'cpf' => $cpfVO->masked(),
                'attempt' => $this->attempts(),
            ]);

            // Broadcast job started event
            SSEController::broadcastEvent('job.started', [
                'cpf' => $cpfVO->masked(),
                'message' => 'Iniciando busca de ofertas de crédito...',
            ]);

            $offers = $useCase->execute($cpfVO, $this->creditRequestId);

            Log::info('Ofertas de crédito processadas com sucesso', [
                'cpf' => $cpfVO->masked(),
                'offers_count' => count($offers),
                'attempt' => $this->attempts(),
            ]);

            // Broadcast job completed event
            SSEController::broadcastEvent('job.completed', [
                'cpf' => $cpfVO->masked(),
                'ofertas_count' => count($offers),
                'ofertas' => $offers,
                'message' => count($offers) > 0
                    ? 'Consulta finalizada com sucesso!'
                    : 'Nenhuma oferta encontrada para este CPF.',
            ]);

        } catch (\Exception $e) {
            $cpfForLog = null;
            try {
                $cpfForLog = (new CPF($this->cpf))->masked();
            } catch (\Throwable) {
                $cpfForLog = '***';
            }
            Log::warning('Erro ao buscar ofertas de crédito', [
                'cpf' => $cpfForLog,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $cpfForLog = null;
        try {
            $cpfForLog = (new CPF($this->cpf))->masked();
        } catch (\Throwable) {
            $cpfForLog = '***';
        }
        Log::error('Falha definitiva na busca de ofertas de crédito', [
            'cpf' => $cpfForLog,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
            'failed_at' => now()->toISOString(),
        ]);

        // Broadcast final failure event
        SSEController::broadcastEvent('job.failed', [
            'cpf' => $cpfForLog,
            'error' => $exception->getMessage(),
            'message' => 'Erro ao buscar ofertas de crédito. Tente novamente.',
        ]);
    }
}
