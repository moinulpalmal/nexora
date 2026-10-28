# Admin Module — Reference

> **Scope.** What the Admin surfaces do and *why* they are built this way. Where things live and what
> they are called is [`ARCHITECTURE.md`](../ARCHITECTURE.md)'s job — this file links there rather than
> restating it, so a decision never has two copies that can disagree.
>
> Update this file in the same change as any Admin surface it describes.

---

## 1. Overview

| Sub-area | Backend | Page | Permission(s) |
| --- | --- | --- | --- |
| a. Users | `Admin\UserController`, `Admin\UserService` | `pages/admin/users/index.tsx` | `admin.users.*` |
| b. Roles | `Admin\RoleController`, `Admin\RoleService` | `pages/admin/roles/index.tsx` | `admin.roles.*` |
| c. Permission catalogue | `Admin\PermissionController`, `Admin\PermissionService` | `pages/admin/permissions/index.tsx` | `admin.permissions.view` |
| d. Audit log | `Admin\AuditLogController`, `Admin\AuditLogService` | `pages/admin/audit-logs/index.tsx` | `admin.audit-logs.view` ★ |
| e. Application Settings → Mail Server | `Admin\MailSettingController`, `Admin\MailSettingService` | `pages/admin/application-settings.tsx` | `admin.mail-settings.{view,update}` ★ |
| f. Application Settings → Quick Login | `Admin\QuickLoginSettingController`, `Admin\QuickLoginService` | same page | `admin.quick-login.{view,update}` ★ |
| g. Sent Mails | `Admin\SentMailController` | `pages/admin/sent-mails/index.tsx` | `admin.sent-mails.view` ★ |

★ `SUPER_ADMIN_ONLY` — no role receives it by rule; an administrator can still grant it by hand.

Routes: `routes/admin.php`, prefix `admin/`, names `admin.*`. Every route carries its own
`permission:` middleware; nothing is gated by role name.

## 2. Users

### 2.1 The account

| Field | Rules |
| --- | --- |
| `name` | required |
| `username` | required, 3–30, letters/digits/`.`/`-`/`_`, starts with a letter or digit, no `@`; **unique including deleted users**; stored lowercase |
| `email` | required, valid, **unique including deleted users**; stored lowercase |
| `phone` | optional, digits and `+ ( ) -` |
| `employee_code` | optional, unique including deleted users |
| `status` | `A` active / `I` inactive (`RecordStatus`) — inactive cannot sign in |
| `avatar_path` | optional photo, jpg/png/webp ≤ 2 MB, `public` disk `avatars/{uuid}.ext` |
| `theme` | the user's own choice (Settings → Appearance) |
| `last_login_at`, `last_login_ip` | stamped on every sign-in |
| `inserted_by`, `last_updated_by` | stamped by `ActorObserver` |

A user signs in with **either their username or their email** in one field.

### 2.2 Lifecycle

```
create ──► active ──(edit status)──► inactive ──► active
   │          │
   │          └──(delete)──► deleted (soft) ──(restore)──► active
   │                              └──(force delete)──► gone (audit history stays)
```

- **Deactivate** (edit → status Inactive): the account stays, cannot sign in, and a session already
  open is ended on its next request (`EnsureUserIsActive`).
- **Delete** is soft: the user moves to the **Deleted** tab and cannot sign in; their username, email
  and employee code stay reserved.
- **Restore** brings them back with their old password.
- **Force delete** removes the row and the photo for good. The audit trail keeps a
  `force-deleted` row naming who they were, and every row they ever wrote keeps their name
  (`actor_name` is stamped at write time).

### 2.3 Guards — what is refused and why

These live in `UserService` as `*Blocker()` methods, not in policies, because the super-admin
`Gate::before` bypass would let a super admin through any gate — and these rules must hold for them.

| Action | Refused when | Why |
| --- | --- | --- |
| Deactivate | it is yourself | you would lock yourself out |
| Deactivate | the user is the **last active super admin** | nobody could administer anything |
| Delete / force delete | yourself, or the last active super admin | same |
| Change roles | it is yourself | no self-escalation (or self-demotion by accident) |
| Revoke `super-admin` | the actor is not a super admin | only a super admin may take it away |
| Revoke `super-admin` | the user is the last active super admin | as above |
| Grant `super-admin` | the actor is not a super admin | `RoleAssignmentRules` refuses it at validation |

"Last *active* super admin" counts only active, non-deleted holders: an inactive super admin cannot
sign in, so it does not count as a way back in.

Refusals are toasts (`error`), not validation errors — see
[`ARCHITECTURE.md` §8.8](../ARCHITECTURE.md#88-toasts-carry-severity-and-they-clear-themselves).

### 2.4 Passwords and the credential mails

- New users and administrator-set passwords follow the one password policy
  (`config/auth.php → password_policy`); the dialogs show the live checklist.
- **"Email this user"** (ticked by default) queues `UserWelcomeMail` / `UserPasswordResetMail` on the
  `mail` queue. The queued payload is encrypted (`ShouldBeEncrypted`); the copy in Sent Mails has the
  password masked. The audit trail records that it was mailed and to whom.
- A password set by an administrator is audited as its own `password-reset` event (the `updated` row
  carries no values, because `password` is never written to the trail), and the user gets a bell
  notification ([§6](#6-notifications)).

### 2.5 The screen

One page, `admin/users`, with **Active** and **Deleted** tabs; filters per column (name/email,
username, code, phone, role, status); sort by name, username, code, last sign-in. Every action is a
dialog; buttons appear only for holders of their permission (`useCan`) and are disabled on your own
row and on the last active super admin. Username and email are checked for availability as they are
typed (`GET admin/users/availability`).

## 3. Roles and permissions

### 3.1 The catalogue is code

Permissions are defined in `database/seeders/Admin/RolePermissionSeeder::CATALOGUE` and described in
`App\Services\Admin\PermissionDescriptions`. The **Permissions** page is read-only: it shows what
each permission allows and which roles hold it. A permission is added by adding it to the catalogue,
its sentence, and the code that checks it — `PermissionDescriptionsTest` fails on a missing sentence.

| Permission | Allows |
| --- | --- |
| `admin.users.view` / `create` / `update` / `delete` / `restore` / `force-delete` | the users list and its actions |
| `admin.users.reset-password` | set another user's password |
| `admin.users.assign-roles` | change another user's roles |
| `admin.roles.view` / `create` / `update` / `delete` | roles |
| `admin.permissions.view` | the catalogue |
| `admin.audit-logs.view` ★ | the audit trail |
| `admin.mail-settings.view` / `update` ★ | Mail Server tab, test mail |
| `admin.quick-login.view` / `update` ★ | Quick Login tab |
| `admin.sent-mails.view` ★ | Sent Mails |

### 3.2 Seeded roles

| Role | Gets | Notes |
| --- | --- | --- |
| `super-admin` | everything | passes every check via `Gate::before`; cannot be edited or deleted |
| `admin` | every `admin.*` and `settings.*` except ★ | day-to-day administration |
| `viewer` | every `*.view` except ★ | read-only |

The rules are prefixes/suffixes, so a permission added later reaches the right roles on the next seed.

### 3.3 The roles screen

Create and edit in a dialog: a kebab-case name and the grouped permission picker (hover a permission
for what it allows). A role still held by users cannot be deleted (`warning` — unassign it first); the
super-admin role cannot be edited or deleted (`error`). Every grant change is audited by name
(`permissions-changed`), and a user whose roles change is notified.

## 4. The audit trail

- Every create/update/delete/restore of `User`, `Role`, `Permission`, `MailSetting`,
  `QuickLoginSetting` is recorded with old and new values; secrets (`password`, `remember_token`, the
  SMTP password) never are.
- Also recorded: role and permission grants, administrator password resets, force deletes, mails
  queued, the mail-server password changing, test mails, sign-ins, sign-outs and failed sign-ins (the
  login typed, never the password).
- Console writes (seeders, tinker, migrations) are **not** audited — another reason not to change data
  that way.
- The viewer lists newest first, filters by person, event, record type and IP; the eye opens the diff
  and the clock opens the record's whole history. User ids inside values are shown as names.

Mechanism: [`ARCHITECTURE.md` §9.3](../ARCHITECTURE.md#93-audit-logging).

## 5. Application Settings

One page, a tab per setting, each with its own `.view` / `.update` pair; the page opens for either
`.view` and a tab you cannot read is simply not there.

### 5.1 Mail Server

- **SMTP** (host, port, SSL/STARTTLS/none, username, password) or **Log only** (written to
  `storage/logs/mail.log`, nothing sent), plus the sender address and name.
- The password is encrypted with `APP_KEY` and never sent to the browser; leave it blank to keep the
  stored one. If the database was copied from a machine with a different `APP_KEY`, the page says the
  password cannot be read — enter it again.
- **Send test mail** goes to your own address, synchronously, so a failure shows at once.
- The row was seeded once from `MAIL_*` in `.env`; after that `.env` mail settings are ignored.

### 5.2 Quick Login

A development aid: one-click sign-in buttons on `/login` for the seeded `superadmin`, `admin` and
`viewer` accounts (password `password`). **Shown only when switched on *and* `APP_ENV` is `local` or
`testing`** — production never renders them, whatever the switch says. An account that is missing or
inactive gets no button.

## 6. Sent Mails

Every mail the mailer handed over, newest first, filterable by recipient, subject and kind; the eye
previews the stored copy in a sandboxed frame. Passwords in stored copies are masked. A mail that
failed in the queue is not here — look in `failed_jobs`.

## 7. Notifications

`AccountChanged` reaches a user (bell + live push) when an administrator changes their roles or sets
their password. It is best effort: if it cannot be sent, the change still stands.

## 8. Extending Admin

Follow [`ARCHITECTURE.md` §10](../ARCHITECTURE.md#10-adding-a-feature-to-an-existing-module) or invoke
the `admin-crud` skill. A new Application Settings tab: a singleton model (`SINGLETON_ID = 1`,
`current()`), its `.view`/`.update` pair, a section method in `ApplicationSettingController::edit()`,
a panel in `components/admin/application-settings/`, and a tab entry in the page.

## 9. Testing

`tests/Feature/Admin/`: `UserTest`, `RoleTest`, `PermissionCatalogueTest`, `RbacTest`,
`PermissionDescriptionsTest`, `AuditTrailTest`, `AuditLogViewerTest`, `ApplicationSettingTest`,
`SentMailTest`. Helpers in `tests/Pest.php`: `userWithPermissions(...)`, `superAdmin()`,
`assertToast($response, $type)`, `compliantPassword()`, `fakeBreachedPasswordLookup()`. Audit
assertions turn auditing on with `config(['audit.console' => true])` after the fixtures.

## 10. Planned: Companies

> **📋 Planned — nothing built** (owner, 2026-10-28). This is the first thing to build for the ERP
> modules ([`projects.md`](projects.md), [`accounting.md`](accounting.md), [`hrm.md`](hrm.md)). The
> rule it sets is in [`ARCHITECTURE.md` §9.10](../ARCHITECTURE.md#910-multi-company-planned).

Nexora serves **several Palmal Group companies in one installation**. Every HRM, Accounting and
Projects record belongs to a company, and each company keeps its own ledger and payroll. Admin owns
the company list because it is master data every module needs and no one business module owns.

- **Admin → Companies** is the list of companies (name, short code, address, base currency, status),
  with the usual list standards. Companies are deactivated, not deleted, once anything refers to
  them.
- **User ↔ company access.** On the user dialog, an administrator grants each user one or more
  companies. A user with no company can reach no ERP module, though Admin and Settings still work.
- **The selected company.** A user works in **one company at a time**, chosen from a switcher in the
  header and remembered between sessions. Every ERP screen reads and writes only that company's
  records, and the server enforces that whatever the browser sends.
- Draft permissions: `admin.companies.{view,create,update,delete}`.
