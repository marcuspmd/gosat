<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Integration\UseCases\FetchExternalCreditDataUseCase;
use App\Domain\Shared\ValueObjects\CPF;
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

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 120;

    public function __construct(
        private string $cpf,
        private string $requestId
    ) {
        $this->onQueue('credit_offers');
    }

    public function handle(FetchExternalCreditDataUseCase $useCase): void
    {
        try {
            Log::info('Iniciando busca de ofertas de crédito', [
                'cpf' => $this->maskCpf($this->cpf),
                'request_id' => $this->requestId,
                'attempt' => $this->attempts(),
            ]);

            $cpf = new CPF($this->cpf);
            $offers = $useCase->execute($cpf, $this->requestId);

            Log::info('Ofertas de crédito processadas com sucesso', [
                'cpf' => $this->maskCpf($this->cpf),
                'request_id' => $this->requestId,
                'offers_count' => count($offers),
                'attempt' => $this->attempts(),
            ]);

            // Despachar jobs individuais para cada oferta se necessário
            foreach ($offers as $offer) {
                if ($offer->status->canRetry()) {
                    SimulateCreditOfferJob::dispatch(
                        $this->cpf,
                        $offer->institution->id,
                        $offer->modality->standardCode->value,
                        $this->requestId
                    );
                }
            }

        } catch (\Exception $e) {
            Log::warning('Erro ao buscar ofertas de crédito', [
                'cpf' => $this->maskCpf($this->cpf),
                'request_id' => $this->requestId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
            ]);

            // Se ainda há tentativas, relançar para retry
            if ($this->attempts() < $this->tries) {
                $delay = $this->backoff[$this->attempts() - 1] ?? 120;
                $this->release($delay);

                return;
            }

            // Última tentativa falhou
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha definitiva na busca de ofertas de crédito', [
            'cpf' => $this->maskCpf($this->cpf),
            'request_id' => $this->requestId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
            'failed_at' => now()->toISOString(),
        ]);

        // TODO: Notificar sistema de monitoramento
        // TODO: Marcar request como falho no banco de dados
    }

    public function retryUntil(): \DateTime
    {
        // Jobs expiram após 1 hora
        return now()->addHour();
    }

    private function maskCpf(string $cpf): string
    {
        if (strlen($cpf) !== 11) {
            return '***';
        }

        return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);
    }
}
