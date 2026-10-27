# Setup and Deployment

> **Scope.** Getting Nexora running locally, what it needs at runtime, and — once chosen — how it is
> deployed. Architecture: [`ARCHITECTURE.md`](../ARCHITECTURE.md).

## 1. Prerequisites

- PHP 8.3+ (Laragon ships 8.4) with `pdo_mysql`, `pdo_sqlite`, `mbstring`, `fileinfo`, `gd` or
  `imagick` (avatar validation), `sockets` (browser tests).
- Composer 2, Node 22 + npm.
- MySQL 8 (Laragon), a database named `nexora`.

## 2. First run (local)

```bash
composer install
cp .env.example .env            # then edit DB_*, SUPER_ADMIN_*, REVERB_*
php artisan key:generate
php artisan migrate --seed      # roles, permissions, super admin, demo admin/viewer
php artisan storage:link        # avatars are served from public/storage
npm install
npm run build                   # or keep `composer dev` running for Vite
npx playwright install chromium # only for the browser test suite
```

`composer setup` does the install/key/migrate/build steps in one go.

### 2.1 Environment keys that matter

| Key | Purpose |
| --- | --- |
| `APP_NAME=Nexora`, `APP_URL` | app name; base URL |
| `DB_*` | MySQL `nexora` |
| `SUPER_ADMIN_NAME/USERNAME/EMAIL/PASSWORD` | the first account (`SuperAdminSeeder`). **Password is required in production**; elsewhere a blank one seeds `password` |
| `BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID/KEY/SECRET` | live push; generate random values per install |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | where the **browser** connects (shared at runtime, no rebuild needed) |
| `REVERB_SERVER_PORT` | where `reverb:start` listens — `6001` locally (8080 is Laragon's Apache) |
| `QUEUE_CONNECTION=database` | queued mail and broadcasts |
| `MAIL_*` | read **once**, to seed the mail server row; afterwards configure mail in Admin → Application Settings → Mail Server |

### 2.2 Seeded accounts (non-production)

| Username | Password | Role |
| --- | --- | --- |
| `superadmin` (or `SUPER_ADMIN_USERNAME`) | `password` (or `SUPER_ADMIN_PASSWORD`) | super-admin |
| `admin` | `password` | admin |
| `viewer` | `password` | viewer |

Sign in with the username or the email. Turn on Admin → Application Settings → Quick Login for
one-click buttons on the login page (local/testing only).

## 3. Running it

```bash
composer dev     # = php artisan dev
```

`php artisan dev` runs every process the app needs, registered by the framework, by Reverb and by
`AppServiceProvider::configureDevCommands()`:

| Process | Command |
| --- | --- |
| Web server | `php artisan serve` (http://localhost:8000) |
| Default queue (broadcasts) | `php artisan queue:listen --tries=1 --timeout=0` |
| Mail queue | `php artisan queue:listen --queue=mail --tries=3 --timeout=0` |
| Reverb (WebSockets) | `php artisan reverb:start` |
| Scheduler | `php artisan schedule:work` |
| Vite | `npm run dev` |
| Logs | `php artisan pail` (where `pcntl` exists) |

A `queue:listen` keeps the environment it started with: **restart it after editing `.env`**.

## 4. Quality gates

```bash
composer ci:check                          # ESLint, Prettier, tsc, Pint, PHPStan L7, Pest
php artisan test --compact                 # Pest only
npm run build && php artisan test -c phpunit.browser.xml   # browser tests
```

The suite runs on in-memory SQLite and **refuses** to run against MySQL
([`ARCHITECTURE.md` §13.1](../ARCHITECTURE.md#131-the-suite-never-touches-the-development-database)).

## 5. Deployment — TBD

The deployment architecture for Nexora has not been chosen yet (owner, 2026-10-26); it will differ
from compozit-suite's Windows `deploy/` supervisor. Whatever it is, a production install needs:

1. PHP-FPM or Apache/Nginx serving `public/`, HTTPS.
2. `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force`, and `php artisan db:seed --force` once with `SUPER_ADMIN_PASSWORD`
   set (demo accounts are never seeded in production).
4. `php artisan storage:link`; `php artisan config:cache route:cache view:cache`.
5. Long-running, supervised processes: `queue:work` (default), `queue:work --queue=mail`,
   `reverb:start` (behind the web server's WebSocket proxy, with `REVERB_HOST/PORT/SCHEME` set to the
   public endpoint), and the scheduler (`schedule:run` every minute).
6. A configured mail server in Admin → Application Settings → Mail Server.
7. `APP_ENV=production`, `APP_DEBUG=false` — Quick Login can then never appear.

Fill this section in when the target is decided, and record the decision in `ARCHITECTURE.md` §15.
