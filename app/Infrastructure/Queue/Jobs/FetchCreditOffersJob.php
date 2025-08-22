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
            
            // Check if this is a final error that should not be retried
            if ($this->shouldFailImmediately($e)) {
                Log::error('Erro final - não retentando', [
                    'cpf' => $cpfForLog,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                    'error_type' => 'final',
                ]);
                
                // Fail immediately without retrying
                $this->fail($e);
                return;
            }

            Log::warning('Erro ao buscar ofertas de crédito - será retentado', [
                'cpf' => $cpfForLog,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'error_type' => 'retryable',
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
            'error' => $this->getErrorMessage($exception),
            'message' => 'Erro ao buscar ofertas de crédito. Tente novamente.',
        ]);
    }

    /**
     * Converte erros técnicos em mensagens amigáveis para o usuário
     */
    private function getErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        // Tratar erros específicos da API externa
        if (str_contains($message, 'Falha na comunicação com API externa')) {
            // Extrair mensagem específica se disponível
            if (str_contains($message, 'CPF não encontrado')) {
                return 'CPF não encontrado ou sem ofertas disponíveis.';
            }
            
            if (str_contains($message, '422 Unprocessable Content')) {
                return 'Dados informados são inválidos. Verifique o CPF e tente novamente.';
            }
            
            if (str_contains($message, '404')) {
                return 'Nenhuma oferta foi encontrada para este CPF.';
            }
            
            if (str_contains($message, '500') || str_contains($message, 'Internal Server Error')) {
                return 'O serviço está temporariamente indisponível. Tente novamente em alguns minutos.';
            }
            
            if (str_contains($message, 'timeout') || str_contains($message, 'Connection timed out')) {
                return 'A consulta demorou mais que o esperado. Tente novamente.';
            }
            
            return 'Não foi possível consultar as ofertas de crédito no momento. Tente novamente.';
        }

        // Tratar erros de validação
        if (str_contains($message, 'CPF inválido') || str_contains($message, 'Invalid CPF')) {
            return 'CPF informado é inválido. Verifique os dados e tente novamente.';
        }

        // Tratar erros de conexão
        if (str_contains($message, 'cURL error') || str_contains($message, 'Connection refused')) {
            return 'Erro de conexão. Verifique sua internet e tente novamente.';
        }

        // Tratar erros de JSON
        if (str_contains($message, 'JSON') || str_contains($message, 'json')) {
            return 'Erro ao processar a resposta do servidor. Tente novamente.';
        }

        // Erro genérico
        return 'Ocorreu um erro inesperado. Tente novamente ou entre em contato com o suporte.';
    }

    /**
     * Determina se o erro é final e não deve ser retentado
     */
    private function shouldFailImmediately(Throwable $exception): bool
    {
        $message = $exception->getMessage();
        
        // Erros HTTP finais que não devem ser retentados
        $finalHttpErrors = [
            '401', // Unauthorized - problema de autenticação
            '403', // Forbidden - sem permissão
            '404', // Not Found - recurso não existe
            '422', // Unprocessable Entity - dados inválidos
            '400', // Bad Request - requisição malformada
        ];
        
        foreach ($finalHttpErrors as $httpCode) {
            if (str_contains($message, $httpCode)) {
                return true;
            }
        }
        
        // Erros específicos que são finais
        $finalErrors = [
            'CPF não encontrado',
            'CPF inválido',
            'Invalid CPF',
            'Dados informados são inválidos',
            'Parâmetros inválidos',
            'Requisição malformada',
            'Unauthorized',
            'Forbidden',
            'Access denied',
        ];
        
        foreach ($finalErrors as $errorPattern) {
            if (str_contains($message, $errorPattern)) {
                return true;
            }
        }
        
        // Erros que devem ser retentados (problemas temporários)
        $retryableErrors = [
            'timeout',
            'Connection timed out',
            'Connection refused',
            'Network is unreachable',
            '500', // Internal Server Error
            '502', // Bad Gateway
            '503', // Service Unavailable  
            '504', // Gateway Timeout
            'cURL error',
            'Unable to connect',
        ];
        
        foreach ($retryableErrors as $errorPattern) {
            if (str_contains($message, $errorPattern)) {
                return false; // Deve ser retentado
            }
        }
        
        // Por padrão, erros desconhecidos são considerados retentáveis
        // para evitar perder jobs por engano
        return false;
    }
}
