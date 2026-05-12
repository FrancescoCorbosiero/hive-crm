# Local development — pull & apply runbook

How to pull a branch from GitHub and get it running on your local Sail
stack. The Docker service for the Laravel app is `laravel.test`.

If you have Sail aliased (`alias sail='./vendor/bin/sail'`), every
`docker compose exec laravel.test <cmd>` below becomes `sail <cmd>`.

## Pull a branch and apply it

```bash
# 1. fetch + switch to the branch
docker compose exec laravel.test git fetch origin
docker compose exec laravel.test git checkout <branch-name>
docker compose exec laravel.test git pull

# 2. install PHP deps (only when composer.lock changed)
docker compose exec laravel.test composer install

# 3. install JS deps + rebuild assets (only when package.json or
#    resources/js|css changed)
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build

# 4. run new migrations
docker compose exec laravel.test php artisan migrate

# 5. clear caches so new translations, routes, views, widgets show up
docker compose exec laravel.test php artisan optimize:clear
```

### One-liner for the common case

When only PHP code + a migration changed:

```bash
docker compose exec laravel.test sh -c \
  "git pull && composer install && php artisan migrate && php artisan optimize:clear"
```

## Demo data

`php artisan db:seed` only creates the admin user. Sample contacts,
websites, leads, transactions and documents are opt-in — install them
from the admin UI under **Settings → Demo data**, or from the CLI:

```bash
docker compose exec laravel.test php artisan db:seed \
  --class="Database\Seeders\DemoDataSeeder"
```

Re-running is idempotent (each domain seeder uses `updateOrCreate` on a
stable key). You can also run a single domain's seeder directly:

```bash
docker compose exec laravel.test php artisan db:seed \
  --class="App\Domains\Leads\Database\Seeders\LeadsSeeder"
```

To wipe everything and start clean (dev only):

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```

## Rollback

```bash
# undo the last migration batch
docker compose exec laravel.test php artisan migrate:rollback --step=1

# go back to the previous branch
docker compose exec laravel.test git checkout main
```

## Quick health checks

```bash
# tests
docker compose exec laravel.test ./vendor/bin/pest

# code style
docker compose exec laravel.test ./vendor/bin/pint --test

# tail logs
docker compose exec laravel.test tail -f storage/logs/laravel.log
```

## When something looks stuck

In order, try:

1. `php artisan optimize:clear` — clears config, route, view, event caches
   in one shot. Fixes 80% of "I changed a file but nothing changed" cases.
2. `php artisan filament:optimize-clear` — Filament caches its discovered
   resources/widgets; clear when a new widget or resource doesn't appear.
3. `composer dump-autoload` — when a new class isn't being found.
4. `docker compose restart laravel.test` — when the container is in a
   weird state (file watcher hung, opcache stale).
5. `docker compose down && docker compose up -d` — last resort, full
   container recycle.
