# Cria um novo teste Pest

SHELL := /bin/bash

# Nome do serviço docker-compose que contém o app PHP
APP_SERVICE ?= app
DC := docker

# Detecta nome do container real (ex: gosat-app-1) a partir do padrão APP_SERVICE
# Pode sobrescrever definindo APP_CONTAINER na environment
APP_CONTAINER ?= $(shell $(DC) ps --format '{{.Names}}' | grep -E "$(APP_SERVICE)$$|$(APP_SERVICE)-[0-9]+" | head -n1)
TARGET_CONTAINER := $(if $(APP_CONTAINER),$(APP_CONTAINER),$(APP_SERVICE))

# Detecta se há um TTY disponível para passar -it quando necessário
TTY_FLAG := $(shell [ -t 0 ] && echo "-it" || echo "-i")

# Comando Docker exec reutilizável
DOCKER_EXEC := $(DC) exec $(TTY_FLAG) $(TARGET_CONTAINER)


# Captura argumentos passados após --
ARGS ?= $(filter-out $@,$(MAKECMDGOALS))

%:
	@:


.PHONY: help artisan tinker migrate migrate-fresh migrate-rollbacks db-seed composer test exec optimize-reload pint pint-test

help:
	@echo "Makefile targets:"
	@echo "  make artisan -- migrate              # roda php artisan <CMD> no container $(APP_SERVICE)"
	@echo "  make tinker                          # abre php artisan tinker dentro do container"
	@echo "  make migrate                         # roda php artisan migrate"
	@echo "  make migrate-fresh                   # roda php artisan migrate:fresh --seed"
	@echo "  make migrate-rollbacks               # roda php artisan migrate:rollback"
	@echo "  make db-seed                         # roda php artisan db:seed"
	@echo "  make composer -- install              # roda composer no container (passe argumentos via --)"
	@echo "  make pest-test -- NomeDoTeste         # roda php artisan pest:test no container"
	@echo "  make test                            # roda php artisan test"
	@echo "  make pint                            # corrige estilo do código com Laravel Pint"
	@echo "  make pint-test                       # verifica estilo do código sem corrigir"
	@echo "  make exec -- bash                     # abre um shell (ou comando) no container"
	@echo "  make optimize-reload                   # atualiza autoload, limpa caches e reinicia workers"

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
	$(DOCKER_EXEC) php artisan migrate:fresh --seed --force


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

pest-test:
	@if [ -z "$(ARGS)" ]; then \
		echo "Por favor, informe o nome do teste: make pest-test -- NomeDoTeste"; exit 1; \
	fi
	$(DOCKER_EXEC) php artisan pest:test $(ARGS)

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

## Atualiza autoload, limpa otimizações e reinicia workers
optimize-reload:
	# roda composer dump-autoload -o
	$(DOCKER_EXEC) composer dump-autoload -o
	# limpa caches otimizados
	$(DOCKER_EXEC) php artisan optimize:clear
	# sinaliza para reiniciar os workers (supervisor/queue workers irão reiniciar ao fim do job atual)
	$(DOCKER_EXEC) php artisan queue:restart

