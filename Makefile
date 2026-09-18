BACKEND  = mediatools-backend
FRONTEND = mediatools-frontend

# ── Docker ──────────────────────────────────────────────────────────────────

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up --build -d
build-front:
	docker compose build frontend && docker compose up -d frontend
	
rebuild:
	docker compose down && docker compose up --build -d

logs:
	docker compose logs -f backend worker

# ── Dependencias ────────────────────────────────────────────────────────────

install:
	docker exec $(BACKEND) composer install
	docker compose run --rm --no-deps frontend npm install

update:
	docker exec $(BACKEND) composer update
	docker compose run --rm --no-deps frontend npm update

vendor-sync:
	docker cp $(BACKEND):/app/vendor ./backend/vendor

# ── Tests ───────────────────────────────────────────────────────────────────

test: test-unit test-integration test-frontend

test-unit:
	@echo "▶ PHPUnit – Unit tests"
	docker exec $(BACKEND) php -d xdebug.mode=off vendor/bin/phpunit --testsuite Unit --colors=always

test-integration:
	@echo "▶ PHPUnit – Integration tests"
	docker exec $(BACKEND) php -d xdebug.mode=off vendor/bin/phpunit --testsuite Integration --colors=always

test-frontend:
	@echo "▶ Vitest – Frontend tests"
	docker compose run --rm --no-deps frontend npm test

test-coverage:
	@echo "▶ PHPUnit – Coverage (HTML → backend/coverage/)"
	docker exec $(BACKEND) php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage

# ── Base de datos ────────────────────────────────────────────────────────────

migrate:
	docker exec $(BACKEND) php bin/console doctrine:migrations:migrate --no-interaction

migration-diff:
	docker exec $(BACKEND) php bin/console doctrine:migrations:diff

migration-status:
	docker exec $(BACKEND) php bin/console doctrine:migrations:status

# ── Symfony ──────────────────────────────────────────────────────────────────

cache-clear:
	docker exec $(BACKEND) php bin/console cache:clear

sf:
	docker exec $(BACKEND) php bin/console $(cmd)

# ── Producción ───────────────────────────────────────────────────────────────

# Crea la BBDD mediatools en el Postgres compartido (idempotente, seguro repetirlo)
# POSTGRES_PASSWORD puede venir del .env del proyecto o del entorno del shell
init-db:
	docker run --rm --network shared-network --env-file .env -e POSTGRES_PASSWORD \
		-v $(PWD)/init-db.sh:/init-db.sh:ro \
		postgres:16-alpine sh /init-db.sh

deploy-prod: init-db
	docker compose -f docker-compose.prod.yml up --build -d

# ── Utilidades ───────────────────────────────────────────────────────────────

shell:
	docker exec -it $(BACKEND) bash

shell-worker:
	docker exec -it mediatools-worker bash

shell-db:
	docker exec -it shared-postgres-db psql -U postgres -d mediatools

redis-cli:
	docker exec -it shared-redis redis-cli

.PHONY: up down build rebuild logs install update vendor-sync \
        test test-unit test-integration test-frontend test-coverage \
        migrate migration-diff migration-status cache-clear sf \
        init-db deploy-prod \
        shell shell-worker shell-db redis-cli
