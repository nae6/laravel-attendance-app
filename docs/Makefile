init:
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php sh -c "[ -f .env ] || cp .env.example .env"
	docker compose exec php php artisan key:generate
	@make fresh

fresh:
	docker compose exec php php artisan migrate:fresh --seed

build:
	docker compose up -d --build

up:
	docker compose up -d

down:
	docker compose down --remove-orphans

bash:
	docker compose exec php bash

migrate:
	docker compose exec php php artisan migrate

tinker:
	docker compose exec php php artisan tinker