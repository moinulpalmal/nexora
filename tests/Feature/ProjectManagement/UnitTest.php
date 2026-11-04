<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\ProjectManagement\Unit;
use Database\Seeders\ProjectManagement\UnitSeeder;

/*
|--------------------------------------------------------------------------
| Projects → Settings → Units of Measure
|--------------------------------------------------------------------------
|
| A group-wide master table (ARCHITECTURE.md §9.10, owner 2026-11-02): one
| list every company shares, so no company is selected, stamped or scoped.
| Code and name are unique across the table — ignoring case, deleted units
| included; the symbol keeps its case and may repeat. Nothing references a
| unit yet, so no delete is refused. `UnitSeeder` ships a standard set
| (§9.14).
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function unitPayload(array $overrides = []): array
{
    return [
        'code' => 'KG',
        'name' => 'Kilogram',
        'symbol' => 'kg',
        'decimal_places' => 3,
        'description' => null,
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.settings.units.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('projects.settings.units.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $unit = Unit::factory()->create();

    if ($trashed) {
        $unit->delete();
    }

    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->{$method}(route($route, $unit), unitPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.settings.units.update', false],
    'delete' => ['delete', 'projects.settings.units.destroy', false],
    'restore' => ['put', 'projects.settings.units.restore', true],
    'force delete' => ['delete', 'projects.settings.units.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->post(route('projects.settings.units.store'), unitPayload())
        ->assertForbidden();
});

/*
| List
*/

test('the list works with no company selected and shows every unit', function () {
    Unit::factory()->create(['code' => 'KG', 'symbol' => 'kg', 'decimal_places' => 3]);
    Unit::factory()->create(['code' => 'PCS'])->delete();

    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->get(route('projects.settings.units.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/settings/units/index')
            ->missing('company')
            ->has('units.data', 1)
            ->where('units.data.0.code', 'KG')
            ->where('units.data.0.symbol', 'kg')
            ->where('units.data.0.decimal_places', 3)
            ->where('sortable', Unit::SORTABLE)
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('the deleted tab lists only deleted units', function () {
    Unit::factory()->create(['code' => 'KG']);
    Unit::factory()->create(['code' => 'PCS'])->delete();

    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->get(route('projects.settings.units.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('units.data', 1)
            ->where('units.data.0.code', 'PCS'),
        );
});

test('the list filters by code prefix, name and symbol, and sorts by decimal places', function () {
    Unit::factory()->create(['code' => 'SQM', 'name' => 'Square Metre', 'symbol' => 'm²', 'decimal_places' => 2]);
    Unit::factory()->create(['code' => 'SQKM', 'name' => 'Square Kilometre', 'symbol' => 'km²', 'decimal_places' => 4]);
    Unit::factory()->create(['code' => 'SFT', 'name' => 'Square Foot', 'symbol' => 'sft', 'decimal_places' => 1]);

    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->get(route('projects.settings.units.index', ['filter' => ['code' => 'SQ', 'name' => 'square', 'symbol' => '²'], 'sort' => 'decimal_places', 'direction' => 'desc']))
        ->assertInertia(fn ($page) => $page
            ->has('units.data', 2)
            ->where('units.data.0.code', 'SQKM'),
        );
});

test('a status filter outside the enum is an error, not an empty list', function () {
    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->get(route('projects.settings.units.index', ['filter' => ['status' => 'X']]))
        ->assertSessionHasErrors('filter.status');
});

/*
| Validation
*/

test('a unit is created with no company, its code uppercased, its name tidied and its symbol kept as typed', function () {
    $this->actingAs(userWithPermissions('projects.settings.units.create'));

    assertToast($this->post(route('projects.settings.units.store'), unitPayload(['code' => ' sqm ', 'name' => ' Square   Metre ', 'symbol' => ' m² ', 'description' => ' Floor area. '])), 'success');

    $unit = Unit::sole();

    expect($unit->code)->toBe('SQM')
        ->and($unit->name)->toBe('Square Metre')
        ->and($unit->symbol)->toBe('m²')
        ->and($unit->decimal_places)->toBe(3)
        ->and($unit->description)->toBe('Floor area.');
});

test('symbol and description are optional', function () {
    $this->actingAs(userWithPermissions('projects.settings.units.create'));

    assertToast($this->post(route('projects.settings.units.store'), unitPayload(['symbol' => '', 'description' => ''])), 'success');

    expect(Unit::sole())->symbol->toBeNull()->description->toBeNull();
});

test('code, name, decimal places and status are required', function () {
    $this->actingAs(userWithPermissions('projects.settings.units.create'))
        ->post(route('projects.settings.units.store'), ['code' => '', 'name' => '', 'decimal_places' => '', 'status' => ''])
        ->assertSessionHasErrors(['code', 'name', 'decimal_places', 'status']);
});

test('decimal places outside 0 to 6, a long symbol, and a code with spaces are refused', function (string $field, mixed $value) {
    $this->actingAs(userWithPermissions('projects.settings.units.create'))
        ->post(route('projects.settings.units.store'), unitPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'decimals, negative' => ['decimal_places', -1],
    'decimals, too many' => ['decimal_places', 7],
    'symbol, too long' => ['symbol', str_repeat('x', 11)],
    'code, spaces' => ['code', 'K G'],
]);

/*
| Duplicates
*/

test('a code or name already used is refused whatever its case', function (string $field, string $value) {
    Unit::factory()->create(['code' => 'KG', 'name' => 'Kilogram']);

    $this->actingAs(userWithPermissions('projects.settings.units.create'))
        ->post(route('projects.settings.units.store'), unitPayload(['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'kg'],
    'name, other case' => ['name', 'KILOGRAM'],
]);

test('two units may share a symbol', function () {
    Unit::factory()->create(['code' => 'LS', 'name' => 'Lump Sum', 'symbol' => 'LS']);

    $this->actingAs(userWithPermissions('projects.settings.units.create'));

    assertToast($this->post(route('projects.settings.units.store'), unitPayload(['code' => 'LOT', 'name' => 'Lot', 'symbol' => 'LS'])), 'success');

    expect(Unit::where('symbol', 'LS')->count())->toBe(2);
});

test('a deleted unit keeps its code and name reserved and says to restore it', function () {
    Unit::factory()->create(['code' => 'KG', 'name' => 'Kilogram'])->delete();

    $this->actingAs(userWithPermissions('projects.settings.units.create'))
        ->post(route('projects.settings.units.store'), unitPayload())
        ->assertSessionHasErrors([
            'code' => 'The code "KG" belongs to a deleted unit. Restore it from the Deleted tab instead.',
            'name' => 'A deleted unit is named "Kilogram". Restore it from the Deleted tab instead.',
        ]);
});

/*
| Writes
*/

test('a unit can be edited, keeping its own code and name', function () {
    $unit = Unit::factory()->create(['code' => 'KG', 'name' => 'Kilogram']);

    $this->actingAs(userWithPermissions('projects.settings.units.update'));

    assertToast($this->put(route('projects.settings.units.update', $unit), unitPayload(['decimal_places' => 2, 'status' => RecordStatus::Inactive->value])), 'success');

    expect($unit->refresh())
        ->decimal_places->toBe(2)
        ->status->toBe(RecordStatus::Inactive);
});

test('a unit is soft deleted, restored and permanently deleted', function () {
    $unit = Unit::factory()->create();

    $this->actingAs(userWithPermissions('projects.settings.units.delete', 'projects.settings.units.restore', 'projects.settings.units.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.settings.units.destroy', $unit)), 'success');
    expect($unit->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.settings.units.restore', $unit)), 'success');
    expect($unit->refresh()->trashed())->toBeFalse();

    $unit->delete();
    assertToast($this->delete(route('projects.settings.units.force-delete', $unit)), 'success');

    expect(Unit::withTrashed()->find($unit->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'unit')->exists())->toBeTrue();
});

test('creating a unit is audited', function () {
    $this->actingAs(userWithPermissions('projects.settings.units.create'));
    config(['audit.console' => true]);

    $this->post(route('projects.settings.units.store'), unitPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'unit')->sole();

    expect($audit->new_values['code'])->toBe('KG');
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $unit = Unit::factory()->create();

    $this->actingAs(userWithPermissions('projects.settings.units.view'))
        ->delete(route($route), ['ids' => [$unit->id]])
        ->assertForbidden();
})->with(['projects.settings.units.bulk-destroy', 'projects.settings.units.bulk-force-delete']);

test('bulk delete moves the ticked units to Deleted', function () {
    [$first, $second, $untouched] = Unit::factory()->count(3)->create();

    $this->actingAs(userWithPermissions('projects.settings.units.delete'));

    assertToast($this->delete(route('projects.settings.units.bulk-destroy'), ['ids' => [$first->id, $second->id]]), 'success');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($untouched->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete destroys only deleted units', function () {
    $deleted = Unit::factory()->create();
    $deleted->delete();
    $live = Unit::factory()->create();

    $this->actingAs(userWithPermissions('projects.settings.units.force-delete'));

    assertToast($this->delete(route('projects.settings.units.bulk-force-delete'), ['ids' => [$deleted->id, $live->id]]), 'success');

    expect(Unit::withTrashed()->find($deleted->id))->toBeNull()
        ->and($live->refresh()->exists)->toBeTrue();
});

/*
| Seeder
*/

test('the seeder adds every standard unit once and is safe to re-run', function () {
    $this->seed(UnitSeeder::class);
    $this->seed(UnitSeeder::class);

    expect(Unit::count())->toBe(count(UnitSeeder::UNITS))
        ->and(Unit::where('code', 'SQM')->sole())
        ->symbol->toBe('m²')
        ->decimal_places->toBe(2);
});

test('the seeder never overwrites an edited, renamed or deleted unit', function () {
    $this->seed(UnitSeeder::class);

    Unit::where('code', 'KG')->sole()->update(['decimal_places' => 6]);
    Unit::where('code', 'PCS')->sole()->update(['code' => 'PC']);
    Unit::where('code', 'HR')->sole()->delete();

    $this->seed(UnitSeeder::class);

    expect(Unit::where('code', 'KG')->sole()->decimal_places)->toBe(6)
        ->and(Unit::where('name', 'Pieces')->count())->toBe(1)
        ->and(Unit::where('code', 'PCS')->exists())->toBeFalse()
        ->and(Unit::withTrashed()->where('code', 'HR')->count())->toBe(1)
        ->and(Unit::where('code', 'HR')->exists())->toBeFalse();
});
