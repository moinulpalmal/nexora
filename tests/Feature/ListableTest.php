<?php

use App\Enums\FilterType;
use App\Http\Requests\ListRequest;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| The list engine (ARCHITECTURE.md §8.6)
|--------------------------------------------------------------------------
|
| `Concerns\Listable` (model half) and `Http\Requests\ListRequest` (request
| half), proved on `User`. Each list screen's own wiring is proved by its
| feature test; this pins the shared behaviour once.
|
*/

/**
 * A concrete ListRequest over the users list's allow-lists, filled from a query.
 *
 * @param  array<string, mixed>  $query
 */
function usersListRequest(array $query = []): ListRequest
{
    $request = new class extends ListRequest
    {
        protected function sortable(): array
        {
            return User::SORTABLE;
        }

        protected function filterable(): array
        {
            return User::FILTERABLE;
        }
    };

    return $request::createFrom(Request::create('/', 'GET', $query));
}

/**
 * @param  array<string, mixed>  $query
 */
function listRequestFails(array $query): bool
{
    $request = usersListRequest($query);

    return Validator::make($request->all(), $request->rules())->fails();
}

test('contains matches mid-string and prefix only from the start', function () {
    User::factory()->create(['name' => 'Ada Lovelace', 'username' => 'ada01']);
    User::factory()->create(['name' => 'Grace Hopper', 'username' => 'xada']);

    expect(User::filterColumns(['name' => 'love'])->pluck('username')->all())->toBe(['ada01'])
        ->and(User::filterColumns(['username' => 'ada'])->pluck('username')->all())->toBe(['ada01']);
});

test('blank filter cells are no filter at all', function () {
    User::factory()->count(3)->create();

    expect(User::filterColumns(['name' => '   ', 'status' => ''])->count())->toBe(3);
});

test('LIKE wildcards typed into a cell are matched literally', function () {
    User::factory()->create(['name' => 'Plain']);
    User::factory()->create(['name' => '100% Sure']);

    expect(User::filterColumns(['name' => '%'])->pluck('name')->all())->toBe(['100% Sure'])
        ->and(User::filterColumns(['name' => '_'])->count())->toBe(0);
});

test('the escape character itself is matched literally too', function () {
    User::factory()->create(['name' => 'Hey!']);
    User::factory()->create(['name' => 'Hey']);

    expect(User::filterColumns(['name' => 'y!'])->pluck('name')->all())->toBe(['Hey!']);
});

test('the permission module filter matches the name prefix literally', function () {
    Permission::findOrCreate('admin.users.view', 'web');
    Permission::findOrCreate('adminx.users.view', 'web');

    expect(Permission::filterColumns(['module' => 'admin'])->pluck('name')->all())->toBe(['admin.users.view'])
        ->and(Permission::filterColumns(['module' => 'admin_'])->count())->toBe(0);
});

test('a key outside FILTERABLE never reaches the query', function () {
    User::factory()->count(2)->create();

    expect(User::filterColumns(['password' => 'x'])->count())->toBe(2);
});

test('a scope filter hands the value to the model scope', function () {
    Role::findOrCreate('auditor', 'web');
    $auditor = User::factory()->create();
    $auditor->assignRole('auditor');
    User::factory()->create();

    expect(User::filterColumns(['role' => 'auditor'])->pluck('id')->all())->toBe([$auditor->id]);
});

test('sorting is allow-listed and falls back to the default column', function () {
    User::factory()->create(['name' => 'Bea', 'username' => 'zz']);
    User::factory()->create(['name' => 'Al', 'username' => 'aa']);

    expect(User::sortBy('username', 'desc')->pluck('username')->all())->toBe(['zz', 'aa'])
        ->and(User::sortBy('password', 'asc')->pluck('name')->all())->toBe(['Al', 'Bea']);
});

test('whereAnyOf matches any ticked value and blanks via the sentinel', function () {
    $withCode = User::factory()->create(['employee_code' => 'E-1']);
    $withoutCode = User::factory()->create(['employee_code' => null]);
    User::factory()->create(['employee_code' => 'E-2']);

    $ids = User::query()
        ->where(fn ($query) => User::whereAnyOf($query, 'employee_code', ['E-1', FilterType::BLANK]))
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect($ids)->toBe([$withCode->id, $withoutCode->id]);
});

test('the request fills every default and every filter cell', function () {
    $filters = usersListRequest()->filters();

    expect($filters['sort'])->toBe('name')
        ->and($filters['direction'])->toBe('asc')
        ->and($filters['per_page'])->toBe(ListRequest::DEFAULT_PER_PAGE)
        ->and(array_keys($filters['filter']))->toBe(array_keys(User::FILTERABLE))
        ->and(array_unique(array_values($filters['filter'])))->toBe(['']);
});

test('the request trims what it is given', function () {
    $filters = usersListRequest(['filter' => ['name' => '  ada  '], 'sort' => 'email', 'direction' => 'desc', 'per_page' => 50])->filters();

    expect($filters)->toMatchArray(['sort' => 'email', 'direction' => 'desc', 'per_page' => 50])
        ->and($filters['filter']['name'])->toBe('ada');
});

test('the request refuses what the allow-lists do not name', function (array $query) {
    expect(listRequestFails($query))->toBeTrue();
})->with([
    'unknown sort column' => [['sort' => 'password']],
    'unknown direction' => [['direction' => 'sideways']],
    'page size off the list' => [['per_page' => 999999]],
    'unknown filter column' => [['filter' => ['password' => 'x']]],
    'over-long filter value' => [['filter' => ['name' => str_repeat('a', 101)]]],
]);

test('the request accepts a well-formed query', function () {
    expect(listRequestFails(['sort' => 'name', 'direction' => 'desc', 'per_page' => 25, 'filter' => ['name' => 'ada'], 'page' => 2]))
        ->toBeFalse();
});
