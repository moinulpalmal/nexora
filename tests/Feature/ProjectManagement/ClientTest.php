<?php

use App\Enums\RecordStatus;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\ProjectManagement\Client;
use App\Models\User;
use Database\Seeders\Admin\RolePermissionSeeder;

/*
|--------------------------------------------------------------------------
| Projects → Clients
|--------------------------------------------------------------------------
|
| The ERP's one customer list, company-scoped as HRM → Departments
| (ARCHITECTURE.md §9.10): every read and write is the selected company's,
| `company_id` never comes from the browser, and code and name are unique per
| company — ignoring case, deleted clients included.
|
*/

/**
 * A user holding the permissions *and* working in the company — an actor
 * missing either fails every test for the wrong reason.
 */
function clientActor(Company $company, string ...$permissions): User
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
function clientPayload(array $overrides = []): array
{
    return [
        'code' => 'ACME',
        'name' => 'Acme Trading Ltd.',
        'address' => '12 Example Road, Dhaka',
        'description' => null,
        'contact_person' => 'Sadia Islam',
        'phone' => '+880 1711 223344',
        'email' => 'projects@acme.example.com',
        'status' => RecordStatus::Active->value,
        ...$overrides,
    ];
}

/*
| Access
*/

test('guests are sent to the login page', function () {
    $this->get(route('projects.clients.index'))->assertRedirect(route('login'));
});

test('the list is refused without the view permission', function () {
    $this->actingAs(clientActor(Company::factory()->create()))
        ->get(route('projects.clients.index'))
        ->assertForbidden();
});

test('each write is refused without its own permission', function (string $method, string $route, bool $trashed) {
    $company = Company::factory()->create();
    $client = Client::factory()->for($company)->create();

    if ($trashed) {
        $client->delete();
    }

    $this->actingAs(clientActor($company, 'projects.clients.view'))
        ->{$method}(route($route, $client), clientPayload())
        ->assertForbidden();
})->with([
    'update' => ['put', 'projects.clients.update', false],
    'delete' => ['delete', 'projects.clients.destroy', false],
    'restore' => ['put', 'projects.clients.restore', true],
    'force delete' => ['delete', 'projects.clients.force-delete', true],
]);

test('creating is refused without the create permission', function () {
    $this->actingAs(clientActor(Company::factory()->create(), 'projects.clients.view'))
        ->post(route('projects.clients.store'), clientPayload())
        ->assertForbidden();
});

test('the project-manager role holds every client permission and the viewer only view', function () {
    $this->seed(RolePermissionSeeder::class);
    $permissions = ['view', 'create', 'update', 'delete', 'restore', 'force-delete'];

    $manager = User::factory()->create()->assignRole('project-manager');
    $viewer = User::factory()->create()->assignRole('viewer');

    foreach ($permissions as $action) {
        expect($manager->can("projects.clients.{$action}"))->toBeTrue()
            ->and($viewer->can("projects.clients.{$action}"))->toBe($action === 'view');
    }
});

/*
| Listing and scoping
*/

test('the list shows only the selected company\'s clients', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Client::factory()->for($mine)->create(['code' => 'MINE']);
    Client::factory()->for($theirs)->create(['code' => 'THEIRS']);

    $this->actingAs(clientActor($mine, 'projects.clients.view'))
        ->get(route('projects.clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/clients/index')
            ->where('company.id', $mine->id)
            ->has('clients.data', 1)
            ->where('clients.data.0.code', 'MINE')
            ->where('counts', ['active' => 1, 'trashed' => 0]),
        );
});

test('the list filters on the contact columns', function (string $column, string $value) {
    $company = Company::factory()->create();
    Client::factory()->for($company)->create(['code' => 'HIT', 'contact_person' => 'Sadia Islam', 'phone' => '+880 1711 223344', 'email' => 'sadia@hit.example.com']);
    Client::factory()->for($company)->create(['code' => 'MISS', 'contact_person' => 'Lena Vogel', 'phone' => '+49 30 1234567', 'email' => 'lena@miss.example.com']);

    $this->actingAs(clientActor($company, 'projects.clients.view'))
        ->get(route('projects.clients.index', ['filter' => [$column => $value]]))
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.code', 'HIT'),
        );
})->with([
    'contact person, mid-word' => ['contact_person', 'adia'],
    'phone, from its start' => ['phone', '+880'],
    'email, mid-word' => ['email', '@hit.'],
]);

test('the deleted tab lists only deleted clients', function () {
    $company = Company::factory()->create();
    Client::factory()->for($company)->create(['code' => 'LIVE']);
    Client::factory()->for($company)->create(['code' => 'GONE'])->delete();

    $this->actingAs(clientActor($company, 'projects.clients.view'))
        ->get(route('projects.clients.index', ['view' => 'trashed']))
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.code', 'GONE')
            ->where('counts', ['active' => 1, 'trashed' => 1]),
        );
});

test('another company\'s client is a 404', function (string $method, string $route) {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    $foreign = Client::factory()->for($theirs)->create();

    $this->actingAs(clientActor($mine, 'projects.clients.update', 'projects.clients.delete'))
        ->{$method}(route($route, $foreign), clientPayload())
        ->assertNotFound();
})->with([
    'update' => ['put', 'projects.clients.update'],
    'delete' => ['delete', 'projects.clients.destroy'],
]);

test('with no company selected the page says so and lists nothing', function () {
    Client::factory()->create();
    $user = userWithPermissions('projects.clients.view');
    $user->companies()->attach(Company::factory()->count(2)->create());

    $this->actingAs($user)
        ->get(route('projects.clients.index'))
        ->assertInertia(fn ($page) => $page
            ->where('company', null)
            ->has('clients.data', 0),
        );
});

test('a client lands in the selected company whatever company_id is posted', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();

    $this->actingAs(clientActor($mine, 'projects.clients.create'));

    assertToast($this->post(route('projects.clients.store'), clientPayload(['company_id' => $theirs->id])), 'success');

    expect(Client::withoutGlobalScopes()->sole()->company_id)->toBe($mine->id);
});

test('creating without a selected company is refused', function () {
    $user = userWithPermissions('projects.clients.create');

    assertToast($this->actingAs($user)->post(route('projects.clients.store'), clientPayload()), 'warning');

    expect(Client::withoutGlobalScopes()->count())->toBe(0);
});

/*
| Validation, writes and duplicates
*/

test('a client is created with its values tidied and empty optional fields saved as null', function () {
    $company = Company::factory()->create();

    $this->actingAs(clientActor($company, 'projects.clients.create'))
        ->post(route('projects.clients.store'), clientPayload([
            'code' => ' acme-01 ',
            'name' => ' Acme   Trading ',
            'contact_person' => ' Sadia   Islam ',
            'email' => ' Projects@ACME.example.com ',
            'address' => '',
            'phone' => '',
        ]))
        ->assertSessionHasNoErrors();

    $client = Client::withoutGlobalScopes()->sole();

    expect($client->code)->toBe('ACME-01')
        ->and($client->name)->toBe('Acme Trading')
        ->and($client->contact_person)->toBe('Sadia Islam')
        ->and($client->email)->toBe('projects@acme.example.com')
        ->and($client->address)->toBeNull()
        ->and($client->phone)->toBeNull()
        ->and($client->description)->toBeNull();
});

test('a malformed field is refused', function (string $field, string $value) {
    $this->actingAs(clientActor(Company::factory()->create(), 'projects.clients.create'))
        ->post(route('projects.clients.store'), clientPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code missing' => ['code', ''],
    'name missing' => ['name', ''],
    'code with a space' => ['code', 'AC ME'],
    'phone with a letter' => ['phone', '01711-ABC'],
    'email without a domain' => ['email', 'projects@'],
    'status out of range' => ['status', 'X'],
]);

test('a code or name already used in the company is refused whatever its case', function (string $field, string $value) {
    $company = Company::factory()->create();
    Client::factory()->for($company)->create(['code' => 'ACME', 'name' => 'Acme Trading Ltd.']);

    $this->actingAs(clientActor($company, 'projects.clients.create'))
        ->post(route('projects.clients.store'), clientPayload(['code' => 'OTHER', 'name' => 'Other', $field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'code, lower case' => ['code', 'acme'],
    'name, other case' => ['name', 'ACME TRADING LTD.'],
]);

test('the same code and name are allowed in another company, and an email may repeat', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    Client::factory()->for($theirs)->create(['code' => 'ACME', 'name' => 'Acme Trading Ltd.']);
    Client::factory()->for($mine)->create(['code' => 'SISTER', 'email' => 'projects@acme.example.com']);

    $this->actingAs(clientActor($mine, 'projects.clients.create'))
        ->post(route('projects.clients.store'), clientPayload())
        ->assertSessionHasNoErrors();

    expect(Client::withoutGlobalScopes()->where('code', 'ACME')->count())->toBe(2);
});

test('a deleted client keeps its code and name reserved and says to restore it', function () {
    $company = Company::factory()->create();
    Client::factory()->for($company)->create(['code' => 'ACME', 'name' => 'Acme Trading Ltd.'])->delete();

    $this->actingAs(clientActor($company, 'projects.clients.create'))
        ->post(route('projects.clients.store'), clientPayload())
        ->assertSessionHasErrors([
            'code' => 'The code "ACME" belongs to a deleted client in this company. Restore it from the Deleted tab instead.',
            'name' => 'A deleted client in this company is named "Acme Trading Ltd.". Restore it from the Deleted tab instead.',
        ]);
});

test('a client can be edited, keeping its own code and name', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->for($company)->create(['code' => 'ACME', 'name' => 'Acme Trading Ltd.']);

    $this->actingAs(clientActor($company, 'projects.clients.update'))
        ->put(route('projects.clients.update', $client), clientPayload(['status' => RecordStatus::Inactive->value, 'description' => 'Key account.']))
        ->assertSessionHasNoErrors();

    expect($client->refresh()->status)->toBe(RecordStatus::Inactive)
        ->and($client->description)->toBe('Key account.');
});

test('a client is soft deleted, restored and permanently deleted', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->for($company)->create();

    $this->actingAs(clientActor($company, 'projects.clients.delete', 'projects.clients.restore', 'projects.clients.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.clients.destroy', $client)), 'success');
    expect($client->refresh()->trashed())->toBeTrue();

    assertToast($this->put(route('projects.clients.restore', $client)), 'success');
    expect($client->refresh()->trashed())->toBeFalse();

    $client->delete();
    assertToast($this->delete(route('projects.clients.force-delete', $client)), 'success');

    expect(Client::withoutGlobalScopes()->withTrashed()->find($client->id))->toBeNull()
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'client')->exists())->toBeTrue();
});

test('creating a client is audited with its company', function () {
    $company = Company::factory()->create();

    $this->actingAs(clientActor($company, 'projects.clients.create'));
    config(['audit.console' => true]);

    $this->post(route('projects.clients.store'), clientPayload());

    $audit = AuditLog::where('event', 'created')->where('auditable_type', 'client')->sole();

    expect($audit->new_values['code'])->toBe('ACME')
        ->and((int) $audit->new_values['company_id'])->toBe($company->id);
});

/*
| Bulk delete
*/

test('bulk delete and bulk permanent delete each need their own permission', function (string $route) {
    $company = Company::factory()->create();
    $client = Client::factory()->for($company)->create();

    $this->actingAs(clientActor($company, 'projects.clients.view'))
        ->delete(route($route), ['ids' => [$client->id]])
        ->assertForbidden();
})->with(['projects.clients.bulk-destroy', 'projects.clients.bulk-force-delete']);

test('bulk delete moves the ticked clients to Deleted, never another company\'s', function () {
    [$mine, $theirs] = Company::factory()->count(2)->create();
    [$first, $second] = Client::factory()->for($mine)->count(2)->create();
    $foreign = Client::factory()->for($theirs)->create();

    $this->actingAs(clientActor($mine, 'projects.clients.delete'));

    $response = $this->delete(route('projects.clients.bulk-destroy'), ['ids' => [$first->id, $second->id, $foreign->id]]);

    assertToast($response, 'success')->assertSessionHas('inertia.flash_data.toast.message', '2 clients moved to Deleted.');

    expect($first->refresh()->trashed())->toBeTrue()
        ->and($second->refresh()->trashed())->toBeTrue()
        ->and($foreign->refresh()->trashed())->toBeFalse();
});

test('bulk permanent delete removes the ticked deleted clients and records each', function () {
    $company = Company::factory()->create();
    [$first, $second] = Client::factory()->for($company)->count(2)->create();
    $first->delete();
    $second->delete();

    $this->actingAs(clientActor($company, 'projects.clients.force-delete'));
    config(['audit.console' => true]);

    assertToast($this->delete(route('projects.clients.bulk-force-delete'), ['ids' => [$first->id, $second->id]]), 'success');

    expect(Client::withoutGlobalScopes()->withTrashed()->count())->toBe(0)
        ->and(AuditLog::where('event', 'force-deleted')->where('auditable_type', 'client')->count())->toBe(2);
});
