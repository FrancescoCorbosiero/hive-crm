# Deploy runbook — Contabo VPS

## One-time VPS setup

```bash
# As root or sudo on a fresh Debian 12 / Ubuntu 24.04 VPS
apt-get update && apt-get install -y ca-certificates curl gnupg git
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg | \
  gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/debian $(. /etc/os-release && echo $VERSION_CODENAME) stable" \
  > /etc/apt/sources.list.d/docker.list
apt-get update && apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Open firewall
ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw enable

# Point your domain's A/AAAA records at the VPS public IP.
```

## Application deploy

```bash
git clone git@github.com:your-org/hive-crm.git /srv/hive-crm
cd /srv/hive-crm

cp .env.example .env
# Edit .env — at minimum set:
#   APP_ENV=production, APP_DEBUG=false, APP_URL=https://APP_DOMAIN
#   APP_DOMAIN=ops.example.com, ACME_EMAIL=admin@example.com
#   DB_PASSWORD=<a real secret>
#   AWS_*, CONTABO_*, CALCOM_*

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# First boot only:
docker compose -f docker-compose.prod.yml exec app php artisan key:generate --force
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force --seed
docker compose -f docker-compose.prod.yml exec app php artisan storage:link
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

`migrate --force --seed` only creates the admin user — demo data
(sample contacts, websites, leads, etc.) is opt-in via the admin UI at
**Settings → Demo data**. Production boxes stay clean by default.

Visit `https://APP_DOMAIN/admin` — Caddy will issue a Let's Encrypt cert on
the first request. Allow ~30s on first hit.

## Updates

```bash
cd /srv/hive-crm
git pull
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize
docker compose -f docker-compose.prod.yml restart queue scheduler
```

## AWS SES — production access

New SES accounts are sandboxed: only verified addresses can send/receive. To
launch real campaigns you must request production access:

1. Verify your sending domain (DKIM + SPF) in the SES console.
2. Verify a from-address (`MAIL_FROM_ADDRESS`).
3. Open *Account dashboard* → *Request production access*. Describe the
   workload (transactional admin notifications + low-volume marketing to
   opt-in CRM contacts) and link to the unsubscribe flow.
4. Create an SNS topic and subscribe an HTTPS endpoint to
   `https://APP_DOMAIN/webhooks/ses`. Point Bounce, Complaint, Delivery,
   Open, Click event types at the topic via an SES Configuration Set; set
   `AWS_SES_CONFIGURATION_SET` in `.env`.

The application code works in both sandbox and production; only the recipient
allowlist differs.

## Cal.com — webhook

In Cal.com → Settings → Developer → Webhooks, create a webhook pointing at
`https://APP_DOMAIN/webhooks/calcom` with secret = `CALCOM_WEBHOOK_SECRET`.
Subscribe to `BOOKING_CREATED`, `BOOKING_RESCHEDULED`, `BOOKING_CANCELLED`.

## Backup restore drill

A backup you have not restored is not a backup. Run this drill at least once
after first deploy, and again whenever you change the schema in a non-trivial
way.

```bash
# 1. Take a fresh backup
docker compose -f docker-compose.prod.yml exec app php artisan backup:run

# 2. List backups in Contabo
docker compose -f docker-compose.prod.yml exec app php artisan backup:list

# 3. Spin up a throwaway Postgres and restore into it:
docker run --rm -d --name pg-restore-test \
  -e POSTGRES_PASSWORD=secret -e POSTGRES_USER=sail -e POSTGRES_DB=hive_crm_restore \
  -p 55432:5432 postgres:16-alpine
sleep 5

# 4. Pull the latest backup zip from Contabo (using your S3 client of choice)
#    s5cmd / mc / aws-cli — example with aws-cli:
aws --endpoint-url "$CONTABO_ENDPOINT" s3 cp \
  "s3://$CONTABO_BUCKET/<backup-name>/<latest>.zip" /tmp/backup.zip

# 5. Unzip, locate db-dumps/pgsql.sql, and pipe into the throwaway DB:
unzip -o /tmp/backup.zip -d /tmp/backup-extract
PGPASSWORD=secret psql -h 127.0.0.1 -p 55432 -U sail -d hive_crm_restore \
  -f /tmp/backup-extract/db-dumps/*.sql

# 6. Sanity-check tables exist
PGPASSWORD=secret psql -h 127.0.0.1 -p 55432 -U sail -d hive_crm_restore \
  -c "\dt"

# 7. Tear down
docker rm -f pg-restore-test
rm -rf /tmp/backup.zip /tmp/backup-extract
```

If step 6 shows the expected tables, the backup is good. Document the date of
the most recent successful restore in your ops journal.

## Observability

- `https://APP_DOMAIN/horizon` — Horizon queue dashboard (auth-gated).
- `https://APP_DOMAIN/pulse` — Pulse application metrics.
- `https://APP_DOMAIN/up` — health probe for uptime monitors.

## Operational checklist after first deploy

Run through this list once after the initial deploy and again after any
non-trivial schema migration. It exists because every item here has bitten
real production setups before.

- [ ] `https://APP_DOMAIN/up` returns 200 — Caddy + app are healthy.
- [ ] `https://APP_DOMAIN/admin` loads + you can log in with the seeded
      admin (set `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`).
- [ ] Locale switcher in the topbar flips IT ↔ EN.
- [ ] Dashboard widgets render: today calendar, monthly income chart,
      YTD totals, upcoming renewals, leads pipeline, recent transactions.
- [ ] Backup ran: `docker compose -f docker-compose.prod.yml exec app
      php artisan backup:run` — confirm the zip lands in your Contabo
      bucket.
- [ ] **Backup restored** at least once via the drill above.
- [ ] Cal.com webhook delivers — book a test event, confirm it appears
      in the today widget within seconds. Hourly `calcom:sync` also
      works as a fallback.
- [ ] SES sandbox-period test email lands at a verified address from the
      `MailTestPage`. Once production access is granted: send a campaign
      to one verified contact, confirm the SES SNS notifications flip
      `recipients.status` and that a simulated bounce flips
      `do_not_email = true` on the contact.
- [ ] `unsubscribe` link from a campaign email lands on the success page
      and flips `do_not_email`.
- [ ] First fattura issued via `/admin/fatture` — number = `0001/<year>`,
      PDF downloads with € + accents rendered correctly.
- [ ] Horizon queue dashboard (`/horizon`) shows the worker as online and
      the `default`, `notifications` queues idle.

## Observability

- `https://APP_DOMAIN/horizon` — Horizon queue dashboard (auth-gated).
- `https://APP_DOMAIN/pulse` — Pulse application metrics.
- `https://APP_DOMAIN/up` — health probe for uptime monitors.

## Common issues

- **Caddy stuck issuing cert.** DNS for `APP_DOMAIN` must resolve to the
  VPS public IP before Let's Encrypt will validate. Check with
  `dig +short $APP_DOMAIN`.
- **Sessions / queue not working.** Check `redis` container is healthy and
  `REDIS_HOST=redis` in `.env` (not `127.0.0.1`).
- **Backups fail with 403.** Verify the Contabo bucket exists and the
  access key has write permissions; confirm
  `CONTABO_USE_PATH_STYLE_ENDPOINT=true`.
- **Cal.com webhook returns 403.** Confirm `CALCOM_WEBHOOK_SECRET` in
  `.env` matches the secret you configured on the Cal.com webhook.
- **SES webhook returns 403.** SNS auto-confirms its subscription on the
  first delivery. If signature verification fails, check that the SNS
  topic is in the same AWS region as your SES identity and that the
  `aws/aws-php-sns-message-validator` package can reach the AWS cert URL
  outbound (port 443).
- **Fattura PDF shows ? in place of €.** dompdf needs DejaVu Sans to be
  resolvable. The provided Dockerfile installs the dependency; if you
  build a custom image, ensure `font-dejavu` (Alpine: `font-dejavu` or
  `ttf-dejavu`) is present.
