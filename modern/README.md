# Jesuit Science Network — modern runtime

This directory contains the maintained JSN application. The historical Symfony
3/PHP 7.2 tree remains at the repository root only as a migration reference.

## Runtime

- PHP 8.5 FPM and Symfony 7.4 LTS
- Nginx 1.30
- MariaDB 11.8 LTS
- Doctrine ORM 3 / DBAL 4 / Migrations
- Twig 3, Bootstrap 5, Vite 8, Node.js 24

All runtime versions and JavaScript dependencies are pinned. Nginx and PHP are
built as immutable images; generated frontend assets are copied into both
images and are never served from an incidental host build directory.

## Local verification

Copy `.env.compose.example` to `.env.local` and replace every placeholder.
Then run:

```sh
docker compose build app web test
docker compose up -d db app web
docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T app php bin/console doctrine:schema:validate
docker compose exec -T app php bin/console lint:container
docker compose exec -T app php bin/console lint:twig templates
docker compose run --rm test
```

The web endpoint defaults to `http://127.0.0.1:8080`.

## Data import

`php bin/console jsn:import` is intentionally refused unless `--force` is
provided. It is a full dataset replacement and must be run against an isolated
staging database first. Remote PDR/VIAF reads complete before existing data is
touched, and all database writes use a rollback-capable transaction.

See [docs/OPERATIONS.md](docs/OPERATIONS.md) for backup, deployment, rollback,
and disaster-recovery procedures.
