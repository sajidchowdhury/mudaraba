# =============================================================================
# Mudaraba — Make commands for Docker development
# =============================================================================

# Start everything (build + up)
start:
	docker compose up -d --build

# Stop containers (keep data)
stop:
	docker compose stop

# Stop + remove containers (keep DB data)
down:
	docker compose down

# Full reset (removes DB + node_modules volumes)
reset:
	docker compose down -v
	docker compose up -d --build

# Rebuild images from scratch
rebuild:
	docker compose build --no-cache
	docker compose up -d

# Artisan commands
artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

# Run migrations
migrate:
	docker compose exec app php artisan migrate

# Fresh migrate + seed
fresh:
	docker compose exec app php artisan migrate:fresh --seed

# Run tests
test:
	docker compose exec app php artisan test

# Run Pint
pint:
	docker compose exec app vendor/bin/pint

# npm build (production)
build:
	docker compose exec node npm run build

# TypeScript check
tsc:
	docker compose exec node npx tsc --noEmit

# View logs
logs:
	docker compose logs -f

logs-app:
	docker compose logs -f app

logs-node:
	docker compose logs -f node

# Shell into containers
shell:
	docker compose exec app bash

shell-node:
	docker compose exec node bash

shell-db:
	docker compose exec postgres psql -U mudaraba -d mudaraba

# Clean everything (nuclear option)
clean:
	docker compose down -v --rmi all --remove-orphans

# Pass through any artisan command
%:
	@:
