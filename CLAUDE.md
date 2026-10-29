# Critical

**Use the Serena MCP server for code retrieval and editing** — symbol overviews, `find_symbol`,
`replace_symbol_body`, `replace_content` — and fall back to the built-in Read/Edit tools only where
Serena cannot help (non-code files, a few lines). It is the owner's standing requirement, and it keeps
token use down. If the Serena tools are not loaded in a session, say so and ask the owner to restart
Claude Code rather than working around it.

**Every development change updates its documentation and its training guides, in the same change**
(owner, 2026-10-29). Whatever you build, fix or alter — a screen, field, button, message, rule,
permission or guard — before you finish:

1. Update the owning module's `documentation/{module}.md` (and every other module whose behaviour the
   change alters).
2. Update the training guide of **every screen it touches**, in **English and Bangla**
   (`resources/js/components/training/guides/`); write the guide for a new screen (not just a
   `pending` entry); add or update its screenshot recipe and recapture the shots whose picture
   changed; `php artisan training:gaps` must say **No gaps**.
3. Update `ARCHITECTURE.md` when the change is structural (§2 below).

"In the next change" is not allowed. The `docs-sync` Stop hook (`.claude/hooks/docs-sync.mjs`) refuses
to end a turn that changed a module's code or screens without touching its doc and guides; if a change
is genuinely not user-visible, say so plainly.

# Agent Operating Contract

## 1. Read the map first

**Before planning or writing any code, read @ARCHITECTURE.md.** It is the authoritative map: which
module owns each concern, where every kind of class belongs, how routes, permissions and pages are
named, and which files register a new surface. Do not infer the layout by grepping. Read it again at
the start of every session; it changes.

If what you find on disk contradicts `ARCHITECTURE.md`, **the disk is the fact and the file is the
bug** — fix the file as part of your change and say so.

## 2. Keep the map in sync

Update `ARCHITECTURE.md` **in the same change** whenever you make a structural change — a module or
top-level directory added, renamed or removed; a route file or prefix; a naming or placement
convention; a cross-cutting mechanism (RBAC, audit, mail, theme, notifications); an
architecture-shaping dependency; layout resolution in `resources/js/app.tsx`. §12 of that file maps
each trigger to the section to edit. Ordinary feature work that follows recorded conventions does not
touch it.

Edit a decision **in place** with its reason; never leave two contradicting readings live.

**This is machine-enforced.** A PostToolUse hook (`.claude/hooks/architecture-sync.mjs`, wired in
`.claude/settings.json`) pushes back when a structural file changes while `ARCHITECTURE.md` has no
pending edits (Rule A), or when a file lands in a module folder the map never mentions (Rule B). Both
clear themselves once the map is updated. If it fires on something genuinely not structural, say why.

## 3. The owner's preferences are the source of truth

`ARCHITECTURE.md` records decisions the owner made, not immutable law. When the owner states a
preference that conflicts with it, **the preference wins** — update the file to match in the same
turn, with the rationale, so the next agent inherits the decision.

## 4. Project rules

- **Use `php artisan make:*`** for new files, with `--no-interaction`.
- **Never edit `resources/js/actions/`, `resources/js/routes/`, `resources/js/wayfinder/`** (Wayfinder
  output) **or `resources/js/lib/enums.ts`** (`php artisan enums:sync` output).
- **Never nest `database/migrations/`.** Schema changes are new migrations.
- **Every change needs a test**: `php artisan test --compact --filter=...`.
- **Run `vendor/bin/pint --dirty --format agent`** before finalizing PHP; `npm run format` and
  `npm run lint` for TypeScript. `composer ci:check` is the full gate.
- **No Policy classes**: gate routes with `permission:` middleware and put self-protection rules in
  the service's `*Blocker()` guards (`ARCHITECTURE.md` §9.1).
- **Permissions are code**: add them to `RolePermissionSeeder::CATALOGUE` *and*
  `PermissionDescriptions`, never through a UI.
- **Do not add dependencies or create top-level directories** without asking first.
- **Never write application data through tinker, scripts or raw SQL** on the development database:
  console writes are not audited (§9.3). Verify through the screens or tests.
- **Do not create documentation files** unless asked. `ARCHITECTURE.md`, the module docs in
  `documentation/` and the training guides are kept current as part of **every** change (see
  Critical, above).
- The Laravel Boost guidelines imported below apply in full.

## 5. Where to start, by task

| Task | Start here |
| --- | --- |
| Anything at all | `ARCHITECTURE.md` §5 — find the owning module |
| Adding a feature (CRUD screen) | §10, and the `admin-crud` skill |
| Adding a module | §11 |
| Naming something | §6 |
| Deciding where logic goes | §7 |
| UI work (lists, dialogs, forms, toasts) | §8, `documentation/frontend.md`, the `daisyui-ui` skill |
| Permissions, audit, mail, theme, notifications | §9 |
| Running or testing the app | §13, `documentation/setup-and-deployment.md` |

## 6. Playwright (manual verification in the browser)

- **Only when the owner asks** for a browser check, or to verify a UI change you made.
- Base URL: read `APP_URL` from `.env` — do not hardcode it. `php artisan serve` binds
  `http://localhost:8000`.
- Build first (`npm run build`) unless `composer dev` (Vite) is running; a stale `public/hot` file
  makes every page load from a dead Vite server — delete it.
- Sign in through the **`login`** field (email *or* username) with the seeded accounts:
  `superadmin` / `password` (super admin, from `SUPER_ADMIN_*`), `admin` / `password`,
  `viewer` / `password`. With Application Settings → Quick Login on, the login page has one-click
  buttons for them (local only).
- Screenshots and snapshots go to `.playwright-mcp/` (git-ignored).
- Live push needs Reverb (`php artisan reverb:start`, port 6001) and a queue worker; restart a running
  `queue:listen` after editing `.env` — it keeps the old environment.

## 7. Environment

- Windows + Laragon. `php`, `composer`, `node` are on PATH in both bash and PowerShell.
- `composer.bat` strips `^` from constraints typed on the command line — check `composer.json` after
  every `composer require`.
- Port 8080 is Laragon's Apache; Reverb uses 6001.

@AGENTS.md

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
