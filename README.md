# Hive CRM

Personal operations dashboard for managing my own websites and customer
websites: CRM, finance, mail, calendar, documents, fatture. Self-hosted on
a Contabo VPS, single-user for v1, multi-tenant ready.

## Domains

Thirteen domains under `app/Domains/`. Each owns its migrations,
Filament resources, public service surface (DTOs only across boundaries),
and event listeners. The conventions are spelled out in
[`docs/architecture.md`](docs/architecture.md) and enforced by
`tests/Arch`.

| Domain      | Surface                                                      |
| ----------- | ------------------------------------------------------------ |
| Contacts    | Person/company directory with role flag-set (customer / collaborator / employer / vendor); do-not-email is hard-locked. |
| Websites    | Subscription-based site portfolio with translatable name + notes, status, tech stack, daily renewal alerts. |
| Finance     | Income/expense ledger with polymorphic source attribution (website / lead / fattura), category + website-level analytics. |
| Leads       | Sales pipeline (new / contacted / qualified / proposal / won / lost) with single-action transactional Convert-to-Customer. |
| Calendar    | [Cal.com](https://cal.com) bookings via signed webhook + hourly fallback REST sync. Today widget reads from local store only. |
| Mail        | Campaigns + per-recipient queued sending via SES, SNS-signed bounce/complaint webhooks, signed unsubscribe. |
| Documents   | Uploads + Italian fatture with race-safe sequential numbering, payments, recurring schedules, FatturaPA XML import/export, dompdf rendering, Contabo Object Storage. |
| Quotations  | Preventivi with their own numbering, validity dates and one-click conversion into a fattura. |
| Catalog     | Sellable services (price, VAT, cadence) used as templates for quotation and fattura lines. |
| DomainNames | Domain registrations with registrar, expiry, renewal cost and an idempotent "log renewal" ledger action. |
| Repositories | Code repositories linked to their website and owner contact. |
| Scheduling  | Database-driven Laravel scheduler for a whitelist of commands, editable from the UI, with last-run outcomes. |
| Settings    | The owner's business profile — the Cedente Prestatore identity FatturaPA export and import rely on. |

## Tech stack

- PHP 8.3 / Laravel 11
- PostgreSQL 16 (jsonb)
- Redis 7 (cache + queues + sessions)
- Filament v3 (TALL admin panel)
- Filament Spatie Translatable plugin (IT / EN)
- AWS SES for email
- Contabo Object Storage (S3-compatible) for files & backups
- Cal.com Cloud (free tier) via webhooks + REST
- Caddy v2 in production for automatic HTTPS
- Pest for tests, Horizon for queues, Pulse for app monitoring

## Getting started (local dev with Sail)

```bash
git clone <this repo>
cd hive-crm
cp .env.example .env
# Fill in: ADMIN_EMAIL, ADMIN_PASSWORD, AWS_*, CONTABO_*, CALCOM_*

# First boot — builds the Sail image (a few minutes the first time)
./vendor/bin/sail up -d

# Generate the application key, run migrations, seed the admin user
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link

# Visit http://localhost/admin and log in with your seeded credentials.
```

To stop: `./vendor/bin/sail down`. Data persists in named docker volumes
(`sail-pgsql`, `sail-redis`).

### Useful Sail commands

```bash
./vendor/bin/sail artisan ...     # any artisan command
./vendor/bin/sail pest            # run the test suite
./vendor/bin/sail npm run dev     # run vite dev server (HMR)
./vendor/bin/sail tinker          # REPL
./vendor/bin/sail logs -f app     # tail container logs
```

### Locale switching

The Filament topbar has an `IT / EN` switcher. Selection is persisted in the
session and applied via the `SetLocale` middleware. Translatable model fields
(Website name, Document title, Campaign subject, …) are stored in `jsonb`
columns via `spatie/laravel-translatable`.

## Production deploy

See [`docs/deploy.md`](docs/deploy.md) for the full Contabo VPS runbook.
TL;DR: SSH in, `git pull`, `docker compose -f docker-compose.prod.yml up -d`.
Caddy issues a real Let's Encrypt cert on first request to `APP_DOMAIN`.

## Architecture

The codebase is split into domains under `app/Domains/{Name}/`. Each
publishes a small language (public services, DTOs, events, enums); the
write side may only use other domains through it, while Filament screens
may compose across domains for display. The full rules live in
[`docs/architecture.md`](docs/architecture.md); `tests/Arch` enforces them.

Each domain registers itself via two classes:

- `XxxServiceProvider` — loads its migrations, maps its factory namespace,
  wires its event listeners, registers its routes (if any).
- `XxxPanelPlugin` — Filament `Plugin` contract, discovers the domain's
  Resources / Pages / Widgets and is mounted in
  `App\Providers\Filament\AdminPanelProvider`.

Adding a new domain to the panel is a one-line change.

## Testing

```bash
./vendor/bin/sail pest                   # full suite: Unit, Feature, Arch
./vendor/bin/sail pest --testsuite=Arch  # module-boundary rules only
./vendor/bin/sail pest --filter=...      # focused
./vendor/bin/sail pint                   # code style
```

Tests run on an in-memory SQLite database (see `phpunit.xml`). CI
(`.github/workflows/ci.yml`) runs `pint --test` and the full suite on
every push to `main` and every pull request.

## Backups

`spatie/laravel-backup` runs nightly at 01:30 Europe/Rome via the scheduler
container. Backups go to the disk named in `BACKUP_DISK` (defaults to `s3`,
which points at Contabo Object Storage). A failure notification is sent to
`BACKUP_NOTIFICATION_EMAIL` if set.

**Verify a restore at least once** before relying on a backup. See
`docs/deploy.md` → "Backup restore drill".
