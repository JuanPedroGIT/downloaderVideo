BACKEND  = yt-downloader-backend
FRONTEND = yt-downloader-frontend

# ── Docker ──────────────────────────────────────────────────────────────────

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up --build -d

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

# ── Utilidades ───────────────────────────────────────────────────────────────

shell:
	docker exec -it $(BACKEND) bash

shell-worker:
	docker exec -it yt-downloader-worker bash

shell-db:
	docker exec -it yt-downloader-postgres psql -U postgres -d app

redis-cli:
	docker exec -it yt-downloader-redis redis-cli

.PHONY: up down build rebuild logs install update vendor-sync \
        test test-unit test-integration test-frontend test-coverage \
        migrate migration-diff migration-status cache-clear sf \
        shell shell-worker shell-db redis-cli
