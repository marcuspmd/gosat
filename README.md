
# gosat

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
