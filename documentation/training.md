# Training Module — Reference

> **Scope.** What the training module does, *why* it is built this way, and how to add to it. Where
> things live and what they are called is [`ARCHITECTURE.md`](../ARCHITECTURE.md)'s job (§5). This
> file links there rather than restating it.
>
> The guides themselves are the user-facing content. They live in
> `resources/js/components/training/guides/`, not here.
>
> Update this file in the same change as any training surface it describes.

---

## 1. Overview

An in-app manual: **one guide per screen**, in **English and Bangla**, readable in two places from one
source:

| Surface | Route | What it is |
| --- | --- | --- |
| The manual | `GET /training` (`training.index`) | Every screen by section, searchable in either language |
| One guide | `GET /training/{key}` (`training.show`) | A single guide, linkable and printable, beside the table of contents |
| The help dialog | **?** in the header → `GET /training/{key}/panel` (`training.panel`, JSON) | The same guide in a modal over the screen it describes |

`{key}` is a registry key such as `admin.users`. Keys use dots, which are legal in a route parameter;
slashes are not.

| Concern | Where |
| --- | --- |
| Registry | `config/training.php` |
| Registry reader | `App\Services\Training\TrainingRegistry` (static, config only — no query) |
| Drift check | `App\Services\Training\TrainingGapFinder`, `php artisan training:gaps` |
| The gate | `tests/Feature/Training/TrainingGapsTest.php` — fails the suite on any gap |
| Controller | `Training\TrainingController` — `index`, `show`, `panel` |
| Shared prop | `training` in `HandleInertiaRequests::share()` — `{key, title_en, title_bn, status}` or `null` |
| Pages | `pages/training/index.tsx`, `show.tsx` |
| Vocabulary, loader, toggle, help button, table of contents | `components/training/` |
| Guides | `components/training/guides/{section}/{name}.tsx` — 12 written (the port's 10, Admin → Companies, HRM → Departments); the three ERP overviews are `pending` |
| Shared fragments | `components/training/guides/shared/list-basics.tsx` |
| Screenshots | `public/images/training/{key}/{shot}.png`, committed |
| Capture | `tests/Browser/Training/CaptureScreenshotsTest.php`, `composer training:screens` |
| Bangla font | `resources/fonts/nikosh.woff2`, `@font-face` in `resources/css/app.css` (relative URL, so Vite serves it in dev and fingerprints it in the build) |

The module owns no models, migrations or permissions. The only change to existing screens is the
header button.

## 2. Where it came from

Ported from compozit-suite's training module (its `documentation/training.md`), itself modelled on
maccounts' manual, in a grilling session with the owner on **2026-10-27**. Every compozit decision
carries over unless the table below says otherwise.

**Left out on purpose: definitions and theory.** A guide explains **how to use a screen**, not
administration or security theory. Do not add `concept.*` pages.

## 3. Decisions

| # | Question | Decision | Why |
| --- | --- | --- | --- |
| Q1 | Content | Engine **and** a written guide for every screen Nexora had (10) | The port ships whole, not as an empty manual |
| Q2 | Languages | **English + Bangla**, both required in one file | Two files drift: an English edit and a forgotten Bangla edit look like one change in a diff |
| Q3 | Screenshots | **Captured automatically** against fictional data, light theme, committed | Training is open to every user; real rows would show them people and settings they have no right to see. Automatic capture is the only way images stay current |
| Q4 | Surfaces and access | Manual + one guide + **?** modal; **any signed-in user**, no permission; "Training" is the only ungated sidebar link (Platform, under Dashboard) | The person who cannot open a screen yet is the one who needs its guide |
| Q5 | Bangla font | **compozit's repaired Nikosh**, self-hosted, no dependency — at `resources/fonts/`, not compozit's `public/fonts/` | The common Nikosh TTF has a `name` table Chrome rejects; this copy was rebuilt. Do not replace it with a fresh download. Under `public/` the dev server 404'd it (ARCHITECTURE.md §9.5) |
| Q6 | The gate | **Exactly as compozit's** — only routes of `App\Http\Controllers\…` are checked | Owner's choice. A `Route::inertia()` screen (the dashboard) is invisible to it and is registered by hand |
| Q7 | Registry placement | `App\Services\Training\` beside the gap finder | No new `app/Support/` layer folder |
| Q8 | Sections | `general` / `admin` / `settings` ("Settings (your account)"), then one per ERP module — `projects`, `accounting`, `hrm` | Nexora's own module names; guide folders match |
| Q9 | Dashboard guide | **"Getting around"** — the sidebar, the **?**, the bell, the user menu, toasts, dialogs | The dashboard has no widgets yet; rewrite the guide when it does |
| Q10 | Sign-in guide | **None**; a paragraph in the dashboard guide covers signing in | Parity with compozit; the manual is for signed-in users |
| — | Storage | **TSX files in git**, no database | Reviewed in diffs; required `en`/`bn` props make a one-language guide fail the type check |
| — | Dropdown options | Explained **inside the guide**, read live from `lib/enums.ts` (`EnumOptions`) | A new enum case fails the type check until it is explained |
| — | Default language | **English**, remembered per browser (`localStorage['training.lang']`) | No server state |
| — | Help surface | A **modal**, never light-dismissed (ARCHITECTURE.md §8.7) | Same as every dialog |
| — | Inline field help on screens | **No** | Every edited screen is a chance to break one |

## 4. Writing a guide

### The fixed shape

Every guide is a `<Guide>` of `<GuideSection kind="…">` in this order. Leave a section out when there is
nothing to say; never reorder or repeat one. `training:gaps` checks the order.

1. `purpose` — What this screen is for
2. `who` — Who uses it, and the permission it needs
3. `before` — Before you start
4. `steps` — Step by step, with the screenshots
5. `fields` — Fields and options
6. `messages` — Messages you may see, quoted exactly
7. `mistakes` — Common mistakes
8. `related` — Related guides

### The vocabulary

`components/training/vocabulary.tsx`. A guide uses these and nothing else, so a change to how guides
look is one edit.

| Element | Use |
| --- | --- |
| `Para`, `Bullets`/`Bullet`, `SubHeading` | Prose |
| `Steps`/`Step` | Numbered steps; a `Step` may hold a `Shot` |
| `Fields`/`Field label="…"` | One input, filter, column or button. `label` is what the screen shows |
| `Message text="…"` | A message the screen shows, **copied from the code**, and what it means |
| `Callout tone="info\|warn\|danger"` | See below |
| `Shot id="…"` | A screenshot declared on the entry |
| `EnumOptions labels={X_LABELS} explain={{…}}` | Every option of a dropdown, from `lib/enums.ts` |
| `RelatedGuide to="key"` | Another registered guide, and why the reader would go there |
| `Ui` | Inline: a label exactly as the screen shows it |

Every element that carries words takes **both `en` and `bn`**, required.

### Content rules

- **`danger` is for a mistake nothing on screen catches**: the save goes through and the wrong value
  just sits there. A mistake the screen refuses with a message is `warn`.
- **Quote messages from the code, not from memory.** Grep `app/` for the text and copy it, punctuation
  included. Check every button label against the page source the same way. A message with a value in
  it (an address) is quoted with an invented value.
- **Bangla is written, not translated.** Write it as its own explanation in plain Bangla. Keep in
  English whatever the screen shows in English (button labels, permission names, column names) so the
  reader can match what they read to what they see.
- **No real data.** Examples use invented people (*Ayesha Rahman*, *Farhana Akter*) and
  `example.com` addresses.
- Every guide cites its source in its docblock — a section of `documentation/{module}.md`.

## 5. The gate — `training:gaps`

`TrainingGapFinder::find()` runs every check. `php artisan training:gaps` prints the list and exits 0.
`TrainingGapsTest` asserts it is empty, so **any gap fails the suite**.

| Check | Fires when | Fix |
| --- | --- | --- |
| `unregistered-screen` | A named GET route of `App\Http\Controllers\…` is neither registered nor in `ignore_routes` | Register it `pending`, or ignore it if it is JSON, a file or a redirect |
| `dead-route` | An entry or an ignore line names a route that does not exist | Fix or remove it |
| `unknown-section` / `unknown-status` | An entry's section or status is not a known value | Fix the entry |
| `missing-guide` | A `written` entry has no guide file | Write it, or set it back to `pending` |
| `unflipped-status` | A `pending` entry's guide file exists | Set it to `written` |
| `missing-overview` | A `written` entry declares no `overview` shot | Declare one |
| `missing-shot` | A declared shot has no PNG | `composer training:screens` |
| `undeclared-shot` / `unused-shot` | The guide's `<Shot id>`s and the entry's `shots` disagree | Make them match |
| `language-pair` | A guide file's count of `en=`/`en:` differs from its `bn=`/`bn:` | Add the missing language |
| `guide-shape` | Sections out of order, repeated, or of an unknown kind | Follow §4 |
| `dead-related` | A `RelatedGuide to=` names an unregistered key | Fix the key |
| `orphan-shot` | A PNG no written entry declares | Delete it |

**What the gate cannot see:** a guide whose prose still describes behaviour the screen no longer has,
and — because only `App\Http\Controllers` routes are checked (Q6) — a new screen declared with
`Route::inertia()`. **When you change a screen, read its guide.**

## 6. Screenshots

`composer training:screens` builds the front end, then runs
`tests/Browser/Training/CaptureScreenshotsTest.php` (group `training-screens`): one test per written
guide, named after its key. Each seeds invented data on the browser test database, drives the screen
into each state its guide shows, and writes `public/images/training/{key}/{shot}.png`.

- **Never under `public/training/`**: Apache serves a real folder before Laravel sees the request, so a
  folder named like the `/training` route hides it (found in compozit).
- The tests **skip unless `TRAINING_SCREENS=1`**, which the composer script sets, so the ordinary
  browser run never rewrites the committed images.
- Light theme, viewport only, 1440 × 900 — the Users list at 1600 wide so its row actions fit, the Mail
  Server tab at 1080 tall so *Send test mail* is in the picture.
- Every recipe acts as an invented super admin (`trainingActor()`) with invented colleagues
  (`trainingColleagues()`) and the seeded role catalogue.
- The audit-log recipe turns `audit.console` on to record its changes at all, then gives those rows an
  invented web URL and browser — a console write records the command line.
- Stored mails are rendered from the real mailables with the password masked, under an invented app
  URL.
- A shot's URL carries the file's modification time (`?v=`), so a recaptured image is never served
  stale.
- One guide: `TRAINING_SCREENS=1 vendor/bin/pest -c phpunit.browser.xml --group=training-screens --filter='admin.users'`.

## 7. Adding a guide

1. Read the screen's page, its dialogs, its controller and its section of `documentation/{module}.md`.
2. Write `components/training/guides/{section}/{name}.tsx` to §4's shape, with a default export.
3. In `config/training.php`, set the entry's `status` to `written` and declare its `shots` (`overview`
   plus one per dialog or state).
4. Add the recipe to `CaptureScreenshotsTest.php` (one test, named after the key), then run
   `composer training:screens` and look at every image.
5. `php artisan training:gaps` must say **No gaps**. Then `php artisan test --compact tests/Feature/Training`.
6. Read the guide in both languages at `/training/{key}` and in the dialog on the screen itself.
7. Send the Bangla to the owner for review.

**A new screen** needs only step 3's entry, as `pending`, or the suite fails
([ARCHITECTURE.md §10](../ARCHITECTURE.md#10-adding-a-feature-to-an-existing-module)). A new module may
need a new section in `config/training.php`.

## 8. Status

| Batch | Screens | Status |
| --- | --- | --- |
| Port | Dashboard; Admin — Users, Roles, Permissions, Audit Log, Sent Mails, Application Settings; Settings — Profile, Security, Appearance | ✅ written 2026-10-27; **Bangla awaits the owner's read** |
| Multi-company | Admin — Companies (new), Users (the Companies dialog added); HRM — Departments (new, with its import) | ✅ written 2026-10-29; **Bangla awaits the owner's read** |
| ERP overviews | Projects, Accounts and HRM overviews | `pending` — sample-data previews; written when each overview shows real data |

There is no backlog: every new screen is registered as it lands and its guide written in the same
change or the next one.

### Shared fragments

`guides/shared/list-basics.tsx` — column filters, sorting, Rows, refresh, Clear filters, the
refresh-on-return, and (with `deletedTab`) the Deleted tab — is included by every list guide. A
fragment is not a guide and has no registry entry, but `training:gaps` still holds it to the
English/Bangla pairing. **A fragment never contains a `<Shot>`**: shots are matched against the guide
file's own source.

## 9. Testing

| File | Covers |
| --- | --- |
| `tests/Feature/Training/TrainingGapsTest.php` | The gate, plus each kind of gap firing on a doctored registry |
| `tests/Feature/Training/TrainingPagesTest.php` | Manual, guide and panel for a user with no permission; 404 on an unknown key; guests redirected; the shared `training` prop |
| `tests/Feature/Training/TrainingRegistryTest.php` | Route → key, table-of-contents order, the shared prop's shape, an uncaptured shot |
| `tests/Browser/Training/TrainingHelpDialogTest.php` | The **?** opens the guide, no light dismiss, the pending notice, Bangla surviving a reload, search restoring every section |
| `tests/Browser/Training/CaptureScreenshotsTest.php` | Not a test of behaviour — the screenshot recipes (§6) |

`config/training.php`'s page keys hold dots, so a test that changes an entry replaces the whole
`training.pages` array rather than `config()->set()`-ing a dotted path.
