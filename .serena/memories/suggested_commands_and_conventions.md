# Nexora — commands and conventions

Environment: Windows + Laragon, MySQL db `nexora`. Port 8080 = Laragon Apache; Reverb on 6001. composer.bat strips `^` from CLI constraints — check composer.json after `composer require`.

Run: `composer dev` (= php artisan dev: serve, queue default, queue mail, reverb, schedule:work, vite). Restart queue:listen after editing .env.
Seed: `php artisan migrate --seed` → superadmin/password, admin/password, viewer/password (non-production).

Gates (all must pass before finishing):
- `vendor/bin/pint --dirty --format agent`
- `npm run format`, `npm run lint`, `npm run types:check`
- `vendor/bin/phpstan analyse` (level 7)
- `php artisan test --compact [--filter=X]` (SQLite in-memory; TestCase refuses other DBs)
- Full: `composer ci:check`. Browser: `npm run build && php artisan test -c phpunit.browser.xml`.
- `npm run build` before feature tests that render a new page (Vite manifest).

Generated, never edit: resources/js/{actions,routes,wayfinder}/, resources/js/lib/enums.ts. Regenerate with `php artisan wayfinder:generate --with-form`, `php artisan enums:sync`.

Rules: use `php artisan make:* --no-interaction`; flat migrations, schema changes are new migrations; every change has a test; no data writes via tinker/SQL (not audited); no new dependencies or top-level dirs without asking; writes return back() + Inertia::flash('toast', [type, message]); toast types success/warning/error/info; no native <select> (Combobox); daisyUI semantic colours only.
Test helpers (tests/Pest.php): userWithPermissions(...), superAdmin(), assertToast($r, $type), compliantPassword(), fakeBreachedPasswordLookup(); audit assertions: config(['audit.console' => true]) after fixtures.
Code style: PHP docblocks explain *why* and cite ARCHITECTURE.md sections; TS 4-space, single quotes, sorted imports, `import type`.
