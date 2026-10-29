# Frontend — Reference

> **Scope.** The UI standards every screen follows and the components that implement them. The rules
> themselves are recorded in [`ARCHITECTURE.md` §8](../ARCHITECTURE.md#8-frontend-wiring); this file is
> the working guide. Stack: Inertia v3, React 19 (React Compiler), TypeScript, Tailwind 4, daisyUI 5.

## 1. Styling

- **daisyUI owns the palette.** Use its semantic classes (`btn`, `badge`, `alert`, `table`, `tabs`,
  `toggle`, `file-input`) and colours (`bg-base-100`, `text-base-content`, `text-primary`,
  `border-base-300`, `text-error`). **Never hard-code a colour** — every theme, dark ones included,
  must work.
- `cn()` (`lib/utils.ts`) merges classes with a daisyUI-aware `tailwind-merge`
  (`lib/daisy-class-groups.ts`), so `cn('btn-primary', 'btn-ghost')` keeps only the last variant.
- Tables are `table table-sm` inside `overflow-x-auto rounded-box border border-base-300/70`; column
  dividers are global (`app.css`).
- Form layouts in dialogs use `field-grid` (+ `field-wide`, `field-span-2..4`).

## 2. Primitives — `components/ui/`

| Component | Notes |
| --- | --- |
| `button` | cva over `btn`; `variant` (default/secondary/outline/ghost/destructive/link), `size` (default/sm/lg/icon), `asChild` |
| `input`, `label`, `checkbox`, `separator`, `skeleton`, `spinner`, `alert`, `card`, `avatar`, `breadcrumb` | thin daisyUI wrappers |
| `dialog` | native `<dialog>`; no backdrop/Escape dismissal; X always shown; children unmount on close |
| `sheet` | the same primitive placed at an edge (mobile sidebar) |
| `dropdown-menu` | popover API, light-dismiss |
| `combobox` | downshift; single or `multiple`; `searchUrl` for server-side search; the only picker — **no native `<select>`** |
| `hover-card` | the permission help cards |
| `sidebar` | the app sidebar (collapsible to icons) |
| `sonner` | toasts in daisyUI colours |

## 3. Shared — `components/shared/`

| Component | Use |
| --- | --- |
| `list-toolbar` | page size, clear filters, refresh, plus tabs as children |
| `column-filter-row` | the filter row under a table's headers — `text`, `select`, `stack`, `none` cells |
| `sortable-header` | a `<th>` that sorts when the column is in `sortable`; `nextSort(filters, column)` |
| `pagination` | windowed pager over a Laravel paginator |
| `bulk-action-bar` | with `hooks/use-row-selection` |
| `confirm-action-dialog` / `confirm-delete-dialog` | a confirmation that submits a Wayfinder form |
| `form-dialog-footer` | Cancel / Save & add another / Save & close |
| `password-policy-checklist` | the live password rules (from the `passwordPolicy` shared prop) |
| `sheet-import-dialog` | every spreadsheet import's upload → map → confirm dialog; one per list page, never copied ([`ARCHITECTURE.md` §9.13](../ARCHITECTURE.md#913-spreadsheet-imports)) |
| `pending-import-banner` | reopens or cancels the uploader's staged import |
| `import-result-panel` / `import-reject-list` | a partial import's rejected rows, with the download |
| `preview-banner` / `preview-widgets` | the 🧪 POC overviews' "Preview" banner and sample-data cards — throwaway with those pages ([`ARCHITECTURE.md` §5](../ARCHITECTURE.md#5-module-registry)) |

Module components that are not shared yet: `components/hrm/no-company-panel.tsx` (drawn by every ERP
list when no company is selected; promoted here when a second module uses it) and the form dialogs —
`components/hrm/{department,designation,employee}-form-dialog.tsx`. The employee dialog is the
reference for a dialog with a **conditional block** (the sign-in account and its temporary password)
whose state resets with `formKey`.

## 4. Hooks

| Hook | Use |
| --- | --- |
| `useCan(permission)` / `usePermissions()` | show or hide UI by permission (`['*']` = super admin). Convenience, not authorization |
| `useListFilters({ filters, url, only, refreshOnly })` | a list's filter/sort/page state, debounced visits, refresh on focus |
| `useFormDialog(close)` | `formKey`, `formProps`, `setIntent` for the three-button footer |
| `useAvailability(field, value, ignore?)` | live username/email check |
| `useOptionSearch(searchUrl, query)` | the combobox's server-side search (`?q=` → `{ data: ComboboxOption[] }`) |
| `useRowSelection(rows)` | ticked rows for `bulk-action-bar` |
| `usePasswordPolicy()` | the policy behind the live checklist |
| `useAppearance()` / `useThemeSetting()` | the active theme; persist a choice |
| `useFlashToast()` | renders `Inertia::flash('toast', …)` (mounted once in `app.tsx`) |
| `useNavGroups(groups)` | sidebar group collapse state (call once, in `app-sidebar.tsx`) |
| `useInitials()` | avatar fallback text |
| `useUserNotifications(userId, cb)` (`lib/echo.ts`) | live push on the user's private channel |

## 5. Patterns

### 5.1 A list page

Reference: `pages/admin/users/index.tsx`.

```tsx
const { draft, visit, setFilter, clear, hasActiveFilter, refresh, refreshing } =
    useListFilters({ filters, url: index, only: ['things', 'filters'] });

<ListToolbar perPage={filters.per_page} perPageOptions={perPageOptions}
    onPerPage={(per_page) => visit({ per_page })} onClear={clear}
    hasActiveFilter={hasActiveFilter} onRefresh={refresh} refreshing={refreshing} />
<table className="table table-sm">
    <thead>
        <tr><SortableHeader column="name" sortable={sortable} filters={filters}
            onSort={(c) => visit(nextSort(filters, c))}>Name</SortableHeader> …</tr>
        <ColumnFilterRow filterable={filterable} draft={draft} onFilter={setFilter}
            cells={[{ type: 'text', column: 'name', label: 'name' }, …]} />
    </thead>
    …
</table>
<Pagination page={things} />
```

### 5.2 A form dialog

Reference: `components/admin/user-form-dialog.tsx`.

```tsx
const [open, setOpen] = useState(false);
const close = useCallback(() => setOpen(false), []);
const { formKey, formProps, setIntent } = useFormDialog(close);

<Dialog open={open} onOpenChange={setOpen}>
    <DialogTrigger asChild>{children}</DialogTrigger>
    <DialogContent className="max-w-4xl">
        <DialogTitle>…</DialogTitle>
        <Form key={formKey} {...submit} {...formProps} options={{ preserveScroll: true }}>
            {({ processing, errors }) => (<>
                <div className="field-grid">… <InputError message={errors.name} /></div>
                <FormDialogFooter processing={processing} addAnother={isCreate}
                    onIntent={setIntent} saveTestId="save-thing" />
            </>)}
        </Form>
    </DialogContent>
</Dialog>
```

Fields are uncontrolled (`name` + `defaultValue`) unless the UI reacts to them; mark invalid fields
with `aria-invalid`. `submit` is a Wayfinder `Controller.action.form(id)`.

### 5.3 Feedback

The server answers every write with `back()` and `Inertia::flash('toast', ['type', 'message'])`:
`success`, `warning` (refused, you can fix the cause), `error` (refused by a rule), `info`. Do not
show your own success messages from the client.

## 6. Theming

All 35 daisyUI themes plus **System**. The theme is set on `<html data-theme>` by the root template
before first paint and kept in step by `ThemeSync`; components must not read the theme to choose
colours — use daisyUI classes and it follows automatically.

## 6.1 Training and Bangla text

`components/training/` is the in-app manual: the vocabulary every guide is written in, the guide
loader, the EN / বাংলা toggle, the table of contents and the header's **?** (`TrainingHelpButton`,
beside the bell in `app-sidebar-header.tsx`). A guide uses the vocabulary and nothing else —
[`training.md`](training.md) §4. Bangla renders in the self-hosted **Nikosh** font, limited by
`unicode-range` to the Bengali block, so English stays Instrument Sans (`resources/css/app.css`).

## 7. Conventions

- Imports alphabetised, type imports separate (`import type`); `curly` braces always (ESLint).
- 4-space indentation, single quotes, 80 columns (Prettier + the Tailwind class sorter).
- Pages: `pages/{module}/{resource}/index.tsx`; module components in `components/{module}/`; promote
  to `shared/` when a second module needs it.
- Never edit `resources/js/actions|routes|wayfinder` or `lib/enums.ts` — they are generated.
- A changed screen means a changed guide: read its guide in `components/training/guides/` against
  the new behaviour, and recapture its screenshots ([`training.md`](training.md) §6).
