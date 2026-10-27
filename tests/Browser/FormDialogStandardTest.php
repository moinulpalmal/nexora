<?php

use App\Models\Admin\Role;

/*
|--------------------------------------------------------------------------
| The form-modal standard, driven in a real browser
|--------------------------------------------------------------------------
|
| ARCHITECTURE.md §8.10: every create/edit modal wears Cancel, Save & add
| another and Save & close, and each one does something specific to the form
| and the panel. A feature test posts a payload and never learns whether a
| panel closed, a form emptied or where focus went — the gap §13.2 keeps this
| suite for.
|
| Proved once, against `admin/roles`, because every dialog shares
| `hooks/use-form-dialog.ts` and `components/shared/form-dialog-footer.tsx`: a
| regression in the contract is a regression in those two files. Roles is the
| smallest surface with a create and an edit dialog. The per-field rules are
| feature tests (`tests/Feature/Admin/RoleTest.php`).
|
*/

/**
 * Read a control's value out of the open dialog.
 *
 * Scoped to the `<form>`, because the list behind the panel holds the same
 * text. `script()` does not retry, so every call follows an assertion that
 * waits for the state it reads.
 */
function dialogValue(object $page, string $name): string
{
    return (string) $page->script(<<<JS
        (() => document.querySelector('form [name="{$name}"]')?.value ?? '')();
    JS);
}

/** Whether the named control is painted as rejected. */
function dialogInvalid(object $page, string $name): bool
{
    return (bool) $page->script(<<<JS
        (() => document.querySelector('form #{$name}')?.getAttribute('aria-invalid') === 'true')();
    JS);
}

/** The `id` of whatever holds focus. */
function focusedId(object $page): string
{
    return (string) $page->script('(() => document.activeElement?.id ?? "")();');
}

function openCreateRoleDialog(object $page, string $name): object
{
    return $page->click('[data-test="new-role"]')
        ->fill('input[name="name"]', $name);
}

test('save & add another saves the row, keeps the panel open and clears the form', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.create'));

    $page = visit('/admin/roles');

    openCreateRoleDialog($page, 'data-entry')
        ->click('Save & add another')
        // The toast says the round trip finished; the reads below do not retry.
        ->waitForText('Role created.')
        // Only rendered inside the panel, so it tells an open dialog from a closed one.
        ->assertSee('Save & add another')
        ->assertNoJavaScriptErrors();

    expect(dialogValue($page, 'name'))->toBe('')
        // Focus back on the first field, so the next record can be typed at once.
        ->and(focusedId($page))->toBe('name')
        ->and(Role::where('name', 'data-entry')->exists())->toBeTrue();
});

/**
 * A *failed* save must not clear anything — retyping a rejected form is the
 * worst outcome the standard can produce, so the remount is bound to success.
 */
test('a rejected save & add another keeps the panel, the value and the error', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.create'));
    Role::findOrCreate('data-entry', 'web');

    $page = visit('/admin/roles');

    openCreateRoleDialog($page, 'data-entry')
        ->click('Save & add another')
        ->waitForText('The name has already been taken.')
        ->assertSee('Save & add another')
        ->assertNoJavaScriptErrors();

    expect(dialogValue($page, 'name'))->toBe('data-entry')
        ->and(dialogInvalid($page, 'name'))->toBeTrue()
        ->and(focusedId($page))->toBe('name')
        ->and(Role::where('name', 'data-entry')->count())->toBe(1);
});

test('save & close saves the row and closes the panel', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.create'));

    visit('/admin/roles')
        ->click('[data-test="new-role"]')
        ->fill('input[name="name"]', 'store-keeper')
        ->click('Save & close')
        ->waitForText('Role created.')
        ->assertDontSee('Save & add another')
        ->assertNoJavaScriptErrors();

    expect(Role::where('name', 'store-keeper')->exists())->toBeTrue();
});

/**
 * Cancel has no code of its own — `DialogContent` unmounts its children on
 * close, so the next open re-seeds from props. Nothing says that out loud,
 * which is why it is pinned.
 */
test('cancel closes the panel and the form comes back empty', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.create'));

    $page = visit('/admin/roles');

    openCreateRoleDialog($page, 'abandoned')
        ->click('Cancel')
        ->assertDontSee('Save & add another')
        ->click('[data-test="new-role"]')
        ->assertSee('Save & add another')
        ->assertNoJavaScriptErrors();

    expect(dialogValue($page, 'name'))->toBe('')
        ->and(Role::where('name', 'abandoned')->exists())->toBeFalse();
});

/**
 * An edit modal posts to that record's update route, so "add another" would
 * re-save the same row. It is not offered, and Cancel restores the stored row.
 */
test('an edit modal offers no save & add another, and cancel restores the row', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.update'));
    Role::findOrCreate('quality-head', 'web');

    $page = visit('/admin/roles');

    $page->click('[aria-label="Edit quality-head"]')
        ->assertSee('Save & close')
        ->assertDontSee('Save & add another')
        ->fill('input[name="name"]', 'something-else')
        ->click('Cancel')
        ->click('[aria-label="Edit quality-head"]')
        ->assertSee('Save & close')
        ->assertNoJavaScriptErrors();

    expect(dialogValue($page, 'name'))->toBe('quality-head');
});

/**
 * Implicit submission fires the first submit button in tree order, which the
 * footer's DOM order makes "Save & add another" on a create modal (§8.10).
 */
test('enter in a field fires the leftmost save button', function () {
    $this->actingAs(userWithPermissions('admin.roles.view', 'admin.roles.create'));

    $page = visit('/admin/roles');

    // `keys()`, not `press()` — the latter clicks a button labelled "Enter".
    openCreateRoleDialog($page, 'line-lead')
        ->keys('input[name="name"]', 'Enter')
        ->waitForText('Role created.')
        ->assertSee('Save & add another')
        ->assertNoJavaScriptErrors();

    expect(dialogValue($page, 'name'))->toBe('')
        ->and(Role::where('name', 'line-lead')->exists())->toBeTrue();
});
