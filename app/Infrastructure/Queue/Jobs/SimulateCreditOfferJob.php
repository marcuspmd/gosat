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

class SimulateCreditOfferJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public array $backoff = [15, 30]; // Backoff mais rápido para ofertas individuais
    public int $timeout = 60; // 1 minuto de timeout

    public function __construct(
        private string $cpf,
        private string $institutionId,
        private string $modalityCode,
        private string $requestId
    ) {
        $this->onQueue('credit_simulations');
    }

    public function handle(FetchExternalCreditDataUseCase $useCase): void
    {
        try {
            Log::debug('Processando simulação de oferta individual', [
                'cpf' => $this->maskCpf($this->cpf),
                'institution_id' => $this->institutionId,
                'modality_code' => $this->modalityCode,
                'request_id' => $this->requestId,
                'attempt' => $this->attempts(),
            ]);

            $cpf = new CPF($this->cpf);
            $offer = null;

            if ($offer !== null) {
                Log::debug('Simulação de oferta processada', [
                    'cpf' => $this->maskCpf($this->cpf),
                    'institution_id' => $this->institutionId,
                    'offer_id' => $offer->id,
                    'status' => $offer->status->value,
                ]);
            } else {
                Log::debug('Nenhuma oferta retornada para a simulação', [
                    'cpf' => $this->maskCpf($this->cpf),
                    'institution_id' => $this->institutionId,
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Erro na simulação de oferta individual', [
                'cpf' => $this->maskCpf($this->cpf),
                'institution_id' => $this->institutionId,
                'request_id' => $this->requestId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $delay = $this->backoff[$this->attempts() - 1] ?? 30;
                $this->release($delay);

                return;
            }

            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha na simulação de oferta individual', [
            'cpf' => $this->maskCpf($this->cpf),
            'institution_id' => $this->institutionId,
            'modality_code' => $this->modalityCode,
            'request_id' => $this->requestId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function retryUntil(): \DateTime
    {
        // Jobs de simulação expiram após 30 minutos
        return now()->addMinutes(30);
    }

    private function maskCpf(string $cpf): string
    {
        if (strlen($cpf) !== 11) {
            return '***';
        }

        return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);
    }
}
