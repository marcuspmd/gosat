# 🚀 Gosat - Guia de Setup Completo

## Resumo das Implementações

### ✅ 1. Container Node.js Adicionado
- **Dockerfile**: `docker/node/Dockerfile` (Node.js 20 Alpine)
- **Serviço no docker-compose**: Container `node` com porta 5173 exposta
- **Volume dedicado**: `node_modules` separado para performance

### ✅ 2. Comando Unificado `dev-ready`
```bash
make dev-ready
```
Este comando faz **tudo automaticamente**:
1. Sobe todos os containers (`make up`)
2. Instala dependências PHP (`make composer`)
3. Roda migrations (`make migrate`)
4. Executa optimize-reload
5. Instala dependências Node.js (`make npm -- install`)
6. Builda os assets frontend (`make assets-build`)
7. ✅ **Ambiente pronto em http://localhost:8080**

### ✅ 3. CI/CD Completo (GitHub Actions)
Arquivo: `.github/workflows/ci.yml`

**Pipeline em 4 estágios**:
1. **Backend Tests**: PHP 8.4, Pest, PHPStan, Laravel Pint
2. **Frontend Tests**: Node.js 20, Vite build, assets validation
3. **Docker Build**: Testa build de ambos containers (PHP + Node)
4. **Integration Tests**: Ambiente completo + testes end-to-end

## 🛠️ Novos Comandos Disponíveis

### Frontend/Node.js
```bash
make npm -- install        # Instala dependências Node.js
make npm -- run dev        # Qualquer comando npm
make assets-build          # Build produção (Vite)
make assets-dev            # Dev server Vite (porta 5173)
```

### Setup e Deploy
```bash
make dev-ready            # 🚀 Setup completo automatizado
make up                   # Só sobe containers
make build                # Rebuild + up containers
```

### Testes e Qualidade
```bash
make test                 # Pest tests (paralelo)
make pint                 # Fix code style
make pint-test           # Check code style
```

## 🐳 Arquitetura Docker Atualizada

```
┌─────────────────┐    ┌─────────────────┐
│   nginx:80      │    │   node:5173     │
│   (Web Server)  │    │   (Vite Dev)    │
└─────────────────┘    └─────────────────┘
         │                       │
┌─────────────────┐    ┌─────────────────┐
│   app (PHP)     │    │   worker        │
│   (Laravel)     │    │   (Queue)       │
└─────────────────┘    └─────────────────┘
         │                       │
┌─────────────────┐    ┌─────────────────┐
│  postgres:5432  │    │   redis:6379    │
│  (Database)     │    │   (Cache/Queue) │
└─────────────────┘    └─────────────────┘
```

## 🚀 Fluxo de Desenvolvimento

### Setup Inicial (Uma vez)
```bash
git clone <repo>
cd gosat
cp .env.example .env    # Configure database credentials
make dev-ready          # 🎯 Comando único - faz tudo!
```

### Desenvolvimento Diário
```bash
# Ambiente completo
make up                 # Sobe tudo
make assets-dev         # Vite dev server (HMR)

# Só backend
make down && make up    # Restart containers
make optimize-reload    # Reload após mudanças

# Frontend
make assets-build       # Build produção
make npm -- run dev     # Dev server manual
```

### Testes
```bash
make test               # Todos os testes
make pint-test         # Code style check
```

## 🔄 CI/CD - Quando Roda

### Em todo Push/PR:
- ✅ Testes backend (PHP, Pest, PHPStan, Pint)
- ✅ Testes frontend (Node.js, Vite build)
- ✅ Docker build test

### Em Push para main/master:
- ✅ Testes de integração completos
- ✅ Ambiente Docker real
- ✅ API endpoint tests

## 📂 Estrutura Atualizada

```
gosat/
├── docker/
│   ├── node/                 # ← NOVO: Container Node.js
│   │   └── Dockerfile
│   ├── nginx/
│   └── php/
├── .github/
│   └── workflows/
│       └── ci.yml           # ← NOVO: CI/CD Pipeline
├── public/build/            # Assets buildados (Vite)
├── resources/js/            # Vue.js components
└── Makefile                 # ← ATUALIZADO: Novos comandos
```

## 🌐 URLs Importantes

- **App**: http://localhost:8080
- **Vite Dev**: http://localhost:5173
- **API Health Check**: http://localhost:8080/api/v1/health
- **SSE Endpoint**: http://localhost:8080/api/v1/sse (Server-Sent Events)
- **API Docs**: http://localhost:8080/api/docs (em desenvolvimento)

### 🎯 **Funcionalidades Implementadas:**
- ✅ **Consulta de Crédito**: `POST /api/v1/credit/request`
- ✅ **Simulação**: `POST /api/v1/credit/simulate`
- ✅ **Tempo Real**: SSE para acompanhar progresso das consultas
- ✅ **Health Check**: Monitoramento da aplicação
- ✅ **Queue Jobs**: Processamento assíncrono com retry automático

## 🔧 Configuração do Vite Atualizada

O `vite.config.js` foi atualizado para funcionar no Docker:
- Host: `0.0.0.0` (aceita conexões externas)
- Port: `5173` (exposta no container)
- HMR: Configurado para `localhost`

## ⚡ Performance

### Volume Node_modules
Container Node.js usa volume dedicado para `node_modules`, evitando conflitos entre host/container e melhorando performance.

### Cache CI/CD
- **Composer**: Cache das dependências PHP
- **NPM**: Cache das dependências Node.js
- **Docker**: Buildx cache para layers

## 🚨 Troubleshooting

### Container não encontrado:
```bash
make down && make up
```

### Assets não buildam:
```bash
make npm -- install
make assets-build
```

### Problemas de permissão:
```bash
make exec -- chown -R www-data:www-data storage bootstrap/cache
```

### Reset completo:
```bash
make down
docker system prune -f
make dev-ready
```
