# JSN operations runbook

## Required configuration

Keep `.env`/`.env.local` mode `0600` on the server. Required values are
`APP_SECRET`, `DB_PASSWORD`, and `DB_ROOT_PASSWORD`. Set
`GOOGLE_MAPS_BROWSER_KEY` for browser maps, `GOOGLE_MAPS_SERVER_KEY` for the
maintenance geocoder, and `SITE_BASE_URL=https://jsn.soohwalee.com`.

The Oracle deployment binds the web service to `172.17.0.1:8081`, matching the
existing Nginx Proxy Manager target without exposing a new public port.

## Deploy from a verified backup

1. Stop the legacy app and database and record their container/volume state.
2. Create and verify both logical SQL and physical-volume backups.
3. Extract the modern source into a new directory; never overwrite the dirty
   legacy checkout.
4. Generate new application/database secrets and copy the restricted Google
   key into environment variables without printing it.
5. Build `app`, `web`, and `test` images.
6. Start only the new database, restore `symfony.sql.gz`, and compare exact
   table counts with the backup manifest.
7. Run `doctrine:migrations:migrate`, `doctrine:schema:validate`, container/Twig
   lint, and the PHPUnit suite.
8. Start `app` and `web`, verify `/healthz`, `/readyz`, representative HTML/JSON
   pages, BEACON output, and public HTTPS through Nginx Proxy Manager.

## Routine deployment

```sh
docker compose build app web test
docker compose run --rm test
docker compose up -d db
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction
docker compose up -d app web
docker compose ps
```

Do not run `jsn:import --force` as part of a routine deployment.

## Rollback

Application rollback is image/directory based:

1. Stop the modern `web` and `app` services.
2. If a migration changed data, stop the modern DB and restore the verified
   pre-deployment volume/SQL backup; do not attempt an unsafe down migration.
3. Ensure port `8081` is free, then start the untouched legacy `db` and `app`
   containers.
4. Verify public HTTPS and compare table counts.

The baseline and utf8mb4 safety migrations are deliberately irreversible to
prevent accidental table deletion or recreation with an unsafe row layout.

## Backup verification

A backup is usable only after a temporary MariaDB restore, `mariadb-check`,
exact table-count comparison, and canonical schema/data hash comparison. Store
the manifest alongside the SQL dump and volume archive.
