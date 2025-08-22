# Camadas DDD

## 1. Visão Geral das Camadas

### Domain Layer (Núcleo do Negócio)
- **Responsabilidade**: Contém toda a lógica de negócio
- **Conteúdo**: Entities, Value Objects, Use Cases, Domain Services, Repositories (interfaces)
- **Regra**: Não depende de nenhuma outra camada
- **Exemplo**: Validação de CPF, cálculo de juros, regras de ranking

### Application Layer (Orquestração)
- **Responsabilidade**: Orquestra os Use Cases e coordena operações
- **Conteúdo**: Application Services, DTOs, Contracts/Interfaces
- **Regra**: Depende apenas do Domain
- **Exemplo**: Coordenar múltiplos Use Cases, gerenciar transações, converter DTOs

### Infrastructure Layer (Implementações Técnicas)
- **Responsabilidade**: Implementações de infraestrutura
- **Conteúdo**: Repositories concretos, APIs externas, Jobs, Persistence
- **Regra**: Implementa interfaces do Domain e Application
- **Exemplo**: Eloquent repositories, HTTP clients, Queue jobs

### Interface Layer (Pontos de Entrada)
- **Responsabilidade**: Interfaces com o mundo externo
- **Conteúdo**: Controllers, Commands, Event Listeners
- **Regra**: Coordena chamadas para Application Services
- **Exemplo**: API endpoints, CLI commands, Web pages

## 2. Fluxo de Dados

```
Interface → Application → Domain
    ↓           ↓          ↓
Infrastructure ← ← ← ← ← ← ←
```

### Exemplo Prático: Buscar Ofertas de Crédito

#### 1. Interface Layer (Controller)
```php
// Infrastructure/Http/Controllers/Api/CreditOfferController.php
class CreditOfferController extends Controller
{
    public function creditRequest(Request $request): JsonResponse
    {
        // Apenas coordena a chamada - sem lógica de negócio
        $requestId = $this->applicationService->processCreditsRequest(
            $request->input('cpf')
        );

        return response()->json([
            'request_id' => $requestId,
            'message' => 'Consulta em processamento. Use SSE para acompanhar.',
            'sse_endpoint' => '/api/v1/sse'
        ], 202);
    }
}
```

#### 2. Application Layer (Orquestração)
```php
// Application/Services/CreditOfferApplicationService.php
final readonly class CreditOfferApplicationService
{
    public function processCreditsRequest(string $cpfString): string
    {
        // 1. Valida CPF usando Value Object
        $cpf = new CPF($cpfString);
        $creditRequestId = Uuid::uuid4()->toString();

        // 2. Soft delete old offers (business rule)
        $this->creditOfferRepository->softDeleteByCpf($cpf);

        // 3. Broadcast immediate feedback via SSE
        SSEController::broadcastEvent('request.queued', [
            'cpf' => $cpf->masked(),
            'request_id' => $creditRequestId,
            'message' => 'Consulta adicionada à fila...',
        ]);

        // 4. Despacha job para processamento assíncrono
        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpf->value, $creditRequestId)
        );

        return $creditRequestId;
    }
}
```

#### 3. Domain Layer (Use Cases) - EM IMPLEMENTAÇÃO
```php
// Domain/Integration/UseCases/FetchExternalCreditDataUseCase.php
final readonly class FetchExternalCreditDataUseCase
{
    public function execute(CPF $cpf, string $creditRequestId): array
    {
        // Lógica pura de negócio para buscar ofertas externas
        $externalData = $this->apiService->fetchCreditOffers($cpf);

        // Mapeia dados externos para entidades de domínio
        $creditOffers = $this->mapper->mapToEntities(
            $externalData,
            $cpf,
            $creditRequestId
        );

        // Persiste as ofertas
        foreach ($creditOffers as $offer) {
            $this->creditOfferRepository->save($offer);
        }

        return $creditOffers;
    }
}

// Domain/Credit/UseCases/ - ⚠️ PASTA VAZIA - LÓGICA ESTÁ NOS SERVICES
// Aqui deveriam estar:
// - GetCreditOffersUseCase
// - CalculateCreditOfferUseCase
// - RankCreditOffersUseCase
```

#### 4. Infrastructure Layer (Job) - IMPLEMENTADO
```php
// Infrastructure/Queue/Jobs/FetchCreditOffersJob.php
class FetchCreditOffersJob implements ShouldQueue
{
    public function handle(FetchExternalCreditDataUseCase $useCase): void
    {
        // Job apenas chama o Use Case do Domain
        $cpfVO = new CPF($this->cpf);

        // Broadcast progresso via SSE
        SSEController::broadcastEvent('job.started', [
            'cpf' => $cpfVO->masked(),
            'message' => 'Iniciando busca...',
        ]);

        $offers = $useCase->execute($cpfVO, $this->creditRequestId);

        // Broadcast resultado via SSE
        SSEController::broadcastEvent('job.completed', [
            'cpf' => $cpfVO->masked(),
            'ofertas_count' => count($offers),
            'ofertas' => $offers,
        ]);
    }
}
```

## 3. Principais Diferenças

### Use Cases (Domain) vs Application Services

| Aspecto | Use Cases (Domain) | Application Services |
|---------|-------------------|---------------------|
| **Localização** | `Domain/*/UseCases/` | `Application/Services/` |
| **Responsabilidade** | Lógica de negócio específica | Orquestração de Use Cases |
| **Dependências** | Apenas Domain | Domain + Infrastructure contracts |
| **Testabilidade** | Testes unitários puros | Testes de integração |
| **Exemplo** | `CalculateCreditOfferUseCase` | `CreditOfferApplicationService` |

### Exemplo de Responsabilidades

#### ❌ **Errado** - Use Case fazendo orquestração:
```php
// ERRADO: Use Case não deveria lidar com filas
class GetCreditOffersUseCase
{
    public function execute(string $cpf): string
    {
        $requestId = Uuid::uuid4()->toString();

        // ❌ Use Case não deveria despachar jobs
        $this->queueService->dispatch(new FetchCreditOffersJob($cpf, $requestId));

        return $requestId;
    }
}
```

#### ✅ **Correto** - Use Case com lógica pura:
```php
// CORRETO: Use Case com lógica de negócio pura
class GetCreditOffersUseCase
{
    public function execute(CPF $cpf): Collection
    {
        // ✅ Lógica pura de negócio
        $externalOffers = $this->apiService->fetchOffers($cpf);

        return $externalOffers->map(function ($offer) {
            return $this->normalizationService->normalize($offer);
        });
    }
}
```

#### ✅ **Correto** - Application Service orquestrando:
```php
// CORRETO: Application Service orquestra
class CreditOfferApplicationService
{
    public function processCreditsRequest(string $cpfString): CreditRequestDTO
    {
        // ✅ Orquestra Use Cases e infraestrutura
        $cpf = new CPF($cpfString);
        $requestId = Uuid::uuid4()->toString();

        $this->queueService->dispatch(
            new FetchCreditOffersJob($cpf->value(), $requestId)
        );

        return new CreditRequestDTO($requestId, $cpf->formatted(), 'processing');
    }
}
```

## 4. Benefícios dessa Arquitetura

### 1. **Testabilidade**
- Use Cases são facilmente testáveis sem infraestrutura
- Application Services podem ser testados com mocks
- Camadas bem separadas facilitam testes isolados

### 2. **Manutenibilidade**
- Lógica de negócio isolada no Domain
- Mudanças de infraestrutura não afetam negócio
- Responsabilidades bem definidas

### 3. **Flexibilidade**
- Fácil trocar implementações (SQLite → PostgreSQL)
- Adicionar novos canais (API → CLI → GraphQL)
- Modificar orquestração sem afetar negócio

### 4. **Escalabilidade**
- Use Cases podem ser reutilizados
- Application Services podem ser compostos
- Infraestrutura pode ser otimizada independentemente

## 5. Estrutura Final Atual

```
app/
├── Domain/                          # ✅ Use Cases implementados onde necessário
│   ├── Credit/
│   │   ├── UseCases/
│   │   ├── Entities/               # ✅ Implementado
│   │   ├── Services/               # ✅ Implementado
│   │   └── Repositories/           # ✅ Interfaces implementadas
│   ├── Customer/
│   │   ├── Entities/               # ✅ Implementado
│   │   └── Repositories/           # ✅ Implementado
│   ├── Integration/
│   │   ├── UseCases/               # ✅ FetchExternalCreditDataUseCase
│   │   ├── Services/               # ✅ ExternalCreditApiService
│   │   ├── Mappers/                # ✅ ExternalCreditMapper
│   │   └── Contracts/              # ✅ Interfaces
│   └── Shared/
│       ├── ValueObjects/           # ✅ PHP 8.4 Property Hooks
│       ├── DTOs/                   # ✅ ExternalCreditDto
│       └── Enums/                  # ✅ CreditOfferStatus
├── Application/                     # ✅ Apenas orquestração
│   ├── Services/                   # ✅ CreditOfferApplicationService
│   ├── DTOs/                       # ✅ Request/Response DTOs
│   └── Contracts/                  # ✅ QueueServiceInterface
├── Infrastructure/                  # ✅ Implementações técnicas
│   ├── Queue/Jobs/                 # ✅ FetchCreditOffersJob com SSE
│   ├── Http/Controllers/           # ✅ API + SSE Controllers
│   ├── Persistence/                # ✅ Estrutura para Eloquent
│   └── ExternalServices/           # ✅ Integração APIs externas
└── Http/                           # ✅ Controllers padrão Laravel
    ├── Controllers/
    └── Middleware/
```
