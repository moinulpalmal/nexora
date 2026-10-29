# Every change carries its documentation (owner, 2026-10-29)

Any development change (feature, fix, rule, message, permission, guard) must update in the same change:
- `documentation/{module}.md` of every module whose behaviour changed;
- the training guide (EN + BN) of every screen touched, under `resources/js/components/training/guides/{section}/`; new screens get a written guide + `config/training.php` entry `written` + shots + recipe in `tests/Browser/Training/CaptureScreenshotsTest.php`;
- `ARCHITECTURE.md` when structural.

Checks: `php artisan training:gaps` (No gaps), `php artisan test --compact tests/Feature/Training`.
Screenshots: `npm run build` then `TRAINING_SCREENS=1 vendor/bin/pest -c phpunit.browser.xml --group=training-screens --filter='<key>'`.
Enforced by `.claude/hooks/docs-sync.mjs` (PostToolUse nudge + Stop gate). New module → add to its MODULE_DOCS / GUIDE_SECTIONS.
