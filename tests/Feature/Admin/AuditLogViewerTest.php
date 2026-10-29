<?php

use App\Models\Admin\AuditLog;
use App\Models\Admin\Role;
use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;

/*
| The viewer reads rows the tests write by hand, so it is proved against rows
| it did not produce itself. The writing side is AuditTrailTest.
*/

/**
 * @param  array<string, mixed>  $attributes
 */
function auditRow(array $attributes = []): AuditLog
{
    return AuditLog::query()->create([
        'event' => 'updated',
        'auditable_type' => 'user',
        'auditable_id' => 1,
        'actor_name' => 'Ada',
        'actor_username' => 'ada',
        'old_values' => ['name' => 'Before'],
        'new_values' => ['name' => 'After'],
        'ip_address' => '10.0.0.1',
        ...$attributes,
    ]);
}

test('the trail is refused without the permission', function () {
    $this->actingAs(userWithPermissions('admin.users.view'))
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();
});

test('only super-admin is seeded the permission to read the trail', function () {
    $this->seed(RolePermissionSeeder::class);

    $holders = User::permission('admin.audit-logs.view')->count()
        + collect(['admin', 'viewer'])->filter(fn (string $role): bool => Role::findByName($role)->hasPermissionTo('admin.audit-logs.view'))->count();

    expect($holders)->toBe(0);
});

test('the list renders newest first', function () {
    $this->actingAs(superAdmin());
    $old = auditRow(['created_at' => now()->subDay()]);
    $new = auditRow(['created_at' => now()]);

    $this->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/audit-logs/index')
            ->where('filters.direction', 'desc')
            ->where('auditLogs.data.0.id', $new->id)
            ->where('auditLogs.data.1.id', $old->id)
            ->where('auditLogs.data.0.changed', ['name'])
            ->where('auditLogs.data.0.event_label', 'Updated'),
        );
});

test('the model type filter is the morph map, never hand-listed', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.audit-logs.index'))
        ->assertInertia(fn ($page) => $page->where('modelTypes', [
            ['value' => 'branding-setting', 'label' => 'Branding setting'],
            ['value' => 'company', 'label' => 'Company'],
            ['value' => 'department', 'label' => 'Department'],
            ['value' => 'designation', 'label' => 'Designation'],
            ['value' => 'import-limit', 'label' => 'Import limit'],
            ['value' => 'mail-setting', 'label' => 'Mail setting'],
            ['value' => 'permission', 'label' => 'Permission'],
            ['value' => 'quick-login-setting', 'label' => 'Quick login setting'],
            ['value' => 'role', 'label' => 'Role'],
            ['value' => 'sheet-import', 'label' => 'Sheet import'],
            ['value' => 'user', 'label' => 'User'],
        ]));
});

test('filters narrow the trail and reject values outside their sources', function () {
    $this->actingAs(superAdmin());
    auditRow(['event' => 'logged-in']);
    auditRow(['event' => 'updated']);

    $this->get(route('admin.audit-logs.index', ['filter' => ['event' => 'logged-in']]))
        ->assertInertia(fn ($page) => $page->has('auditLogs.data', 1));

    $this->get(route('admin.audit-logs.index', ['filter' => ['event' => 'made-up']]))->assertSessionHasErrors('filter.event');
    $this->get(route('admin.audit-logs.index', ['filter' => ['auditable_type' => 'App\\Models\\User']]))->assertSessionHasErrors('filter.auditable_type');
});

test('user ids inside the values read as names', function () {
    $editor = User::factory()->create(['name' => 'Eddie', 'username' => 'eddie']);
    $this->actingAs(superAdmin());
    auditRow(['new_values' => ['last_updated_by' => $editor->id]]);

    $this->get(route('admin.audit-logs.index'))
        ->assertInertia(fn ($page) => $page->where('auditLogs.data.0.new_values.last_updated_by', 'Eddie (eddie)'));
});

test('a record history returns every audit for that record, newest first', function () {
    $this->actingAs(superAdmin());
    auditRow(['auditable_id' => 7, 'created_at' => now()->subHour()]);
    auditRow(['auditable_id' => 7, 'event' => 'roles-changed', 'created_at' => now()]);
    auditRow(['auditable_id' => 8]);

    $this->getJson(route('admin.audit-logs.history', ['type' => 'user', 'id' => 7]))
        ->assertOk()
        ->assertJsonCount(2, 'audits')
        ->assertJsonPath('audits.0.event', 'roles-changed');
});

test('the history endpoint refuses a class name where an alias belongs', function () {
    $this->actingAs(superAdmin())
        ->getJson(route('admin.audit-logs.history', ['type' => 'App\\Models\\User', 'id' => 1]))
        ->assertUnprocessable();
});
