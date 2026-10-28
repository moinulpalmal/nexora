<?php

use App\Enums\Imports\ImportStatus;
use App\Models\Admin\AuditLog;
use App\Models\SheetImport;
use App\Models\User;
use App\Services\Imports\ImportRunner;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Fixtures\Imports\ImportFixtures;
use Tests\Fixtures\Imports\Widget;

/*
|--------------------------------------------------------------------------
| The import lifecycle — stage, map, confirm, cancel
|--------------------------------------------------------------------------
|
| Proved against the fixture `tests.widgets` importer (a global code/name
| table, unique on the code), because no shipped master table imports yet. A
| real importer's own test covers its permission, its fields and the
| equivalence of an imported value with the same value typed into the form;
| the engine's behaviour is proved once, here (ARCHITECTURE.md §9.13).
|
*/

beforeEach(function () {
    ImportFixtures::install();
});

function widgetImporter(): User
{
    return userWithPermissions('tests.widgets.import');
}

/**
 * Stage a workbook as the uploader and return the pending import.
 *
 * @param  array<string, list<list<string|int|null>>>  $tabs
 */
function stageWidgets(User $uploader, array $tabs = ['Widgets' => [['Code', 'Name'], ['w-001', 'First'], ['w-002', 'Second']]]): SheetImport
{
    test()->actingAs($uploader)->post(route('tests.widgets.import.store'), ['file' => uploadedWorkbook($tabs, 'widgets.xlsx')]);

    return SheetImport::query()->pending()->where('inserted_by', $uploader->id)->latest('id')->firstOrFail();
}

test('guests are redirected to the login page', function () {
    $this->post(route('tests.widgets.import.store'))->assertRedirect(route('login'));
});

test('every import endpoint is denied without the import permission', function () {
    $sheetImport = stageWidgets(widgetImporter());

    $this->actingAs(User::factory()->create());

    $this->post(route('tests.widgets.import.store'), ['file' => uploadedWorkbook(['W' => [['Code'], ['x']]])])->assertForbidden();
    $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip'])->assertForbidden();
    $this->delete(route('tests.widgets.import.cancel', $sheetImport))->assertForbidden();
    $this->get(route('tests.widgets.import.template'))->assertForbidden();
});

test('only the uploader may confirm their own import', function () {
    $sheetImport = stageWidgets(widgetImporter());

    $this->actingAs(widgetImporter())
        ->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip'])
        ->assertNotFound();
});

test('an import staged against another blueprint cannot be confirmed or cancelled here', function () {
    $user = widgetImporter();
    $sheetImport = SheetImport::factory()->create(['import_key' => 'tests.gadgets', 'inserted_by' => $user->id]);

    $this->actingAs($user);

    $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip'])->assertNotFound();
    $this->delete(route('tests.widgets.import.cancel', $sheetImport))->assertNotFound();
});

test('a clean workbook stages with a suggested mapping, no company and nothing written', function () {
    $user = widgetImporter();

    $this->actingAs($user)
        ->post(route('tests.widgets.import.store'), ['file' => uploadedWorkbook(['Widgets' => [['Code', 'Name'], ['w-001', 'First']]])])
        ->assertRedirect(route('tests.widgets.index'));

    $sheetImport = SheetImport::query()->sole();
    $pending = app(ImportRunner::class)->pendingFor($user, 'tests.widgets');

    expect($sheetImport->status)->toBe(ImportStatus::Pending)
        ->and($sheetImport->company_id)->toBeNull()
        ->and($sheetImport->row_count)->toBe(1)
        ->and($sheetImport->payload['sheets'][0]['suggested_map'])->toBe(['code' => 0, 'name' => 1])
        ->and($pending['id'])->toBe($sheetImport->id)
        ->and(app(ImportRunner::class)->pendingFor(widgetImporter(), 'tests.widgets'))->toBeNull()
        ->and(Widget::query()->count())->toBe(0);
});

test('an unreadable workbook is refused with an error toast and nothing is staged', function () {
    $response = $this->actingAs(widgetImporter())
        ->post(route('tests.widgets.import.store'), ['file' => uploadedWorkbook(['Widgets' => [[], ['w-001']]])]);

    assertToast($response, 'error');
    expect(SheetImport::query()->count())->toBe(0);
});

test('confirming writes every row, normalised, active and stamped with the uploader', function () {
    $user = widgetImporter();
    $sheetImport = stageWidgets($user, ['Widgets' => [['Code', 'Name'], [' w-001 ', ' First '], ['w-002', 'Second']]]);

    $response = $this->post(route('tests.widgets.import.confirm', $sheetImport), [
        'map' => [0 => ['code' => 0, 'name' => 1]],
        'on_duplicate' => 'skip',
    ]);

    assertToast($response, 'success')->assertRedirect(route('tests.widgets.index'));

    $first = Widget::query()->where('code', 'W-001')->sole();

    expect(Widget::query()->count())->toBe(2)
        ->and($first->name)->toBe('First')
        ->and($first->isActive())->toBeTrue()
        ->and($first->inserted_by)->toBe($user->id)
        ->and($sheetImport->fresh()->status)->toBe(ImportStatus::Confirmed)
        ->and($sheetImport->fresh()->created_count)->toBe(2);
});

test('one bad row refuses the whole workbook, writes nothing and leaves it pending', function () {
    $sheetImport = stageWidgets(widgetImporter(), ['Widgets' => [['Code', 'Name'], ['w-001', 'First'], ['w-002', null]]]);

    $response = $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip']);

    assertToast($response, 'error');
    expect(session('inertia.flash_data.toast.message'))->toContain('"Widgets" row 3 (Name is required.)')
        ->and(Widget::query()->count())->toBe(0)
        ->and($sheetImport->fresh()->status)->toBe(ImportStatus::Pending);
});

test('a value repeated on two tabs is a collision that names both places', function () {
    $sheetImport = stageWidgets(widgetImporter(), [
        'Plant A' => [['Code', 'Name'], ['w-001', 'First']],
        'Plant B' => [['Name', 'Code'], ['Again', 'W-001']],
    ]);

    $response = $this->post(route('tests.widgets.import.confirm', $sheetImport), [
        'map' => [0 => ['code' => 0, 'name' => 1], 1 => ['code' => 1, 'name' => 0]],
        'on_duplicate' => 'skip',
    ]);

    assertToast($response, 'error');
    expect(session('inertia.flash_data.toast.message'))->toContain('"Plant B" row 2 (duplicates "Plant A" row 2)')
        ->and(Widget::query()->count())->toBe(0);
});

test('each tab is mapped on its own and a deselected tab is not imported', function () {
    $sheetImport = stageWidgets(widgetImporter(), [
        'Plant A' => [['Code', 'Name'], ['w-001', 'First']],
        'Plant B' => [['Name', 'Code'], ['Second', 'w-002']],
        'Plant C' => [['Code', 'Name'], ['w-003', 'Third']],
    ]);

    $this->post(route('tests.widgets.import.confirm', $sheetImport), [
        'map' => [0 => ['code' => 0, 'name' => 1], 1 => ['code' => 1, 'name' => 0]],
        'on_duplicate' => 'skip',
    ]);

    expect(Widget::query()->orderBy('code')->pluck('name', 'code')->all())->toBe(['W-001' => 'First', 'W-002' => 'Second']);
});

test('an existing row is skipped or updated as the uploader chose', function (string $onDuplicate, string $expectedName, string $counted) {
    $user = widgetImporter();
    Widget::query()->create(['code' => 'W-001', 'name' => 'Original']);
    $sheetImport = stageWidgets($user, ['Widgets' => [['Code', 'Name'], ['w-001', 'Renamed'], ['w-002', 'New']]]);

    $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => $onDuplicate]);

    expect(Widget::query()->where('code', 'W-001')->value('name'))->toBe($expectedName)
        ->and($sheetImport->fresh()->{$counted})->toBe(1)
        ->and($sheetImport->fresh()->created_count)->toBe(1);
})->with([
    'skip' => ['skip', 'Original', 'skipped_count'],
    'update' => ['update', 'Renamed', 'updated_count'],
]);

test('a required field left unmapped is refused', function () {
    $sheetImport = stageWidgets(widgetImporter());

    $response = $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0]], 'on_duplicate' => 'skip']);

    assertToast($response, 'error');
    expect(session('inertia.flash_data.toast.message'))->toContain('Name is required and was not mapped');
});

test('a mapping naming an unknown field, tab or column is a validation error', function (array $map) {
    $sheetImport = stageWidgets(widgetImporter());

    $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => $map, 'on_duplicate' => 'skip'])
        ->assertSessionHasErrors();

    expect(Widget::query()->count())->toBe(0);
})->with([
    'unknown field' => [[0 => ['code' => 0, 'name' => 1, 'colour' => 1]]],
    'unknown tab' => [[7 => ['code' => 0, 'name' => 1]]],
    'column past the last header' => [[0 => ['code' => 0, 'name' => 5]]],
]);

test('anyone with the import permission may cancel a pending import, but not a finished one', function () {
    $sheetImport = stageWidgets(widgetImporter());

    $response = $this->actingAs(widgetImporter())->delete(route('tests.widgets.import.cancel', $sheetImport));

    assertToast($response, 'success');
    expect($sheetImport->fresh()->status)->toBe(ImportStatus::Cancelled);

    $this->delete(route('tests.widgets.import.cancel', $sheetImport))->assertNotFound();
});

test('an import is audited as one decision, never row by row', function () {
    $user = widgetImporter();
    config(['audit.console' => true]);

    $sheetImport = stageWidgets($user);
    $this->post(route('tests.widgets.import.confirm', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip']);

    $audits = AuditLog::query()->where('auditable_type', 'sheet-import')->orderBy('id')->get();

    expect($audits->pluck('event')->all())->toBe(['created', 'updated'])
        ->and($audits[1]->new_values)->toMatchArray(['column_map' => '[{"code":0,"name":1}]', 'created_count' => 2])
        ->and($audits[0]->new_values)->not->toHaveKey('staged_rows')
        ->and(AuditLog::query()->where('auditable_type', '!=', 'sheet-import')->count())->toBe(0)
        ->and(Widget::query()->count())->toBe(2);
});

test('the template is a workbook headed with every importable field', function () {
    $response = $this->actingAs(widgetImporter())->get(route('tests.widgets.import.template'));

    $response->assertOk()->assertDownload('widgets-import-template.xlsx');

    $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

    expect($sheet->getTitle())->toBe('Widgets')
        ->and($sheet->rangeToArray('A1:B1')[0])->toBe(['Code', 'Name'])
        ->and($sheet->getHighestDataRow())->toBe(1);
});

test('an all-or-nothing importer has no review step', function () {
    $sheetImport = stageWidgets(widgetImporter());

    $this->postJson(route('tests.widgets.import.review', $sheetImport), ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip'])
        ->assertNotFound();
});
