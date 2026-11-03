<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\ProductCategory;
use App\Models\User;
use App\Services\ProjectManagement\ProductCategoryService;
use Database\Seeders\Admin\RolePermissionSeeder;
use Database\Seeders\ProjectManagement\ProductCategorySeeder;

/*
|--------------------------------------------------------------------------
| Projects → Product Categories
|--------------------------------------------------------------------------
|
| Company-scoped as Projects → Clients (ARCHITECTURE.md §9.10): every read and
| write is the selected company's, `company_id` never comes from the browser,
| and code and name are unique per company — ignoring case, deleted rows
| included. Every company starts with a starter set (owner, 2026-11-03).
|
*/

/**
 * A user holding the permissions *and* working in the company.
 */
function productCategoryActor(Company $company, string ...$permissions): User
{
    $user = userWithPermissions(...$permissions);
    $user->companies()->attach($company);
    $user->forceFill(['selected_company_id' => $company->id])->saveQuietly();

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productCategoryPayload(array $overrides = []): array
{
    return [
        'code' => 'RAW',
        'name' => 'Raw Materials',
        'description' => null,
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.product-categories.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(productCategoryActor(Company::factory()->create()))
        ->get(route('projects.product-categories.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create();

    if ($trashed) {
        $category->delete();
    }

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.view'))
        ->{$method}(route($route, $category), productCategoryPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.product-categories.update', false],
    'delete' => ['delete', 'projects.product-categories.destroy', false],
    'restore' => ['put', 'projects.product-categories.restore', true],
    'force delete' => ['delete', 'projects.product-categories.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(productCategoryActor(Company::factory()->create(), 'projects.product-categories.view'))
        ->post(route('projects.product-categories.store'), productCategoryPayload())
        ->assertForbidden();
});

test('the project-manager role holds every product category permission and the viewer only view', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create()->assignRole('project-manager');
    $viewer = User::factory()->create()->assignRole('viewer');

    foreach (['view', 'create', 'update', 'delete', 'restore', 'force-delete'] as $action) {
        expect($manager->can("projects.product-categories.{$action}"))->toBeTrue()
            ->and($viewer->can("projects.product-categories.{$action}"))->toBe($action === 'view');
    }
});

/*
| Listing and scoping
*/

test('the list shows only the selected company\'s categories', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    ProductCategory::factory()->for($mine)->create(['code' => 'MINE']);
    ProductCategory::factory()->for($theirs)->create(['code' => 'THEIRS']);

    $this->actingAs(productCategoryActor($mine, 'projects.product-categories.view'))
        ->get(route('projects.product-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/product-categories/index')
            ->where('company.id', $mine->id)
            ->has('categories.data', 1)
            ->where('categories.data.0.code', 'MINE')
            ->where('counts', ['active' => 1, 'trashed' => 0]),
        );
});

test('the deleted tab lists only deleted categories', function () {
    $company = Company::factory()->create();
    ProductCategory::factory()->for($company)->create(['code' => 'LIVE']);
    ProductCategory::factory()->for($company)->create(['code' => 'GONE'])->delete();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.view'))
        ->get(route('projects.product-categories.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('categories.data', 1)
            ->where('categories.data.0.code', 'GONE')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('another company\'s category is a 404', function (string $method, string $route) {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = ProductCategory::factory()->for($theirs)->create();

    $this->actingAs(productCategoryActor($mine, 'projects.product-categories.update', 'projects.product-categories.delete'))
        ->{$method}(route($route, $foreign), productCategoryPayload())
        ->assertNotFound();
})->with([
    'update' => ['put', 'projects.product-categories.update'],
    'delete' => ['delete', 'projects.product-categories.destroy'],
]);

test('with no company selected the page says so and lists nothing', function () {
    ProductCategory::factory()->create();
    $user = userWithPermissions('projects.product-categories.view');
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('projects.product-categories.index'))
        ->assertInertia(fn ($page) => $page
            ->where('company', null)
            ->has('categories.data', 0),
        );
});

test('a category lands in the selected company whatever company_id is posted', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $this->actingAs(productCategoryActor($mine, 'projects.product-categories.create'));

    assertToast($this->post(route('projects.product-categories.store'), productCategoryPayload(['company_id' => $theirs->id])), 'success');

    expect(ProductCategory::withoutGlobalScopes()->sole()->company_id)->toBe($mine->id);
});

test('creating without a selected company is refused', function () {
    $user = userWithPermissions('projects.product-categories.create');

    assertToast($this->actingAs($user)->post(route('projects.product-categories.store'), productCategoryPayload()), 'warning');

    expect(ProductCategory::withoutGlobalScopes()->count())->toBe(0);
});

/*
| Validation, writes and duplicates
*/

test('a category is created with its values tidied and an empty description saved as null', function () {
    $company = Company::factory()->create();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.create'))
        ->post(route('projects.product-categories.store'), productCategoryPayload([
            'code' => ' raw-01 ',
            'name' => ' Raw   Materials ',
            'description' => '',
        ]))
        ->assertSessionHasNoErrors();

    $category = ProductCategory::withoutGlobalScopes()->sole();

    expect($category->code)->toBe('RAW-01')
        ->and($category->name)->toBe('Raw Materials')
        ->and($category->description)->toBeNull();
});

test('a malformed field is refused', function (string $field, string $value) {
    $this->actingAs(productCategoryActor(Company::factory()->create(), 'projects.product-categories.create'))
        ->post(route('projects.product-categories.store'), productCategoryPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code missing' => ['code', ''],
    'name missing' => ['name', ''],
    'code with a space' => ['code', 'RA W'],
    'name too long' => ['name', str_repeat('n', 101)],
    'status out of range' => ['status', 'X'],
]);

test('a code or name already used in the company is refused whatever its case', function (string $field, string $value) {
    $company = Company::factory()->create();
    ProductCategory::factory()->for($company)->create(['code' => 'RAW', 'name' => 'Raw Materials']);

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.create'))
        ->post(route('projects.product-categories.store'), productCategoryPayload(['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'raw'],
    'name, other case' => ['name', 'RAW MATERIALS'],
]);

test('the same code and name are allowed in another company', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    ProductCategory::factory()->for($theirs)->create(['code' => 'RAW', 'name' => 'Raw Materials']);

    $this->actingAs(productCategoryActor($mine, 'projects.product-categories.create'))
        ->post(route('projects.product-categories.store'), productCategoryPayload())
        ->assertSessionHasNoErrors();

    expect(ProductCategory::withoutGlobalScopes()->where('code', 'RAW')->count())->toBe(2);
});

test('a deleted category keeps its code and name reserved and says to restore it', function () {
    $company = Company::factory()->create();
    ProductCategory::factory()->for($company)->create(['code' => 'RAW', 'name' => 'Raw Materials'])->delete();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.create'))
        ->post(route('projects.product-categories.store'), productCategoryPayload())
        ->assertSessionHasErrors([
            'code' => 'The code "RAW" belongs to a deleted product category in this company. Restore it from the Deleted tab instead.',
            'name' => 'A deleted product category in this company is named "Raw Materials". Restore it from the Deleted tab instead.',
        ]);
});

test('a category can be edited, keeping its own code and name', function () {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create(['code' => 'RAW', 'name' => 'Raw Materials']);

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.update'))
        ->put(route('projects.product-categories.update', $category), productCategoryPayload(['status' => RecordStatus::Inactive->value, 'description' => 'Unprocessed inputs.']))
        ->assertSessionHasNoErrors();

    expect($category->refresh()->status)->toBe(RecordStatus::Inactive)
        ->and($category->description)->toBe('Unprocessed inputs.');
});

test('a category is soft deleted, restored and permanently deleted', function () {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.delete', 'projects.product-categories.restore', 'projects.product-categories.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.product-categories.destroy', $category)), 'success');
    expect($category->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.product-categories.restore', $category)), 'success');
    expect($category->refresh()->trashed())->toBeFalse();

    $category->delete();
    assertToast($this->delete(route('projects.product-categories.force-delete', $category)), 'success');

    expect(ProductCategory::withoutGlobalScopes()->withTrashed()->find($category->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'product-category')->exists())->toBeTrue();
});

test('creating a category is audited with its company', function () {
    $company = Company::factory()->create();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.create'));
    config(['audit.console' => true]);

    $this->post(route('projects.product-categories.store'), productCategoryPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'product-category')->sole();

    expect($audit->new_values['code'])->toBe('RAW')
        ->and((int) $audit->new_values['company_id'])->toBe($company->id);
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.view'))
        ->delete(route($route), ['ids' => [$category->id]])
        ->assertForbidden();
})->with(['projects.product-categories.bulk-destroy', 'projects.product-categories.bulk-force-delete']);

test('bulk delete moves the ticked categories to Deleted, never another company\'s', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    [$first, $second] = ProductCategory::factory()->for($mine)->count(2)->create();
    $foreign = ProductCategory::factory()->for($theirs)->create();

    $this->actingAs(productCategoryActor($mine, 'projects.product-categories.delete'));

    $response = $this->delete(route('projects.product-categories.bulk-destroy'), ['ids' => [$first->id, $second->id, $foreign->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 product categories moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted categories and records each', function () {
    $company = Company::factory()->create();
    [$first, $second] = ProductCategory::factory()->for($company)->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(productCategoryActor($company, 'projects.product-categories.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.product-categories.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(ProductCategory::withoutGlobalScopes()->withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'product-category')->count())->toBe(2);
});

/*
| The starter set
*/

test('the starter set skips codes and names already taken in the company, deleted ones included', function () {
    $company = Company::factory()->create();
    ProductCategory::factory()->for($company)->create(['code' => 'RAW', 'name' => 'Renamed raw']);
    ProductCategory::factory()->for($company)->create(['code' => 'X1', 'name' => 'consumables'])->delete();

    $added = app(ProductCategoryService::class)->addStarterSet($company);

    expect($added)->toBe(count(ProductCategoryService::STARTER) - 2)
        ->and(ProductCategory::withoutGlobalScopes()->where('company_id', $company->id)->where('code', 'RAW')->sole()->name)->toBe('Renamed raw')
        ->and(ProductCategory::withoutGlobalScopes()->where('company_id', $company->id)->where('code', 'CONS')->exists())->toBeFalse();
});

test('the seeder back-fills every existing company and is safe to re-run', function () {
    [$first, $second] = Company::factory()->count(2)->create();

    $this->seed(ProductCategorySeeder::class);
    $this->seed(ProductCategorySeeder::class);

    $starter = count(ProductCategoryService::STARTER);

    expect(ProductCategory::withoutGlobalScopes()->where('company_id', $first->id)->count())->toBe($starter)
        ->and(ProductCategory::withoutGlobalScopes()->where('company_id', $second->id)->count())->toBe($starter);
});
