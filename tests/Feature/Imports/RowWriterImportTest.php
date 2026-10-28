<?php

use App\Enums\Imports\ImportStatus;
use App\Models\SheetImport;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Fixtures\Imports\GadgetImportBlueprint;
use Tests\Fixtures\Imports\ImportFixtures;
use Tests\Fixtures\Imports\Widget;

/*
|--------------------------------------------------------------------------
| Partial imports — the ImportRowWriter path
|--------------------------------------------------------------------------
|
| The one exception to all-or-nothing (ARCHITECTURE.md §9.13): a row writer
| reviews before importing, imports the rows it can, and reports the rest with
| a downloadable workbook of the rejects. Proved against the fixture
| `tests.gadgets` blueprint, which refuses any row whose name says REJECT.
|
*/

beforeEach(function () {
    ImportFixtures::install();
    GadgetImportBlueprint::$lastOptions = null;
});

function stageGadgets(User $uploader): SheetImport
{
    test()->actingAs($uploader)->post(route('tests.gadgets.import.store'), [
        'file' => uploadedWorkbook(['Gadgets' => [
            ['Code', 'Name'],
            ['g-001', 'First'],
            ['g-002', null],
            ['g-003', 'REJECT me'],
            ['g-004', 'Fourth'],
        ]], 'gadgets.xlsx'),
    ]);

    return SheetImport::query()->pending()->where('inserted_by', $uploader->id)->latest('id')->firstOrFail();
}

/** @return array{map: array<int, array<string, int>>, on_duplicate: string} */
function gadgetMapping(): array
{
    return ['map' => [0 => ['code' => 0, 'name' => 1]], 'on_duplicate' => 'skip'];
}

test('review reports what Import would do and writes nothing', function () {
    $sheetImport = stageGadgets(userWithPermissions('tests.gadgets.import'));

    $this->postJson(route('tests.gadgets.import.review', $sheetImport), gadgetMapping())
        ->assertOk()
        ->assertJson([
            'created' => 2,
            'updated' => 0,
            'skipped' => 0,
            'rejected' => 2,
            'rejects' => [
                ['sheet' => 'Gadgets', 'line' => 3, 'message' => 'Name is required.'],
                ['sheet' => 'Gadgets', 'line' => 4, 'message' => 'The name is refused.'],
            ],
            'notes' => ['Will write gadget G-001.', 'Will write gadget G-004.'],
        ]);

    expect(Widget::query()->count())->toBe(0)
        ->and($sheetImport->fresh()->status)->toBe(ImportStatus::Pending);
});

test("confirming requires the writer's own inputs, but reviewing does not", function () {
    $sheetImport = stageGadgets(userWithPermissions('tests.gadgets.import'));

    $this->post(route('tests.gadgets.import.confirm', $sheetImport), gadgetMapping())
        ->assertSessionHasErrors('remark');

    expect(Widget::query()->count())->toBe(0);
});

test('confirming imports the good rows, rejects the rest and says so with a warning', function () {
    $sheetImport = stageGadgets(userWithPermissions('tests.gadgets.import'));

    $response = $this->post(route('tests.gadgets.import.confirm', $sheetImport), [...gadgetMapping(), 'remark' => 'October list']);

    assertToast($response, 'warning')
        ->assertSessionHas('inertia.flash_data.importResult.rejected', 2);

    $sheetImport->refresh();

    expect(Widget::query()->orderBy('code')->pluck('code')->all())->toBe(['G-001', 'G-004'])
        ->and(GadgetImportBlueprint::$lastOptions)->toBe(['remark' => 'October list'])
        ->and($sheetImport->status)->toBe(ImportStatus::Confirmed)
        ->and($sheetImport->rejected_count)->toBe(2)
        ->and($sheetImport->rejected_rows[1])->toBe(['sheet' => 'Gadgets', 'line' => 4, 'message' => 'The name is refused.']);
});

test('the rejected rows download as a workbook of the original cells and the reason', function () {
    $sheetImport = stageGadgets(userWithPermissions('tests.gadgets.import'));
    $this->post(route('tests.gadgets.import.confirm', $sheetImport), [...gadgetMapping(), 'remark' => 'October list']);

    $response = $this->get(route('tests.gadgets.import.rejects', $sheetImport));

    $response->assertOk()->assertDownload('gadgets - rejected rows.xlsx');

    $rows = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray(formatData: false);

    expect($rows)->toBe([
        ['Code', 'Name', 'Reason', 'Source row'],
        ['g-002', null, 'Name is required.', 3],
        ['g-003', 'REJECT me', 'The name is refused.', 4],
    ]);
});

test('there is no rejects download for an import that is still pending', function () {
    $sheetImport = stageGadgets(userWithPermissions('tests.gadgets.import'));

    $this->get(route('tests.gadgets.import.rejects', $sheetImport))->assertNotFound();
});
