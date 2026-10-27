---
name: daisyui-ui
description: Nexora's UI standards on daisyUI 5 — which primitive to reach for, the list page, form dialog and confirmation standards, toasts, theming and layout utilities. Invoke for any work in resources/js (pages, dialogs, forms, tables, menus, styling), and before adding or restyling a component.
---

# Nexora UI (daisyUI 5)

The working reference is `documentation/frontend.md`; the rules and their reasons are
`ARCHITECTURE.md` §8. This skill is the checklist. When the code disagrees with it, the code wins —
fix the skill.

Use Serena (`get_symbols_overview`, `find_symbol`, `replace_symbol_body`) to read and edit the
components.

## Hard rules

1. **No hard-coded colours.** daisyUI semantic classes only — `btn-primary`, `bg-base-100`,
   `bg-base-200`, `text-base-content`, `text-base-content/60`, `border-base-300`, `text-error`,
   `badge-success`, `alert-warning`. Thirty-five themes, dark ones included, must all work; a
   `text-gray-500` or `bg-white` breaks half of them.
2. **No native `<select>`.** Every choice is `components/ui/combobox.tsx` (downshift) — single,
   `multiple`, or server-searched with `searchUrl` (§8.5). Disabled means absent: a disabled combobox
   submits nothing.
3. **Modals never light-dismiss** (no backdrop click, no Escape); the X is always there; closing
   unmounts the children, so reopening starts clean. Menus always light-dismiss (§8.7).
4. **A form dialog has three buttons** — `FormDialogFooter`: Cancel, Save & add another (create
   only), Save & close — wired through `useFormDialog` (§8.10).
5. **Feedback comes from the server** as a flash toast (`success` / `warning` / `error` / `info`);
   the client does not invent its own success messages (§8.8).
6. **Every list is paginated, sortable and filtered per column** (§8.6) — no unbounded tables, no
   single global search box.
7. **Merge classes with `cn()`** (`lib/utils.ts`), which understands daisyUI variants — never string
   concatenation.
8. **Never edit generated files**: `resources/js/actions|routes|wayfinder`, `lib/enums.ts`.

## Which component

| Need | Use |
| --- | --- |
| Button / icon button | `ui/button` (`variant`, `size="icon"`, `asChild` around a `Link`) |
| Text field | `ui/input` + `ui/label` + `InputError` |
| Choice from a list | `ui/combobox` |
| Yes/no | `ui/checkbox`, or a daisyUI `toggle` for a setting |
| Modal form | `ui/dialog` + `useFormDialog` + `shared/form-dialog-footer` |
| "Are you sure?" | `shared/confirm-action-dialog` / `shared/confirm-delete-dialog` |
| Row actions menu | `ui/dropdown-menu` |
| Explanation on hover | `ui/hover-card` |
| Side panel (mobile) | `ui/sheet` |
| List chrome | `shared/list-toolbar`, `shared/sortable-header` + `nextSort`, `shared/column-filter-row`, `shared/pagination` |
| Bulk actions | `shared/bulk-action-bar` + `hooks/use-row-selection` |
| Loading | `ui/spinner`, `ui/skeleton` |
| Notice | `ui/alert` |
| Person | `ui/avatar` (photo, initials fallback via `useInitials`) |
| Password field with rules | `shared/password-policy-checklist` |

Not there? Build it in `components/{module}/` from daisyUI classes; promote it to `components/shared/`
only when a **second module** imports it (§6.5). A new primitive in `ui/` is a thin daisyUI wrapper
with a cva variant map where it has variants — read `ui/button.tsx` first.

## The list page

Mirror `pages/admin/users/index.tsx`:

- `useListFilters({ filters, url, only })` owns filter/sort/page state (400 ms debounce, back to page
  1 on a filter change, refresh on window focus).
- `ListToolbar` (page size, clear, refresh; tabs as children — Active/Deleted on a restorable list).
- `table table-sm` inside `overflow-x-auto rounded-box border border-base-300/70`.
- `SortableHeader` on every column in `sortable`; `ColumnFilterRow` with one cell **per `<th>`**
  (`{ type: 'none' }` for the actions column).
- Action buttons behind `useCan(...)` — it hides UI, it does not authorise; the route does.
- An empty result is a row saying so, not a blank table.

## The form dialog

Mirror `components/admin/user-form-dialog.tsx` / `role-form-dialog.tsx`:

- `<Form key={formKey} {...submit} {...formProps} options={{ preserveScroll: true }}>` where `submit`
  is the Wayfinder `Controller.action.form(...)`.
- Fields uncontrolled (`name` + `defaultValue`) unless the UI reacts to them; the first control
  `autoFocus`; `aria-invalid={!!errors.x}` and `<InputError message={errors.x} />` on each.
- Layout with `field-grid` (one column, two from `md`), `field-wide` / `field-span-2..4` for wide
  fields (§8.12). Two columns is a ceiling, not a target.
- `DialogContent className="max-w-4xl"` for a two-column form; long content scrolls inside the panel
  so the footer stays visible.
- A dialog acting on one named record (password, roles) has no "add another".
- A confirmation raised inside another dialog closes the parent through `onSuccess` (§8.11).

## Theming

The theme is `data-theme` on `<html>`, set before first paint and kept in step by `ThemeSync`.
Components never read the theme to pick colours. Persist a choice with `useThemeSetting()`; read the
active one with `useAppearance()`. Check a new screen in one light and one dark theme.

## Verify

`npm run format && npm run lint && npm run types:check`; `npm run build` before feature tests that
render the page. For a visual check, offer Playwright against `APP_URL` (see `CLAUDE.md` §6) — only
when the owner asks or to confirm your own UI change.
