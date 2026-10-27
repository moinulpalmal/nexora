# Critical

**Use the Serena MCP server for code retrieval and editing** — symbol overviews, `find_symbol`,
`replace_symbol_body`, `replace_content` — and fall back to the built-in Read/Edit tools only where
Serena cannot help (non-code files, a few lines). It is the owner's standing requirement, and it keeps
token use down. If the Serena tools are not loaded in a session, say so and ask the owner to restart
Claude Code rather than working around it.

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
- **Do not create documentation files** unless asked. `ARCHITECTURE.md` and the module docs in
  `documentation/` are kept current as part of the work.
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
