<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Product;
use App\Models\ProjectManagement\ProductCategory;
use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;

/*
|--------------------------------------------------------------------------
| Projects → Settings → Products
|--------------------------------------------------------------------------
|
| Company-scoped as Projects → Settings → Suppliers (ARCHITECTURE.md §9.10): every read
| and write is the selected company's, `company_id` never comes from the
| browser, and code and name are unique per company — ignoring case, deleted
| rows included. The category is the company's own, and must be active to be
| chosen (owner, 2026-11-03).
|
*/

/**
 * A user holding the permissions *and* working in the company.
 */
function productActor(Company $company, string ...$permissions): User
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
function productPayload(ProductCategory $category, array $overrides = []): array
{
    return [
        'product_category_id' => $category->id,
        'code' => 'CEM-50KG',
        'name' => 'Portland Cement 50 kg',
        'description' => null,
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.settings.products.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(productActor(Company::factory()->create()))
        ->get(route('projects.settings.products.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    if ($trashed) {
        $product->delete();
    }

    $this->actingAs(productActor($company, 'projects.settings.products.view'))
        ->{$method}(route($route, $product), productPayload($product->category))
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.settings.products.update', false],
    'delete' => ['delete', 'projects.settings.products.destroy', false],
    'restore' => ['put', 'projects.settings.products.restore', true],
    'force delete' => ['delete', 'projects.settings.products.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $company = Company::factory()->create();

    $this->actingAs(productActor($company, 'projects.settings.products.view'))
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($company)->create()))
        ->assertForbidden();
});

test('the project-manager role holds every product permission and the viewer only view', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create()->assignRole('project-manager');
    $viewer = User::factory()->create()->assignRole('viewer');

    foreach (['view', 'create', 'update', 'delete', 'restore', 'force-delete'] as $action) {
        expect($manager->can("projects.settings.products.{$action}"))->toBeTrue()
            ->and($viewer->can("projects.settings.products.{$action}"))->toBe($action === 'view');
    }
});

/*
| Listing and scoping
*/

test('the list shows only the selected company\'s products and categories', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $category = ProductCategory::factory()->for($mine)->create(['name' => 'Raw Materials']);
    Product::factory()->for($mine)->for($category, 'category')->create(['code' => 'MINE']);
    Product::factory()->for($theirs)->create(['code' => 'THEIRS']);

    $this->actingAs(productActor($mine, 'projects.settings.products.view'))
        ->get(route('projects.settings.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/settings/products/index')
            ->where('company.id', $mine->id)
            ->has('products.data', 1)
            ->where('products.data.0.code', 'MINE')
            ->where('products.data.0.category_name', 'Raw Materials')
            ->has('categories', 1)
            ->where('categories.0.value', $category->id)
            ->where('counts', ['active' => 1, 'trashed' => 0]),
        );
});

test('the list filters by category and sorts by name', function () {
    $company = Company::factory()->create();
    [$raw, $elec] = ProductCategory::factory()->for($company)->count(2)->create();
    Product::factory()->for($company)->for($raw, 'category')->create(['name' => 'Zinc sheet']);
    Product::factory()->for($company)->for($raw, 'category')->create(['name' => 'Cement']);
    Product::factory()->for($company)->for($elec, 'category')->create(['name' => 'Cable']);

    $this->actingAs(productActor($company, 'projects.settings.products.view'))
        ->get(route('projects.settings.products.index', ['filter' => ['product_category_id' => $raw->id], 'sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('products.data.0.name', 'Cement')
            ->where('products.data.1.name', 'Zinc sheet'),
        );
});

test('the deleted tab lists only deleted products', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['code' => 'LIVE']);
    Product::factory()->for($company)->create(['code' => 'GONE'])->delete();

    $this->actingAs(productActor($company, 'projects.settings.products.view'))
        ->get(route('projects.settings.products.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.code', 'GONE')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('another company\'s product is a 404', function (string $method, string $route) {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = Product::factory()->for($theirs)->create();

    $this->actingAs(productActor($mine, 'projects.settings.products.update', 'projects.settings.products.delete'))
        ->{$method}(route($route, $foreign), productPayload(ProductCategory::factory()->for($mine)->create()))
        ->assertNotFound();
})->with([
    'update' => ['put', 'projects.settings.products.update'],
    'delete' => ['delete', 'projects.settings.products.destroy'],
]);

test('with no company selected the page says so and lists nothing', function () {
    Product::factory()->create();
    $user = userWithPermissions('projects.settings.products.view');
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('projects.settings.products.index'))
        ->assertInertia(fn ($page) => $page
            ->where('company', null)
            ->has('products.data', 0)
            ->where('categories', []),
        );
});

test('a product lands in the selected company whatever company_id is posted', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $this->actingAs(productActor($mine, 'projects.settings.products.create'));

    assertToast($this->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($mine)->create(), ['company_id' => $theirs->id])), 'success');

    expect(Product::withoutGlobalScopes()->sole()->company_id)->toBe($mine->id);
});

test('creating without a selected company is refused, no category being choosable', function () {
    $user = userWithPermissions('projects.settings.products.create');

    $this->actingAs($user)
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->create()))
        ->assertSessionHasErrors(['product_category_id' => 'Choose an active product category.']);

    expect(Product::withoutGlobalScopes()->count())->toBe(0);
});

/*
| Validation, writes and duplicates
*/

test('a product is created with its values tidied and an empty description saved as null', function () {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create();

    $this->actingAs(productActor($company, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload($category, [
            'code' => ' cem-50kg ',
            'name' => ' Portland   Cement 50 kg ',
            'description' => '',
        ]))
        ->assertSessionHasNoErrors();

    $product = Product::withoutGlobalScopes()->sole();

    expect($product->code)->toBe('CEM-50KG')
        ->and($product->name)->toBe('Portland Cement 50 kg')
        ->and($product->product_category_id)->toBe($category->id)
        ->and($product->description)->toBeNull();
});

test('a malformed field is refused', function (string $field, mixed $value) {
    $company = Company::factory()->create();

    $this->actingAs(productActor($company, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($company)->create(), [$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'category missing' => ['product_category_id', ''],
    'code missing' => ['code', ''],
    'name missing' => ['name', ''],
    'code with a space' => ['code', 'CE M'],
    'name too long' => ['name', str_repeat('n', 151)],
    'status out of range' => ['status', 'X'],
]);

test('a missing category is reported as the category field', function () {
    $company = Company::factory()->create();

    $this->actingAs(productActor($company, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($company)->create(), ['product_category_id' => '']))
        ->assertSessionHasErrors(['product_category_id' => 'The category field is required.']);
});

test('a category that is inactive, deleted or another company\'s cannot be chosen', function (string $case) {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $category = match ($case) {
        'inactive' => ProductCategory::factory()->for($mine)->inactive()->create(),
        'deleted' => tap(ProductCategory::factory()->for($mine)->create())->delete(),
        'foreign' => ProductCategory::factory()->for($theirs)->create(),
    };

    $this->actingAs(productActor($mine, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload($category))
        ->assertSessionHasErrors(['product_category_id' => 'Choose an active product category.']);
})->with(['inactive', 'deleted', 'foreign']);

test('a product keeps a category that was deactivated after it was chosen', function () {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create();
    $product = Product::factory()->for($company)->for($category, 'category')->create(['code' => 'CEM-50KG', 'name' => 'Portland Cement 50 kg']);
    $category->update(['status' => RecordStatus::Inactive]);

    $this->actingAs(productActor($company, 'projects.settings.products.update'))
        ->put(route('projects.settings.products.update', $product), productPayload($category, ['description' => 'Bagged.']))
        ->assertSessionHasNoErrors();

    expect($product->refresh()->description)->toBe('Bagged.');
});

test('a code or name already used in the company is refused whatever its case', function (string $field, string $value) {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['code' => 'CEM-50KG', 'name' => 'Portland Cement 50 kg']);

    $this->actingAs(productActor($company, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($company)->create(), ['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'cem-50kg'],
    'name, other case' => ['name', 'PORTLAND CEMENT 50 KG'],
]);

test('the same code and name are allowed in another company', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Product::factory()->for($theirs)->create(['code' => 'CEM-50KG', 'name' => 'Portland Cement 50 kg']);

    $this->actingAs(productActor($mine, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($mine)->create()))
        ->assertSessionHasNoErrors();

    expect(Product::withoutGlobalScopes()->where('code', 'CEM-50KG')->count())->toBe(2);
});

test('a deleted product keeps its code and name reserved and says to restore it', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['code' => 'CEM-50KG', 'name' => 'Portland Cement 50 kg'])->delete();

    $this->actingAs(productActor($company, 'projects.settings.products.create'))
        ->post(route('projects.settings.products.store'), productPayload(ProductCategory::factory()->for($company)->create()))
        ->assertSessionHasErrors([
            'code' => 'The code "CEM-50KG" belongs to a deleted product in this company. Restore it from the Deleted tab instead.',
            'name' => 'A deleted product in this company is named "Portland Cement 50 kg". Restore it from the Deleted tab instead.',
        ]);
});

test('a product can be edited onto another category, keeping its own code and name', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create(['code' => 'CEM-50KG', 'name' => 'Portland Cement 50 kg']);
    $other = ProductCategory::factory()->for($company)->create();

    $this->actingAs(productActor($company, 'projects.settings.products.update'))
        ->put(route('projects.settings.products.update', $product), productPayload($other, ['status' => RecordStatus::Inactive->value]))
        ->assertSessionHasNoErrors();

    expect($product->refresh()->product_category_id)->toBe($other->id)
        ->and($product->status)->toBe(RecordStatus::Inactive);
});

test('a product is soft deleted, restored and permanently deleted', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $this->actingAs(productActor($company, 'projects.settings.products.delete', 'projects.settings.products.restore', 'projects.settings.products.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.settings.products.destroy', $product)), 'success');
    expect($product->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.settings.products.restore', $product)), 'success');
    expect($product->refresh()->trashed())->toBeFalse();

    $product->delete();
    assertToast($this->delete(route('projects.settings.products.force-delete', $product)), 'success');

    expect(Product::withoutGlobalScopes()->withTrashed()->find($product->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'product')->exists())->toBeTrue();
});

test('creating a product is audited with its company', function () {
    $company = Company::factory()->create();
    $category = ProductCategory::factory()->for($company)->create();

    $this->actingAs(productActor($company, 'projects.settings.products.create'));
    config(['audit.console' => true]);

    $this->post(route('projects.settings.products.store'), productPayload($category));

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'product')->sole();

    expect($audit->new_values['code'])->toBe('CEM-50KG')
        ->and((int) $audit->new_values['company_id'])->toBe($company->id);
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $this->actingAs(productActor($company, 'projects.settings.products.view'))
        ->delete(route($route), ['ids' => [$product->id]])
        ->assertForbidden();
})->with(['projects.settings.products.bulk-destroy', 'projects.settings.products.bulk-force-delete']);

test('bulk delete moves the ticked products to Deleted, never another company\'s', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    [$first, $second] = Product::factory()->for($mine)->count(2)->create();
    $foreign = Product::factory()->for($theirs)->create();

    $this->actingAs(productActor($mine, 'projects.settings.products.delete'));

    $response = $this->delete(route('projects.settings.products.bulk-destroy'), ['ids' => [$first->id, $second->id, $foreign->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 products moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted products and records each', function () {
    $company = Company::factory()->create();
    [$first, $second] = Product::factory()->for($company)->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(productActor($company, 'projects.settings.products.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.settings.products.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Product::withoutGlobalScopes()->withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'product')->count())->toBe(2);
});
