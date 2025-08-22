# Arquitetura DDD - Sistema de Consulta de Crédito

## 1. Visão Geral

Este documento define a arquitetura baseada em Domain-Driven Design (DDD) para o sistema de consulta de crédito, incluindo a normalização de códigos de modalidades entre instituições e o uso de filas para consultas externas com retry automático.

## 2. Bounded Contexts

### 2.1 Credit Context (Contexto de Crédito)
- **Responsabilidade:** Gerenciar ofertas de crédito, simulações e instituições financeiras
- **Agregados:** CreditOffer, Institution, CreditModality

### 2.2 Customer Context (Contexto de Cliente)
- **Responsabilidade:** Gerenciar dados e validações de CPF
- **Agregados:** Customer

### 2.3 Integration Context (Contexto de Integração)
- **Responsabilidade:** Gerenciar integrações com APIs externas
- **Agregados:** ExternalApiRequest, ModalityMapping

## 3. Estrutura de Pastas (Estado Atual)

```
app/
├── Domain/                          # Camada de Domínio
│   ├── Credit/                      # Bounded Context de Crédito
│   │   ├── Entities/
│   │   │   ├── CreditOfferEntity.php
│   │   │   ├── InstitutionEntity.php
│   │   │   └── CreditModalityEntity.php
│   │   ├── Repositories/
│   │   │   ├── CreditOfferRepositoryInterface.php
│   │   │   ├── InstitutionRepositoryInterface.php
│   │   │   └── CreditModalityRepositoryInterface.php
│   │   ├── Services/
│   │   │   └── CreditCalculatorService.php
│   │   ├── UseCases/
│   │   └── Events/                  # Implementado
│   ├── Customer/                    # Bounded Context de Cliente
│   │   ├── Entities/
│   │   │   └── CustomerEntity.php
│   │   └── Repositories/
│   │       └── CustomerRepositoryInterface.php
│   ├── Integration/                 # Bounded Context de Integração
│   │   ├── Contracts/
│   │   │   ├── ExternalCreditApiServiceInterface.php
│   │   │   └── ExternalCreditMapperInterface.php
│   │   ├── Mappers/
│   │   │   └── ExternalCreditMapper.php
│   │   ├── Services/
│   │   │   └── ExternalCreditApiService.php
│   │   └── UseCases/
│   │       └── FetchExternalCreditDataUseCase.php
│   └── Shared/                      # Value Objects Compartilhados
│       ├── ValueObjects/
│       │   ├── CPF.php              # Com Property Hooks PHP 8.4
│       │   ├── Money.php
│       │   ├── InterestRate.php
│       │   └── InstallmentCount.php
│       ├── DTOs/
│       │   └── ExternalCreditDto.php
│       └── Enums/
│           └── CreditOfferStatus.php
├── Application/                     # Camada de Aplicação (Orquestração)
│   ├── Services/
│   │   └── CreditOfferApplicationService.php  # ✅ Implementado
│   ├── DTOs/
│   │   ├── CreditRequestDTO.php              # ✅ Implementado
│   │   ├── CreditSimulationRequestDTO.php    # ✅ Implementado
│   │   └── CreditSimulationResponseDTO.php   # ✅ Implementado
│   └── Contracts/
│       └── QueueServiceInterface.php         # ✅ Implementado
├── Infrastructure/                  # Camada de Infraestrutura
│   ├── Persistence/
│   │   └── Eloquent/
│   │       ├── Models/               # Models Eloquent padrão Laravel
│   │       └── Repositories/        # Implementações dos Repositories
│   ├── ExternalServices/            # APIs Externas
│   ├── Queue/
│   │   └── Jobs/
│   │       └── FetchCreditOffersJob.php     # ✅ Implementado
│   └── Http/                        # ⚠️ Movido para Infrastructure
│       ├── Controllers/
│       │   └── Api/
│       │       ├── CreditOfferController.php  # ✅ Implementado
│       │       └── SSEController.php          # ✅ SSE para tempo real
│       ├── Requests/
│       │   └── CreditSimulationRequest.php   # ✅ Implementado
│       ├── Resources/               # API Resources
│       └── Swagger/                 # Documentação API
└── Http/                            # Controllers padrão Laravel
    ├── Controllers/
    └── Middleware/
```

## 4. Normalização de Modalidades

### 4.1 Problema
Diferentes instituições usam códigos diferentes para a mesma modalidade:
- Banco PingApp: "crédito pessoal" = "3"
- Financeira Assert: "crédito pessoal" = "a50ed2ed-2b8b-4cc7-ac95-71a5568b34ce"

## 5. Implementação com Filas

### 5.1 Use Cases de Domínio

Os Use Cases ficam **dentro do Domain** pois representam as regras de negócio e casos de uso específicos de cada contexto:

```php
// Domain/Credit/UseCases/GetCreditOffersUseCase.php
class GetCreditOffersUseCase
{
    public function __construct(
        private CreditOfferRepositoryInterface $repository,
        private ExternalCreditApiService $apiService,
        private ModalityNormalizationService $normalizationService
    ) {}

    public function execute(CPF $cpf): Collection
    {
        // Lógica pura de negócio para buscar ofertas
        $externalOffers = $this->apiService->fetchOffers($cpf);

        return $externalOffers->map(function ($offer) {
            return $this->normalizationService->normalize($offer);
        });
    }
}
```

### 5.2 Application Services (Orquestração)

A camada Application contém apenas a **orquestração** dos Use Cases:

```php
// Application/Services/CreditOfferApplicationService.php
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
        SSEController::broadcastEvent('request.queued', [
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
```

### 5.3 Jobs de Fila

```php
// Infrastructure/Queue/Jobs/FetchCreditOffersJob.php
class FetchCreditOffersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

            // Broadcast job started event via SSE
            SSEController::broadcastEvent('job.started', [
                'cpf' => $cpfVO->masked(),
                'message' => 'Iniciando busca de ofertas de crédito...',
            ]);

            $offers = $useCase->execute($cpfVO, $this->creditRequestId);

            // Broadcast job completed event via SSE
            SSEController::broadcastEvent('job.completed', [
                'cpf' => $cpfVO->masked(),
                'ofertas_count' => count($offers),
                'ofertas' => $offers,
                'message' => count($offers) > 0
                    ? 'Consulta finalizada com sucesso!'
                    : 'Nenhuma oferta encontrada para este CPF.',
            ]);

        } catch (\Exception $e) {
            // Error handling and retry logic...
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to fetch credit offers', [
            'cpf' => $this->cpf,
            'request_id' => $this->creditRequestId,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### 5.4 Controllers (Interface Layer)

Controllers apenas coordenam a chamada dos Application Services:

```php
// Infrastructure/Http/Controllers/Api/CreditOfferController.php
class CreditOfferController extends Controller
{
    public function __construct(
        private readonly CreditOfferApplicationService $applicationService,
        private readonly CreditOfferRepositoryInterface $creditOfferRepository,
        private readonly CreditCalculatorService $creditCalculatorService
    ) {}

    public function creditRequest(Request $request): JsonResponse
    {
        $cpf = $request->input('cpf');

        // Controller apenas coordena, sem lógica de negócio
        $requestId = $this->applicationService->processCreditsRequest($cpf);

        return response()->json([
            'request_id' => $requestId,
            'message' => 'Consulta em processamento. Use SSE para acompanhar o progresso.',
            'sse_endpoint' => '/api/v1/sse'
        ], 202);
    }

    public function simulate(Request $request): JsonResponse
    {
        // Implementação da simulação de crédito
        // usando CreditCalculatorService
    }
}
```

### 5.5 Diferença Entre Use Cases e Application Services

**Use Cases (Domain Layer):**
- Contêm a lógica de negócio específica
- Trabalham com entidades e value objects
- São testáveis isoladamente
- Representam um caso de uso do negócio

**Application Services (Application Layer):**
- Orquestram múltiplos Use Cases
- Lidam com infraestrutura (filas, APIs, etc.)
- Coordenam transações
- Convertem DTOs para objetos de domínio

### 5.6 Processamento Assíncrono
```php
// Application/Services/CreditOfferApplicationService.php
class CreditOfferApplicationService
{
    public function __construct(
        private QueueServiceInterface $queueService,
        private CreditOfferRepositoryInterface $repository
    ) {}

    public function processCreditsRequest(string $cpfString): CreditRequestDTO
    {
        $requestId = Uuid::uuid4()->toString();

        // Application Service orquestra, não contém lógica de negócio
        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpfString, $requestId)
        );

        return new CreditRequestDTO($requestId, $cpfString, 'processing');
    }
}
```

## 6. Value Objects Tipados

### 6.1 CPF Value Object (Implementado com PHP 8.4 Property Hooks)
```php
// Domain/Shared/ValueObjects/CPF.php
final class CPF
{
    public string $value {
        set {
            $cleaned = preg_replace('/\D/', '', $value);

            if (strlen($cleaned) !== 11) {
                throw new InvalidArgumentException('CPF deve ter 11 dígitos');
            }

            if (! $this->validateCPF($cleaned)) {
                throw new InvalidArgumentException('CPF inválido');
            }

            $this->value = $cleaned;
        }
    }

    public string $formatted {
        get => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->value);
    }

    public function __construct(string $cpf)
    {
        $this->value = $cpf;
    }

    public function masked(): string
    {
        return substr($this->value, 0, 3) . '.***.***-' . substr($this->value, -2);
    }

    public function asString(): string
    {
        return $this->value;
    }

    private function validateCPF(string $cpf): bool
    {
        // Implementação da validação de CPF
        // Para desenvolvimento, aceita CPFs de teste
        $testCpfs = ['11111111111', '12312312312', '22222222222'];
        if (in_array($cpf, $testCpfs, true)) {
            return true;
        }
        // Lógica de validação real aqui...
        return strlen($cpf) === 11 && !preg_match('/^(\d)\1{10}$/', $cpf);
    }
}
```

### 6.2 Money Value Object
```php
// Domain/Shared/ValueObjects/Money.php
final class Money
{
    private int $amount; // Valor em centavos

    public function __construct(float $value)
    {
        $this->amount = (int) round($value * 100);
    }

    public function value(): float
    {
        return $this->amount / 100;
    }

    public function formatted(): string
    {
        return 'R$ ' . number_format($this->value(), 2, ',', '.');
    }

    public function add(Money $other): Money
    {
        return new Money(($this->amount + $other->amount) / 100);
    }
}
```

## 7. Serviços de Domínio

### 7.1 Ranking de Ofertas
```php
// Domain/Credit/Services/CreditOfferRankingService.php
class CreditOfferRankingService
{
    public function rankOffers(array $offers): array
    {
        usort($offers, function (CreditOffer $a, CreditOffer $b) {
            // Ordena por menor taxa de juros
            $interestComparison = $a->getInterestRate()->value() <=> $b->getInterestRate()->value();

            if ($interestComparison === 0) {
                // Se taxas iguais, ordena por maior valor disponível
                return $b->getMaxAmount()->value() <=> $a->getMaxAmount()->value();
            }

            return $interestComparison;
        });

        return array_slice($offers, 0, 3); // Máximo 3 ofertas
    }
}
```

### 7.2 Calculadora de Crédito
```php
// Domain/Credit/Services/CreditCalculatorService.php
class CreditCalculatorService
{
    public function calculateTotalAmount(
        Money $requestedAmount,
        InterestRate $monthlyRate,
        InstallmentCount $installments
    ): Money {
        $rate = $monthlyRate->value();
        $periods = $installments->value();

        // Fórmula de juros compostos
        $factor = pow(1 + $rate, $periods);
        $monthlyPayment = $requestedAmount->value() * ($rate * $factor) / ($factor - 1);
        $totalAmount = $monthlyPayment * $periods;

        return new Money($totalAmount);
    }
}
```

## 8. Migrations e Banco de Dados - IMPLEMENTADO

### 8.1 Migrations Existentes
```
database/migrations/
├── 2025_08_10_000001_create_institutions_table.php
├── 2025_08_10_000002_create_credit_modalities_table.php
├── 2025_08_10_000003_create_customers_table.php
├── 2025_08_10_000004_create_credit_offers_table.php
└── 2025_08_21_163642_add_request_id_to_credit_offers_table.php
```

### 8.2 Estrutura das Tabelas Principais
```sql
-- Tabela de Ofertas de Crédito (Simplificada)
CREATE TABLE credit_offers (
    id UUID PRIMARY KEY,
    cpf VARCHAR(11),
    request_id UUID,
    institution_id BIGINT,
    modality_code VARCHAR(255),
    min_installments INTEGER,
    max_installments INTEGER,
    min_amount_cents INTEGER,
    max_amount_cents INTEGER,
    monthly_interest_rate DECIMAL(8,6),
    status ENUM('pending', 'completed', 'failed'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP -- Soft deletes
);
```

## 9. APIs e Integração - IMPLEMENTADO

### 9.1 Interface da API
```php
// Infrastructure/Http/Controllers/Api/CreditOfferController.php
class CreditOfferController extends Controller
{
    public function creditRequest(Request $request): JsonResponse
    {
        $requestId = $this->applicationService->processCreditsRequest(
            $request->input('cpf')
        );

        return response()->json([
            'request_id' => $requestId,
            'message' => 'Consulta em processamento. Use SSE para acompanhar.',
            'sse_endpoint' => '/api/v1/sse'
        ], 202);
    }

    public function simulate(Request $request): JsonResponse
    {
        // Simulação usando CreditCalculatorService
        $result = $this->creditCalculatorService->calculateTotalAmount(
            new Money($request->input('amount')),
            new InterestRate($request->input('interest_rate')),
            new InstallmentCount($request->input('installments'))
        );

        return response()->json([
            'simulation' => CreditSimulationResource::make($result)
        ]);
    }
}
```

### 9.2 Server-Sent Events (SSE) para Tempo Real
```php
// Infrastructure/Http/Controllers/Api/SSEController.php
class SSEController extends Controller
{
    public static function broadcastEvent(string $event, array $data): void
    {
        // Implementação SSE para updates em tempo real
        // Permite acompanhar o progresso das consultas
    }
}
```

### 9.3 Rotas API Implementadas
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('/credit/request', [CreditOfferController::class, 'creditRequest']);
    Route::post('/credit/simulate', [CreditOfferController::class, 'simulate']);
    Route::get('/sse', [SSEController::class, 'stream']);
    Route::get('/health', [HealthController::class, 'check']);
});
```
    }
}
```

## 10. Testes - ESTRUTURA IMPLEMENTADA

### 10.1 Estrutura de Testes Atual
```
tests/
├── Pest.php                        # ✅ Configuração Pest
├── TestCase.php                     # ✅ Base TestCase
├── Helpers/
│   └── CpfHelper.php               # ✅ Helpers para testes
├── Unit/
│   ├── Domain/
│   │   ├── Customer/Entities/      # ✅ Testes de entidades
│   │   └── Shared/
│   │       ├── ValueObjects/       # ✅ Testes de VOs
│   │       └── Enums/              # ✅ Testes de enums
│   ├── Application/
│   │   ├── Services/               # ✅ Application Services
│   │   └── DTOs/                   # ✅ DTOs
│   └── Infrastructure/
│       ├── Http/                   # ✅ Controllers, Resources
│       ├── Queue/Jobs/             # ✅ Queue Jobs
│       └── Persistence/            # ✅ Repositories
├── Feature/
│   ├── Domain/
│   │   ├── Credit/Entities/        # ✅ Feature tests
│   │   └── Integration/            # ✅ API integration
│   └── Api/                        # API endpoint tests
└── Integration/                     # End-to-end tests
```

### 10.2 Comando para Executar Testes
```bash
make test                           # Executa todos os testes em paralelo
make test -- --filter=CPF          # Executa testes específicos
```
