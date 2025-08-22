# Cria um novo teste Pest

SHELL := /bin/bash

# Nome do serviço docker-compose que contém o app PHP
APP_SERVICE ?= app
NODE_SERVICE ?= node
DC := docker

# Detecta nome do container real (ex: gosat-app-1) a partir do padrão APP_SERVICE
# Pode sobrescrever definindo APP_CONTAINER na environment
APP_CONTAINER ?= $(shell $(DC) ps --format '{{.Names}}' | grep -E "$(APP_SERVICE)$$|$(APP_SERVICE)-[0-9]+" | head -n1)
NODE_CONTAINER ?= $(shell $(DC) ps --format '{{.Names}}' | grep -E "$(NODE_SERVICE)$$|$(NODE_SERVICE)-[0-9]+" | head -n1)
TARGET_CONTAINER := $(if $(APP_CONTAINER),$(APP_CONTAINER),$(APP_SERVICE))
TARGET_NODE_CONTAINER := $(if $(NODE_CONTAINER),$(NODE_CONTAINER),$(NODE_SERVICE))

# Detecta se há um TTY disponível para passar -it quando necessário
TTY_FLAG := $(shell [ -t 0 ] && echo "-it" || echo "-i")

# Comando Docker exec reutilizável
DOCKER_EXEC := $(DC) exec $(TTY_FLAG) $(TARGET_CONTAINER)
DOCKER_NODE_EXEC := $(DC) exec $(TTY_FLAG) $(TARGET_NODE_CONTAINER)


# Captura argumentos passados após --
ARGS ?= $(filter-out $@,$(MAKECMDGOALS))

%:
	@:


.PHONY: help up down build dev-ready artisan tinker migrate migrate-fresh migrate-rollbacks db-seed composer test npm assets-build assets-dev exec optimize-reload pint pint-test quality swagger-generate swagger-serve swagger-docs

help:
	@echo "Makefile targets:"
	@echo "  make up                              # inicia todos os containers (docker compose up -d)"
	@echo "  make down                            # para todos os containers (docker compose down)"
	@echo "  make build                           # reconstrói e inicia containers (docker compose up -d --build)"
	@echo "  make dev-ready                       # setup completo: up + optimize-reload + assets build"
	@echo "  make artisan -- migrate              # roda php artisan <CMD> no container $(APP_SERVICE)"
	@echo "  make tinker                          # abre php artisan tinker dentro do container"
	@echo "  make migrate                         # roda php artisan migrate"
	@echo "  make migrate-fresh                   # roda php artisan migrate:fresh --seed"
	@echo "  make migrate-rollbacks               # roda php artisan migrate:rollback"
	@echo "  make db-seed                         # roda php artisan db:seed"
	@echo "  make composer -- install              # roda composer no container (passe argumentos via --)"
	@echo "  make pest-test -- NomeDoTeste         # roda php artisan pest:test no container"
	@echo "  make test                            # roda php artisan test"
	@echo "  make npm -- install                   # roda npm no container node"
	@echo "  make assets-build                     # builda assets com Vite"
	@echo "  make assets-dev                       # inicia Vite dev server"
	@echo "  make pint                            # corrige estilo do código com Laravel Pint"
	@echo "  make pint-test                       # verifica estilo do código sem corrigir"
	@echo "  make analyse                         # roda phpstan (via composer analyse)"
	@echo "  make exec -- bash                     # abre um shell (ou comando) no container"
	@echo "  make optimize-reload                   # atualiza autoload, limpa caches e reinicia workers"
	@echo "  make swagger-generate                  # gera documentação OpenAPI/Swagger"
	@echo "  make swagger-serve                     # abre a documentação Swagger no navegador"
	@echo "  make swagger-docs                      # gera docs e abre no navegador"

# Docker commands
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up -d --build

# Executa php artisan <CMD> no container app
artisan:
	@if [ -z "$(ARGS)" ]; then \
		echo "Por favor passe o comando como: make artisan -- <comando>"; exit 1; \
	fi
	$(DOCKER_EXEC) php artisan $(ARGS)

tinker:
	$(DOCKER_EXEC) php artisan tinker


migrate:
	$(DOCKER_EXEC) php artisan migrate --force


migrate-fresh:
	$(DOCKER_EXEC) php artisan migrate:fresh --seed


migrate-rollbacks:
	$(DOCKER_EXEC) php artisan migrate:rollback --force


db-seed:
	$(DOCKER_EXEC) php artisan db:seed --force


composer:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_EXEC) composer $(ARGS); \
	else \
		$(DOCKER_EXEC) composer install; \
	fi

test:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_EXEC) ./vendor/bin/pest $(ARGS); \
	else \
		$(DOCKER_EXEC) ./vendor/bin/pest --parallel; \
	fi

# Exec no container para comandos ad-hoc
exec:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_EXEC) $(ARGS); \
	else \
		$(DOCKER_EXEC) bash; \
	fi


# Laravel Pint - corrige estilo do código
pint:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_EXEC) ./vendor/bin/pint $(ARGS); \
	else \
		$(DOCKER_EXEC) ./vendor/bin/pint; \
	fi

# Laravel Pint - verifica estilo sem corrigir
pint-test:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_EXEC) ./vendor/bin/pint --test $(ARGS); \
	else \
		$(DOCKER_EXEC) ./vendor/bin/pint --test; \
	fi


analyse:
	$(MAKE) composer -- analyse

## Atualiza autoload, limpa otimizações e reinicia workers
optimize-reload:
	# roda composer dump-autoload -o
	$(DOCKER_EXEC) composer dump-autoload -o
	# limpa caches otimizados
	$(DOCKER_EXEC) php artisan optimize:clear
	# sinaliza para reiniciar os workers (supervisor/queue workers irão reiniciar ao fim do job atual)
	$(DOCKER_EXEC) php artisan queue:restart

## Swagger/OpenAPI Documentation targets

# Gera documentação OpenAPI/Swagger
swagger-generate:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_EXEC) php artisan swagger:generate $(ARGS); \
	else \
		$(DOCKER_EXEC) php artisan swagger:generate; \
	fi

# Abre a documentação Swagger no navegador (macOS/Linux)
swagger-serve:
	@echo "🌐 Abrindo documentação Swagger..."
	@echo "   • Swagger UI: http://localhost:8080/api/docs"
	@echo "   • JSON spec: http://localhost:8080/api/docs.json"
	@if command -v open >/dev/null 2>&1; then \
		open "http://localhost:8080/api/docs"; \
	elif command -v xdg-open >/dev/null 2>&1; then \
		xdg-open "http://localhost:8080/api/docs"; \
	else \
		echo "   Abra manualmente: http://localhost:8080/api/docs"; \
	fi

# Gera documentação e abre no navegador
swagger-docs: swagger-generate swagger-serve

# Setup completo: up + optimize-reload + assets build
dev-ready: up
	@echo "🚀 Iniciando setup completo do ambiente..."
	@echo "⏳ Aguardando containers ficarem prontos..."
	@sleep 5
	@echo "📦 Instalando dependências PHP..."
	$(MAKE) composer
	@echo "🔧 Executando migrate e optimize-reload..."
	$(MAKE) migrate
	$(MAKE) optimize-reload
	@echo "📦 Instalando dependências Node.js..."
	$(MAKE) npm -- install
	@echo "🎨 Buildando assets..."
	$(MAKE) assets-build
	@echo "✅ Ambiente pronto! Acesse: http://localhost:8080"

# Comandos Node.js
npm:
	@if [ -n "$(ARGS)" ]; then \
		$(DOCKER_NODE_EXEC) npm $(ARGS); \
	else \
		$(DOCKER_NODE_EXEC) npm install; \
	fi

# Build assets com Vite
assets-build:
	$(DOCKER_NODE_EXEC) npm run build

# Inicia Vite dev server
assets-dev:
	$(DOCKER_NODE_EXEC) npm run dev

