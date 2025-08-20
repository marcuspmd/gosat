
# GoSat - Sistema de Consulta de Crédito

Sistema DDD em Laravel para consulta e simulação de ofertas de crédito, integrando com APIs externas.

## 🎯 Funcionalidades Principais

- ✅ Consulta de ofertas de crédito por CPF
- ✅ Simulação de crédito com valores e parcelas
- ✅ Integração com API externa via jobs assíncronos
- ✅ Normalização de modalidades de crédito
- ✅ API REST completa com documentação
- ✅ Arquitetura DDD com separação de responsabilidades

## 🚀 Setup Rápido

```bash
# 1. Configurar ambiente
cp .env.example .env

# 2. Subir containers
docker compose up -d --build

# 3. Instalar dependências e migrar
make composer -- install
make artisan -- migrate
make artisan -- db:seed

# 4. Acessar aplicação
# http://localhost:8080
```

## 📊 Endpoints da API

### Consulta de Crédito
```bash
# Iniciar consulta
POST /api/v1/credit/search
{
  "cpf": "12345678909"
}

# Verificar status
GET /api/v1/credit/status/{requestId}

# Simular oferta
POST /api/v1/credit/simulate
{
  "cpf": "12345678909",
  "amount": 10000,
  "installments": 12
}

# Health check
GET /api/v1/health
```

## 🔧 Comandos Principais

```bash
make test              # Executar testes
make artisan -- ...    # Comandos Artisan
make composer -- ...   # Comandos Composer
make exec -- bash      # Abrir shell no container
```

## 🏗️ Arquitetura DDD

```
app/
├── Domain/           # Regras de negócio
│   ├── Credit/       # Contexto de Crédito
│   ├── Customer/     # Contexto de Cliente
│   ├── Integration/  # Contexto de Integração
│   └── Shared/       # Value Objects compartilhados
├── Application/      # Casos de uso e DTOs
└── Infrastructure/   # Implementações técnicas
    ├── Http/         # Controllers e Resources
    ├── Persistence/  # Repositories Eloquent
    └── Queue/        # Jobs e Queue Service
```

## 📝 CPFs de Teste

Para desenvolvimento, use estes CPFs válidos:
- `11111111111`
- `12312312312`
- `22222222222`

## 🔍 Monitoramento

- Logs estruturados com contexto mascarado de CPF
- Jobs com retry automático e backoff exponencial
- Health check endpoint para monitoramento
- Tratamento de erros com notificações planejadas

---

Ambiente de desenvolvimento com Docker (PHP 8.4 FPM, Nginx, Postgres, Redis) e suporte para certificados via Let's Encrypt (Certbot).

Requisitos:
- Docker e Docker Compose

Instalação e uso básico:

1. Copie o arquivo de exemplo de variáveis de ambiente:
```bash
cp .env.example .env
```
2. Suba os containers (PHP, Nginx, Postgres, Redis e Certbot):

```bash
docker compose up -d --build
```

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

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
