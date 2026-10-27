# Settings Module — Reference

> **Scope.** The signed-in user's **own account**: profile, password and theme. Application-wide
> configuration is not here — it is Admin → Application Settings
> ([`admin.md` §5](admin.md#5-application-settings)). Where things live:
> [`ARCHITECTURE.md`](../ARCHITECTURE.md).

Routes: `routes/settings.php`, URL `settings/*`, behind `auth` only — everyone may manage their own
account, so there is no permission. Pages render inside `SettingsLayout`
([`ARCHITECTURE.md` §8.1](../ARCHITECTURE.md#81-layout-resolution--resourcesjsapptsx)).

## 1. Profile — `settings/profile`

| Field | Notes |
| --- | --- |
| Photo | upload (jpg/png/webp ≤ 2 MB) or remove; stored through `UserService`, same as the admin screen |
| Name, phone | change freely |
| Username, email | **changing either asks for your current password** — they are what you sign in with, so an unattended session cannot be used to take the account over |
| Employee code | shown, not editable — administrators manage it, and your roles |

The current-password box appears only once the username or email actually differs from the stored
value (`ProfileUpdateRequest::changesSignInIdentity()` is the server's rule). Changing case only
(`JDoe` → `jdoe`) is not a change. Every change is audited with you as the actor.

**There is no "delete my account".** Accounts are retired by administrators (soft delete, restorable).

## 2. Security — `settings/security`

Change your password: current password + new password (+ confirmation), throttled to 6 attempts a
minute, and the page itself asks you to confirm your password if you have not recently
(`RequirePassword`). The new password follows the password policy, with the live checklist. There is
no two-factor authentication (owner, 2026-10-26).

## 3. Appearance — `settings/appearance`

Pick any of daisyUI's 35 themes or **System** (follows the operating system's light/dark). The choice
is saved to your account (`users.theme`) and follows you to every device; the sign-in page on a
machine you have used shows your theme too (a cookie mirrors it).

## 4. Testing

`tests/Feature/Settings/`: `ProfileUpdateTest`, `SecurityTest`, `AppearanceTest`;
`tests/Feature/PasswordPolicyTest.php`.
