# Restorable shape

The global shape plus **soft deletes**: deleting a row moves it to a Deleted tab from which it can be
restored, and only a separate, more powerful action removes it for good. Choose it when a deleted row
must stay recoverable or keep its identifier reserved — not by default. Status (`A`/`I`) and deletion
are different verbs (§9.3.1): an inactive row is retired from pickers but still present; a deleted
one is gone from the list.

**Read first** — the users surface is the only restorable one, so read it for the restorable *parts*
and ignore what is user-specific (passwords, avatars, roles, self-protection):

- `app/Http/Controllers/Admin/UserController.php` — `index()` (the `view` tab), `destroy()`,
  `restore()`, `forceDelete()`, `refuse()`
- `app/Services/Admin/UserService.php` — `delete()`, `restore()`, `forceDelete()`,
  `deletionBlocker()`, `forceDeletionBlocker()`
- `app/Http/Requests/Admin/UserIndexRequest.php` — `filterRules()` / `filterValues()` / `view()`
- `routes/admin.php` — the users block (restore and force-delete routes)
- `resources/js/pages/admin/users/index.tsx` — the Active/Deleted tabs inside `ListToolbar`
- `tests/Feature/Admin/UserTest.php`

---

## What the global shape gains

- **Migration:** `softDeletes()`. Decide whether unique indexes include trashed rows — on users they
  do, so a retired username stays reserved (§9.6). If a deleted row's code should be reusable, the
  unique must exclude trashed rows (a partial index is not portable to SQLite; ask before going there).
- **Model:** `use SoftDeletes` beside the other traits.
- **Index request:** a `view` filter (`'sometimes', 'in:active,trashed'`) carried through
  `filterValues()`; the controller applies `onlyTrashed()` for the Deleted tab.
- **Permissions:** `restore` and `force-delete` in the catalogue beside the four CRUD actions, each
  with its `PermissionDescriptions` sentence. Force delete is the destructive one; decide which roles
  get it rather than letting a prefix rule decide.
- **Routes**, after the resource (they cannot collide with it):

  ```php
  Route::put('<x>/{x}/restore', [XController::class, 'restore'])->withTrashed()
      ->middleware('permission:{module}.<x>.restore')->name('<x>.restore');
  Route::delete('<x>/{x}/force', [XController::class, 'forceDelete'])->withTrashed()
      ->middleware('permission:{module}.<x>.force-delete')->name('<x>.force-delete');
  ```

  Check the users block for the exact form — `withTrashed()` is what lets route binding find a
  deleted row.
- **Service:** `restore()` and `forceDelete()`. A soft delete never trips a `restrictOnDelete` FK, so
  `deletionBlocker()` is about whether hiding the row is acceptable; **`forceDeletionBlocker()`** is
  where the §9.14 reference count belongs, because only a hard delete meets the constraint. A force
  delete that should leave a trace records it through `AuditRecorder` first (the row's own audit
  trail goes with it otherwise).
- **Page:** Active / Deleted tabs as children of `ListToolbar`; row actions differ per tab (edit and
  delete on Active; restore and delete-for-good on Deleted, each behind its own `useCan`); the
  force-delete confirmation says it cannot be undone.
- **Tests:** delete moves the row to the Deleted tab; restore brings it back; force delete removes it
  and is refused while the row is referenced; each new action is forbidden without its permission.
