.DEFAULT_GOAL := up

up:
	docker compose up --build -d

down:
	docker compose down

logs:
	docker compose logs -f

test:
	docker compose run --rm --no-deps app php artisan test

shell:
	docker compose exec app sh
