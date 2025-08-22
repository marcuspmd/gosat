
# GoSat - Sistema de Consulta de Crédito

> Sistema DDD em Laravel para consulta e simulação de ofertas de crédito com processamento assíncrono e tempo real.

## 🎯 Funcionalidades Implementadas

- ✅ **Consulta de Crédito**: Busca ofertas por CPF via API externa
- ✅ **Simulação Financeira**: Cálculo de parcelas e juros
- ✅ **Processamento Assíncrono**: Queue jobs com retry automático
- ✅ **Tempo Real**: Server-Sent Events (SSE) para progresso live
- ✅ **Arquitetura DDD**: Domain, Application, Infrastructure layers
- ✅ **Value Objects**: CPF, Money, InterestRate (PHP 8.4 Property Hooks)
- ✅ **API REST**: Endpoints documentados com Swagger
- ✅ **Frontend**: Vue.js + Inertia.js + Vite
- ✅ **Docker**: Ambiente completo containerizado
- ✅ **Testes**: Pest com execução paralela
- ✅ **CI/CD**: GitHub Actions pipeline

## 🚀 Setup Completo (1 comando)

```bash
# Clone e configure
git clone <repo-url>
cd gosat
cp .env.example .env

# 🎯 Setup automatizado - faz tudo!
make dev-ready

# ✅ Pronto! Acesse: http://localhost:8080
```

### O que `make dev-ready` faz:
1. ⬆️ Sobe todos os containers Docker
2. 📦 Instala dependências PHP (Composer)
3. 🗄️ Executa migrations do banco
4. ⚡ Otimiza caches Laravel
5. 🎨 Instala dependências Node.js
6. 🏗️ Builda assets frontend (Vite)

## 🛠️ Comandos de Desenvolvimento

```bash
# Ambiente
make up                 # Sobe containers
make down              # Para containers
make exec -- bash     # Shell no container

# Dependencies & Build
make composer          # Instalar deps PHP
make npm -- install   # Instalar deps Node.js
make assets-build      # Build produção
make assets-dev        # Dev server Vite (HMR)

# Database
make migrate           # Executar migrations
make migrate-fresh     # Reset e migrar
make db-seed          # Executar seeders

# Testes & Quality
make test             # Todos os testes (paralelo)
make pint             # Fix code style
make pint-test        # Check code style

# Laravel
make artisan -- ...  # Comandos Artisan
make optimize-reload  # Recompila caches
```

## 🌐 URLs e Endpoints

### 🖥️ **Aplicação**
- **Frontend**: http://localhost:8080
- **Vite Dev**: http://localhost:5173
- **SSE Stream**: http://localhost:8080/api/v1/sse

### 📡 **API Endpoints**
```bash
# Consulta de Crédito
POST /api/v1/credit/request
{
  "cpf": "12345678909"
}

# Simulação Financeira
POST /api/v1/credit/simulate
{
  "amount": 10000,
  "interest_rate": 0.02,
  "installments": 12
}

# Health Check
GET /api/v1/health
```

### ⚡ **Server-Sent Events**
```javascript
// Frontend pode ouvir progresso em tempo real
const eventSource = new EventSource('/api/v1/sse');
eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log(data); // {event: 'job.completed', data: {...}}
};
```

## 🏗️ Arquitetura DDD

```
app/
├── Domain/                    # 🧠 Regras de negócio
│   ├── Credit/               # Contexto de Crédito
│   │   ├── Entities/         # ✅ CreditOfferEntity, InstitutionEntity
│   │   ├── Services/         # ✅ CreditCalculatorService
│   │   ├── Repositories/     # ✅ Interfaces
│   │   └── UseCases/         # ⚠️ TODO: MIGRAR DE SERVICES PARA USE CASES
│   ├── Customer/             # Contexto de Cliente
│   │   ├── Entities/         # ✅ CustomerEntity
│   │   └── Repositories/     # ✅ CustomerRepositoryInterface
│   ├── Integration/          # Contexto de Integração
│   │   ├── UseCases/         # ✅ FetchExternalCreditDataUseCase
│   │   ├── Services/         # ✅ ExternalCreditApiService
│   │   └── Mappers/          # ✅ ExternalCreditMapper
│   └── Shared/               # Value Objects
│       ├── ValueObjects/     # ✅ CPF, Money, InterestRate
│       ├── DTOs/             # ✅ ExternalCreditDto
│       └── Enums/            # ✅ CreditOfferStatus
├── Application/              # 🔄 Orquestração
│   ├── Services/             # ✅ CreditOfferApplicationService
│   ├── DTOs/                 # ✅ Request/Response DTOs
│   └── Contracts/            # ✅ QueueServiceInterface
└── Infrastructure/           # 🔧 Implementações técnicas
    ├── Http/Controllers/     # ✅ API + SSE Controllers
    ├── Queue/Jobs/           # ✅ FetchCreditOffersJob
    └── ExternalServices/     # ✅ Integração APIs externas
```

### 🎯 **Padrões Implementados**
- **Value Objects**: Validação e encapsulamento (PHP 8.4 Property Hooks)
- **Entities**: Lógica de domínio encapsulada
- **Repository Pattern**: Abstração de persistência
- **Application Services**: Orquestração de Use Cases
- **Queue Jobs**: Processamento assíncrono com retry
- **SSE**: Comunicação tempo real

## 🐳 Stack Tecnológica

```yaml
Backend:
  - PHP 8.4 (Property Hooks, Typed Properties)
  - Laravel 11 (Framework)
  - PostgreSQL (Database)
  - Redis (Cache/Queue)
  - Pest (Testing)

Frontend:
  - Vue.js 3 (SPA)
  - Inertia.js (Server-side routing)
  - Vite (Build tool)
  - Tailwind CSS (Styling)

DevOps:
  - Docker Compose (Development)
  - GitHub Actions (CI/CD)
  - Make (Task automation)
  - PHPStan (Static analysis)
  - Laravel Pint (Code style)
```

## 🧪 Testes

```bash
# Executar todos os testes
make test

# Testes específicos
make test -- --filter=CPF
make test -- --group=unit

# Code Quality
make pint              # Fix code style
make pint-test         # Check code style
```

### 🧪 **CPFs de Teste**
Para desenvolvimento, use:
- `11111111111` ✅
- `12312312312` ✅
- `22222222222` ✅

## � Status da Implementação

| Componente | Status | Detalhes |
|-----------|--------|----------|
| 🏗️ **Arquitetura DDD** | ✅ | Domain/Application/Infrastructure |
| 💎 **Value Objects** | ✅ | CPF, Money, InterestRate, InstallmentCount |
| 🏢 **Entities** | ✅ | Credit, Customer, Institution, Modality |
| 🔄 **Use Cases** | ⚠️ | Integration ✅ / Credit 🚧 |
| 🌐 **API Rest** | ✅ | Endpoints principais funcionando |
| ⚡ **SSE Real-time** | ✅ | Progresso live das consultas |
| 🐳 **Docker** | ✅ | Ambiente completo containerizado |
| 🧪 **Testes** | ✅ | Pest + Parallel execution |
| 🎨 **Frontend** | ✅ | Vue.js + Inertia + Vite |
| 🚀 **CI/CD** | ✅ | GitHub Actions pipeline |

## 📚 Documentação

- 🏗️ [Arquitetura DDD](docs/arquitetura-ddd.md)
- 📖 [Explicação das Camadas](docs/explicacao-camadas-ddd.md)
- 🚀 [Guia de Setup](docs/setup-guide.md)


---

🚀 **Ready to code!** Use `make dev-ready` e comece a desenvolver!

3. Acesse a aplicação Laravel:
   - HTTP:  http://localhost:8080

## Certificado SSL

Emitir certificado Let's Encrypt (fluxo rápido)

1. Gere a configuração Nginx a partir do template e suba o Nginx (substitua EXAMPLE.COM pelo domínio):

```bash
./scripts/issue-cert.sh EXAMPLE.COM --staging
```

   Use `--staging` nas primeiras tentativas para não atingir limites da CA. Remova `--staging` para produção.

2. O script faz:
   - gera `docker/nginx/default.conf` a partir do template `docker/nginx/default.conf.template` com o domínio informado
   - sobe o Nginx
   - executa o certbot com webroot para obter o certificado
   - recarrega o Nginx para usar os certificados gerados em `./letsencrypt`

Arquivos importantes:
- `docker-compose.yml` — define serviços php, nginx, postgres, redis, certbot
- `docker/nginx/default.conf.template` — template de configuração Nginx para emissão via webroot
- `scripts/issue-cert.sh` — helper para emitir certificados
- `letsencrypt/` — diretório onde os certificados emitidos são armazenados (montado em `/etc/letsencrypt` dentro do container)

Notas de segurança:
- Certificados obtidos via Let's Encrypt são válidos para domínios públicos e não para `localhost`.

## Makefile

Este projeto possui um arquivo `Makefile` para facilitar a execução de comandos comuns no ambiente Docker, como rodar comandos do Laravel, Composer e abrir shell no container.
Para mais informações sobre os comandos disponíveis.

```bash
make help
```

Parar e remover containers:

```bash
docker compose down -v
```

