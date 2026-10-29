# Install the Development Environment

> **Scope.** What a new collaborator does to run Nexora on their own computer and contribute. Runtime
> details (every process, every `.env` key) are in [`setup-and-deployment.md`](setup-and-deployment.md);
> architecture is in [`ARCHITECTURE.md`](../ARCHITECTURE.md).

## 1. Install the prerequisites

- PHP 8.3 or newer (Laragon ships 8.4) with the extensions `pdo_mysql`, `pdo_sqlite`, `mbstring`,
  `fileinfo`, `gd` or `imagick`, and `sockets`.
- Composer 2, Node 22 and npm, Git.
- MySQL 8, with an empty database named `nexora`.

## 2. Get the code

```bash
git clone https://github.com/moinulpalmal/nexora.git
cd nexora
git checkout -b feat/<your-feature>   # work on a branch, never on main
```

## 3. First-time setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Then edit `.env`:

- `DB_*` — your own MySQL `nexora` database.
- `APP_URL` — your local URL.
- `SUPER_ADMIN_*` — the first account. A blank password seeds `password` on local installs.
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` — random values of your own. Keep
  `REVERB_SERVER_PORT=6001` (8080 is Laragon's Apache).

```bash
php artisan migrate --seed
php artisan storage:link
npm install
```

## 4. Run it

```bash
composer dev
```

This starts the web server, the default and mail queues, the scheduler, Reverb and Vite together.
Open your `APP_URL` (http://localhost:8000 without a virtual host).

Seeded accounts (non-production), all with the password `password`:

| Username | Role |
| --- | --- |
| `superadmin` | super-admin |
| `admin` | admin |
| `viewer` | viewer |

## 5. Before every push

```bash
composer ci:check
```

Runs ESLint, Prettier, tsc, Pint, PHPStan and the Pest tests. Browser tests need a one-time
`npx playwright install chromium`, then:

```bash
npm run build && php artisan test -c phpunit.browser.xml
```

Push your branch and open a pull request into `main`.

## 6. Set up Claude Code and the AI tooling (optional)

Skip this section if you do not use Claude Code. Nothing here is needed to run the app.

### 6.1 Install Claude Code

Install it from https://claude.com/claude-code (CLI, VS Code or JetBrains extension), sign in, then
open Claude Code **in the project folder**. It reads [`CLAUDE.md`](../CLAUDE.md) automatically, which
imports the Laravel Boost rules from `AGENTS.md`.

### 6.2 MCP servers (`.mcp.json`)

The repository configures three MCP servers. Claude Code asks you to approve them the first time.

| Server | Needs | Notes |
| --- | --- | --- |
| `laravel-boost` | PHP and `composer install` done | Database schema, docs search, last error, logs. Nothing to configure |
| `serena` | [`uv`](https://docs.astral.sh/uv/) (provides `uvx`) | Symbol-aware code reading and editing. **Required by the project rules** |
| `playwright` | Node (`npx`) | Drives a browser for manual UI checks. Downloads itself on first use |

**Serena: change the project path.** `.mcp.json` holds the original author's absolute path after
`--project`:

```json
"--project", "D:\\Projects\\laragon\\www\\nexora",
```

Edit it to **your** clone's absolute path (in JSON on Windows every backslash is doubled, `\\`). Do not commit a personal path
change unless the team agrees; `git update-index --skip-worktree .mcp.json` keeps it local. Serena's
PHP language server and project settings are already in `.serena/project.yml`, and its memories
(architecture overview, commands) are in `.serena/memories/`; its cache and local overrides are
git-ignored.

Check it worked: restart Claude Code, run `/mcp`, and confirm `serena`, `laravel-boost` and
`playwright` show as connected. If Serena is missing, install `uv`, restart, and look again.

### 6.3 Playwright

There are two separate uses:

- **Playwright MCP** (above) is for Claude to look at the running app. Start `composer dev` first, then
  ask Claude to check a page; it reads `APP_URL` from `.env`.
- **The browser test suite** (`tests/Browser`) uses the `playwright` npm package. Install its browser
  once, and again whenever `npm install` changes the Playwright version:

  ```bash
  npx playwright install chromium
  ```

  If every browser test fails with "Playwright is outdated", that is the cause.

### 6.4 Skills

Project skills are committed in `.claude/skills/` and load automatically:

| Skill | Use |
| --- | --- |
| `admin-crud` | Build a new CRUD screen end to end. Invoke with `/admin-crud` |
| `daisyui-ui` | UI standards for anything in `resources/js` |
| Boost skills (`fortify-development`, `inertia-react-development`, `laravel-best-practices`, `tailwindcss-development`, `testing-best-practices`, `wayfinder-development`, `infer-conventions`) | Activated by Claude when the work matches |
| `skill-creator` | Write or improve a skill |

To refresh the Boost-managed skills after a package update, run `php artisan boost:update`.

### 6.5 Grill-me and other personal skills

`/grill-me` (a skill that interviews you about a plan before building) is **not part of the
repository**. It is a personal skill that lives in the author's `~/.claude/skills/` folder, together
with `grilling`, `find-skills` and `improve-codebase-architecture`. To use it, copy those skill
folders from a teammate (or install them from your own source) into your own
`~/.claude/skills/` (`%USERPROFILE%\.claude\skills\` on Windows). Personal skills apply to every
project you open and are never committed here. Anything the whole team should share belongs in
`.claude/skills/` instead (create it with `skill-creator`).

### 6.6 The documentation hooks

`.claude/settings.json` runs two hooks after every file edit Claude makes, through Write/Edit or
Serena's editing tools:

- `.claude/hooks/architecture-sync.mjs` reminds Claude to update `ARCHITECTURE.md` when a structural
  file changes.
- `.claude/hooks/docs-sync.mjs` reminds Claude that a module's code changed while its
  `documentation/{module}.md`, or a changed screen's training guide, did not. It also runs when
  Claude tries to finish, and refuses until they are updated (owner, 2026-10-29).

They need only Node, and are committed, so there is nothing to install. If one fires on a change that
is genuinely not structural or not user-visible, tell Claude why and carry on.

## 7. Things to know

- `.env` is not in Git and must not be. Everyone has their own, so database and Reverb secrets are
  never shared.
- Read [`CLAUDE.md`](../CLAUDE.md) and [`ARCHITECTURE.md`](../ARCHITECTURE.md) first. If you use Claude
  Code, the Serena MCP server is configured in `.mcp.json` and the repository's agent rules assume it.
- Each person's database is separate. A schema change ships as a new migration; after pulling, run
  `php artisan migrate`.
- After editing `.env`, restart the queue workers: `queue:listen` keeps the settings it started with,
  so mail and the notification bell can misbehave until then.
