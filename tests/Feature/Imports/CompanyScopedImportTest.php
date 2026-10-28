<?php

use App\Models\SheetImport;
use App\Models\User;
use Tests\Fixtures\Imports\CompanyWidget;
use Tests\Fixtures\Imports\ImportFixtures;

/*
|--------------------------------------------------------------------------
| Company-scoped imports — the pre-wired company dimension
|--------------------------------------------------------------------------
|
| No shipped importer is company-scoped until the Company master exists
| (ARCHITECTURE.md §9.10), but the engine's half of it is built now so the first
| one needs nothing from it. The fixture controller reads the selected company
| from the session, standing in for the planned header switcher.
|
*/

beforeEach(function () {
    ImportFixtures::install();
});

function stageCompanyWidgets(User $uploader, ?int $companyId, array $rows): void
{
    test()->actingAs($uploader)
        ->withSession($companyId === null ? [] : ['selected_company_id' => $companyId])
        ->post(route('tests.company-widgets.import.store'), [
            'file' => uploadedWorkbook(['Widgets' => [['Code', 'Name'], ...$rows]]),
            // Ignored: the company comes from the server, never the request body.
            'company_id' => 999,
        ]);
}

test('a company-scoped import is refused when the uploader has no selected company', function () {
    $this->actingAs(userWithPermissions('tests.company-widgets.import'));

    $response = $this->post(route('tests.company-widgets.import.store'), [
        'file' => uploadedWorkbook(['Widgets' => [['Code', 'Name'], ['w-001', 'First']]]),
        'company_id' => 7,
    ]);

    assertToast($response, 'error');
    expect(SheetImport::query()->count())->toBe(0);
});

test('rows land in the selected company, and the same code in another company is not a duplicate', function () {
    $user = userWithPermissions('tests.company-widgets.import');
    CompanyWidget::query()->create(['company_id' => 2, 'code' => 'W-001', 'name' => 'Belongs to company 2']);

    stageCompanyWidgets($user, 1, [['w-001', 'First'], ['w-002', 'Second']]);
    $sheetImport = SheetImport::query()->sole();

    $this->post(route('tests.company-widgets.import.confirm', $sheetImport), [
        'map' => [0 => ['code' => 0, 'name' => 1]],
        'on_duplicate' => 'update',
    ]);

    expect($sheetImport->company_id)->toBe(1)
        ->and($sheetImport->fresh()->created_count)->toBe(2)
        ->and(CompanyWidget::query()->where('company_id', 1)->orderBy('code')->pluck('name')->all())->toBe(['First', 'Second'])
        ->and(CompanyWidget::query()->where('company_id', 2)->sole()->name)->toBe('Belongs to company 2');
});

test('a duplicate is matched within the selected company only', function () {
    $user = userWithPermissions('tests.company-widgets.import');
    CompanyWidget::query()->create(['company_id' => 1, 'code' => 'W-001', 'name' => 'Original']);

    stageCompanyWidgets($user, 1, [['w-001', 'Renamed']]);
    $sheetImport = SheetImport::query()->sole();

    $this->post(route('tests.company-widgets.import.confirm', $sheetImport), [
        'map' => [0 => ['code' => 0, 'name' => 1]],
        'on_duplicate' => 'update',
    ]);

    expect($sheetImport->fresh()->updated_count)->toBe(1)
        ->and(CompanyWidget::query()->sole()->name)->toBe('Renamed');
});
