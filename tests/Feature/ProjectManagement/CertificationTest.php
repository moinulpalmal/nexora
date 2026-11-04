<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\ProjectManagement\Certification;
use App\Models\ProjectManagement\CertificationCategory;
use Database\Seeders\ProjectManagement\CertificationCategorySeeder;
use Database\Seeders\ProjectManagement\CertificationSeeder;

/*
|--------------------------------------------------------------------------
| Projects → Settings → Certifications
|--------------------------------------------------------------------------
|
| A group-wide catalogue of standards (ARCHITECTURE.md §9.10, owner
| 2026-11-02) in the Tender Types shape, with a required group-wide category
| as suppliers have. The code is unique across the table; the name is unique
| per version, a blank version counting as one edition — ignoring case,
| deleted certifications included. Nothing references a certification yet, so
| no delete is refused. `CertificationSeeder` ships a standard set.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function certificationPayload(array $overrides = []): array
{
    return [
        'certification_category_id' => $overrides['certification_category_id'] ?? CertificationCategory::factory()->create()->id,
        'code' => 'ISO9001-2015',
        'name' => 'ISO 9001 Quality Management Systems',
        'issuing_body' => 'ISO',
        'version' => '2015',
        'description' => null,
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.settings.certifications.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(userWithPermissions())
        ->get(route('projects.settings.certifications.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $certification = Certification::factory()->create();

    if ($trashed) {
        $certification->delete();
    }

    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->{$method}(route($route, $certification), certificationPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.settings.certifications.update', false],
    'delete' => ['delete', 'projects.settings.certifications.destroy', false],
    'restore' => ['put', 'projects.settings.certifications.restore', true],
    'force delete' => ['delete', 'projects.settings.certifications.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->post(route('projects.settings.certifications.store'), certificationPayload())
        ->assertForbidden();
});

/*
| Group-wide
*/

test('the list works with no company selected and shows every certification with its category', function () {
    $category = CertificationCategory::factory()->create(['name' => 'Quality Management']);
    Certification::factory()->for($category, 'category')->create(['code' => 'ISO9001-2015']);
    Certification::factory()->create(['code' => 'CE'])->delete();

    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->get(route('projects.settings.certifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/settings/certifications/index')
            ->missing('company')
            ->has('certifications.data', 1)
            ->where('certifications.data.0.code', 'ISO9001-2015')
            ->where('certifications.data.0.category_name', 'Quality Management')
            ->has('categories', 2)
            ->where('sortable', Certification::SORTABLE)
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('the deleted tab lists only deleted certifications', function () {
    Certification::factory()->create(['code' => 'ISO9001-2015']);
    Certification::factory()->create(['code' => 'CE'])->delete();

    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->get(route('projects.settings.certifications.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('certifications.data', 1)
            ->where('certifications.data.0.code', 'CE'),
        );
});

test('the list filters by code prefix, name, category and issuing body, and sorts by version', function () {
    $quality = CertificationCategory::factory()->create();
    Certification::factory()->for($quality, 'category')->create(['code' => 'ISO9001-2008', 'name' => 'ISO 9001 Quality', 'version' => '2008', 'issuing_body' => 'ISO']);
    Certification::factory()->for($quality, 'category')->create(['code' => 'ISO9001-2015', 'name' => 'ISO 9001 Quality', 'version' => '2015', 'issuing_body' => 'ISO']);
    Certification::factory()->create(['code' => 'ISO14001-2015', 'name' => 'ISO 14001 Environmental', 'version' => '2015', 'issuing_body' => 'ISO']);

    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->get(route('projects.settings.certifications.index', [
            'filter' => ['code' => 'ISO', 'name' => 'quality', 'certification_category_id' => (string) $quality->id, 'issuing_body' => 'is'],
            'sort' => 'version',
            'direction' => 'desc',
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('certifications.data', 2)
            ->where('certifications.data.0.code', 'ISO9001-2015'),
        );
});

test('a status or category filter of the wrong kind is an error, not an empty list', function (string $column, string $value) {
    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->get(route('projects.settings.certifications.index', ['filter' => [$column => $value]]))
        ->assertSessionHasErrors("filter.{$column}");
})->with([
    'status' => ['status', 'X'],
    'category' => ['certification_category_id', 'quality'],
]);

test('a certification is created with no company, its code uppercased and its text tidied', function () {
    $this->actingAs(userWithPermissions('projects.settings.certifications.create'));

    assertToast($this->post(route('projects.settings.certifications.store'), certificationPayload([
        'code' => ' iso9001-2015 ',
        'name' => ' ISO 9001   Quality Management Systems ',
        'issuing_body' => '  ISO ',
        'version' => ' 2015 ',
    ])), 'success');

    $certification = Certification::sole();

    expect($certification->code)->toBe('ISO9001-2015')
        ->and($certification->name)->toBe('ISO 9001 Quality Management Systems')
        ->and($certification->issuing_body)->toBe('ISO')
        ->and($certification->version)->toBe('2015');
});

test('an empty issuing body, version and description are saved as null', function () {
    $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), certificationPayload(['code' => 'CE', 'name' => 'CE Marking', 'issuing_body' => '', 'version' => '', 'description' => '']))
        ->assertSessionHasNoErrors();

    expect(Certification::sole()->only(['issuing_body', 'version', 'description']))
        ->toBe(['issuing_body' => null, 'version' => null, 'description' => null]);
});

test('category, code, name and status are required', function () {
    $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), ['certification_category_id' => '', 'code' => '', 'name' => '', 'status' => ''])
        ->assertSessionHasErrors(['certification_category_id' => 'The category field is required.', 'code', 'name', 'status']);
});

test('a code with spaces or symbols is refused', function () {
    $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), certificationPayload(['code' => 'ISO 9001/2015']))
        ->assertSessionHasErrors('code');
});

/*
| The category
*/

test('an inactive or deleted category cannot be chosen', function (bool $deleted) {
    $category = CertificationCategory::factory()->inactive()->create();

    if ($deleted) {
        $category->update(['status' => RecordStatus::Active]);
        $category->delete();
    }

    $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), certificationPayload(['certification_category_id' => $category->id]))
        ->assertSessionHasErrors(['certification_category_id' => 'Choose an active certification category.']);
})->with(['inactive' => false, 'deleted' => true]);

test('a certification keeps its category on edit after the category is deactivated', function () {
    $category = CertificationCategory::factory()->create();
    $certification = Certification::factory()->for($category, 'category')->create(['code' => 'ISO9001-2015', 'name' => 'ISO 9001 Quality Management Systems', 'version' => '2015']);
    $category->update(['status' => RecordStatus::Inactive]);

    $this->actingAs(userWithPermissions('projects.settings.certifications.update'))
        ->put(route('projects.settings.certifications.update', $certification), certificationPayload(['certification_category_id' => $category->id, 'issuing_body' => 'ISO (Geneva)']))
        ->assertSessionHasNoErrors();

    expect($certification->refresh()->issuing_body)->toBe('ISO (Geneva)');
});

/*
| Duplicates
*/

test('a code already used is refused whatever its case', function () {
    Certification::factory()->create(['code' => 'ISO9001-2015']);

    $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), certificationPayload(['code' => 'iso9001-2015', 'name' => 'Something else']))
        ->assertSessionHasErrors('code');
});

test('a name may repeat across versions but not within one', function (?string $existing, ?string $typed, bool $refused) {
    Certification::factory()->create(['code' => 'EXISTING', 'name' => 'ISO 9001 Quality Management Systems', 'version' => $existing]);

    $response = $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), certificationPayload(['name' => 'iso 9001  quality management systems', 'version' => $typed]));

    $refused ? $response->assertSessionHasErrors('name') : $response->assertSessionHasNoErrors();
})->with([
    'same version, other case' => ['2015', '2015', true],
    'another version' => ['2008', '2015', false],
    'both unversioned' => [null, '', true],
    'one unversioned' => [null, '2015', false],
]);

test('a deleted certification keeps its code and name reserved and says to restore it', function () {
    Certification::factory()->create(['code' => 'ISO9001-2015', 'name' => 'ISO 9001 Quality Management Systems', 'version' => '2015'])->delete();

    $this->actingAs(userWithPermissions('projects.settings.certifications.create'))
        ->post(route('projects.settings.certifications.store'), certificationPayload())
        ->assertSessionHasErrors([
            'code' => 'The code "ISO9001-2015" belongs to a deleted certification. Restore it from the Deleted tab instead.',
            'name' => 'A deleted certification is named "ISO 9001 Quality Management Systems" in this version. Restore it from the Deleted tab instead.',
        ]);
});

/*
| Writes
*/

test('a certification can be edited, keeping its own values', function () {
    $certification = Certification::factory()->create(['code' => 'ISO9001-2015', 'name' => 'ISO 9001 Quality Management Systems', 'version' => '2015']);

    $this->actingAs(userWithPermissions('projects.settings.certifications.update'));

    assertToast($this->put(route('projects.settings.certifications.update', $certification), certificationPayload(['status' => RecordStatus::Inactive->value])), 'success');

    expect($certification->refresh()->status)->toBe(RecordStatus::Inactive);
});

test('a certification is soft deleted, restored and permanently deleted', function () {
    $certification = Certification::factory()->create();

    $this->actingAs(userWithPermissions('projects.settings.certifications.delete', 'projects.settings.certifications.restore', 'projects.settings.certifications.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.settings.certifications.destroy', $certification)), 'success');
    expect($certification->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.settings.certifications.restore', $certification)), 'success');
    expect($certification->refresh()->trashed())->toBeFalse();

    $certification->delete();
    assertToast($this->delete(route('projects.settings.certifications.force-delete', $certification)), 'success');

    expect(Certification::withTrashed()->find($certification->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'certification')->exists())->toBeTrue();
});

test('creating a certification is audited', function () {
    $this->actingAs(userWithPermissions('projects.settings.certifications.create'));
    $payload = certificationPayload();
    config(['audit.console' => true]);

    $this->post(route('projects.settings.certifications.store'), $payload);

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'certification')->sole();

    expect($audit->new_values['code'])->toBe('ISO9001-2015');
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $certification = Certification::factory()->create();

    $this->actingAs(userWithPermissions('projects.settings.certifications.view'))
        ->delete(route($route), ['ids' => [$certification->id]])
        ->assertForbidden();
})->with(['projects.settings.certifications.bulk-destroy', 'projects.settings.certifications.bulk-force-delete']);

test('bulk delete moves the ticked certifications to Deleted', function () {
    [$first, $second, $untouched] = Certification::factory()->count(3)->create();

    $this->actingAs(userWithPermissions('projects.settings.certifications.delete'));

    assertToast($this->delete(route('projects.settings.certifications.bulk-destroy'), ['ids' => [$first->id, $second->id]]), 'success');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($untouched->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete destroys only deleted certifications', function () {
    $deleted = Certification::factory()->create();
    $deleted->delete();
    $live = Certification::factory()->create();

    $this->actingAs(userWithPermissions('projects.settings.certifications.force-delete'));

    assertToast($this->delete(route('projects.settings.certifications.bulk-force-delete'), ['ids' => [$deleted->id, $live->id]]), 'success');

    expect(Certification::withTrashed()->find($deleted->id))->toBeNull()
        ->and($live->refresh()->exists)->toBeTrue();
});

/*
| The seeders
*/

test('the seeders add every standard category and certification once, filed by category code', function () {
    $this->seed([CertificationCategorySeeder::class, CertificationSeeder::class]);
    $this->seed([CertificationCategorySeeder::class, CertificationSeeder::class]);

    expect(CertificationCategory::count())->toBe(count(CertificationCategorySeeder::CATEGORIES))
        ->and(Certification::count())->toBe(count(CertificationSeeder::CERTIFICATIONS))
        ->and(Certification::firstWhere('code', 'BSCI')->category->code)->toBe('SOC');
});

test('the certification seeder never overwrites an edit and skips a taken code or name', function () {
    $this->seed(CertificationCategorySeeder::class);
    $category = CertificationCategory::firstWhere('code', 'QMS');
    Certification::factory()->for($category, 'category')->create(['code' => 'ISO9001-2015', 'name' => 'ISO 9001 (our wording)', 'version' => '2015']);
    Certification::factory()->for($category, 'category')->create(['code' => 'MY-CE', 'name' => 'CE Marking', 'version' => null])->delete();

    $this->seed(CertificationSeeder::class);

    expect(Certification::firstWhere('code', 'ISO9001-2015')->name)->toBe('ISO 9001 (our wording)')
        ->and(Certification::withTrashed()->where('name', 'CE Marking')->count())->toBe(1)
        ->and(Certification::withTrashed()->count())->toBe(count(CertificationSeeder::CERTIFICATIONS));
});

test('the certification seeder skips a row whose category code is gone', function () {
    $this->seed(CertificationCategorySeeder::class);
    CertificationCategory::firstWhere('code', 'RADAR')->forceDelete();

    $this->seed(CertificationSeeder::class);

    $radar = collect(CertificationSeeder::CERTIFICATIONS)->where(4, 'RADAR')->count();

    expect(Certification::count())->toBe(count(CertificationSeeder::CERTIFICATIONS) - $radar);
});
