# Nexora — architecture overview (pointer memory)

Authoritative map: ARCHITECTURE.md (read it first, every session). Agent contract: CLAUDE.md. Module docs: documentation/{admin,settings,frontend,setup-and-deployment}.md. This memory is only a quick index; if it disagrees with ARCHITECTURE.md, the file wins.

Stack: Laravel 13 / PHP 8.4, Inertia v3 + React 19 + TS, Tailwind 4 + daisyUI 5 (35 themes + system), Wayfinder (--with-form), Fortify (resetPasswords only), spatie/laravel-permission v8, owen-it/laravel-auditing 14.0.6, Reverb (port 6001) + Echo, Pest 5 (SQLite only), PHPStan L7, Pint, ESLint/Prettier.

Organizing rule: layer first, module second — app/{Http/Controllers,Http/Requests,Models,Services,...}/{Module}/, pages/{module}/{resource}/index.tsx, components/{ui,shared,module}.
Modules: Platform (root: Home/Dashboard/Notifications), Admin (users, roles, permissions catalogue, audit logs, application settings: mail server + quick login, sent mails), Settings (own profile, security, appearance).

Key mechanisms:
- RBAC: permission names {module}.{resource}.{action}, defined only in database/seeders/Admin/RolePermissionSeeder::CATALOGUE + App\Services\Admin\PermissionDescriptions. Route `permission:` middleware + service *Blocker() guards. NO Policy classes. super-admin passes via Gate::before. Roles: super-admin, admin, viewer (prefix/suffix rules; SUPER_ADMIN_ONLY list).
- Audit: Concerns\Audited + Contracts\Auditable on models, table audit_logs (Models\Admin\AuditLog), AuditRecorder for custom events, ActorObserver stamps inserted_by/last_updated_by, console writes not audited, kept forever. Enforced morph map in AppServiceProvider::MORPH_MAP.
- Lists: Concerns\Listable + Http\Requests\ListRequest; model FILTERABLE (FilterType) + SORTABLE; LIKE with ESCAPE '!'. Frontend use-list-filters + shared list components.
- Status: RecordStatus A/I via HasStatus; users also soft-delete.
- Enums for the frontend: EnumRegistry + `php artisan enums:sync` → resources/js/lib/enums.ts (generated).
- Mail: DB-backed `database` mailer from mail_settings; `mail` queue; sent_mails log.
- Notifications: AccountChanged (database + broadcast) on private channel App.Models.User.{id}.
- Login: single `login` field (email or username), EnsureUserIsActive middleware.

Skills: .claude/skills/admin-crud (CRUD surface path, ARCHITECTURE §10), daisyui-ui (UI standards), plus Boost skills. Hook: .claude/hooks/architecture-sync.mjs.
