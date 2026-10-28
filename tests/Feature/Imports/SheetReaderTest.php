<?php

use App\Exceptions\Imports\SheetImportException;
use App\Services\Imports\ColumnMap;
use App\Services\Imports\ImportField;
use App\Services\Imports\ImportLimits;
use App\Services\Imports\SheetReader;

/*
|--------------------------------------------------------------------------
| The mapped-import engine — reading and mapping
|--------------------------------------------------------------------------
|
| `SheetReader`, `ColumnMap` and `ImportField` take no model and touch no
| database, so they are proved here on their own (ARCHITECTURE.md §9.13). What
| an importer does with what they produce — writing, duplicates, stamping,
| auditing — is `SheetImportLifecycleTest`'s job.
|
*/

function readWorkbook(string $path, ImportLimits $limits = new ImportLimits(maxRows: 100, maxColumns: 20, maxSheets: 5)): array
{
    try {
        return (new SheetReader)->read($path, basename($path), $limits);
    } finally {
        unlink($path);
    }
}

test('a clean sheet reads its header and every data row', function () {
    $workbook = readWorkbook(workbookFixture([
        'Widgets' => [['SL', 'Code', 'Name'], [1, 'w-001', 'First'], [2, 'w-002', 'Second'], [3, 'w-003', 'Third']],
    ]));

    expect($workbook['sheets'])->toHaveCount(1)
        ->and($workbook['skipped'])->toBe([])
        ->and($workbook['sheets'][0]['index'])->toBe(0)
        ->and($workbook['sheets'][0]['headers'])->toBe(['SL', 'Code', 'Name'])
        ->and($workbook['sheets'][0]['rows'])->toHaveCount(3)
        ->and($workbook['sheets'][0]['rows'][0])->toBe(['1', 'w-001', 'First']);
});

test('blank rows at the tail are not data', function () {
    $workbook = readWorkbook(workbookFixture([
        'Widgets' => [['Code'], ['w-001'], [null], [null], ['w-002']],
    ]));

    expect($workbook['sheets'][0]['rows'])->toBe([['w-001'], ['w-002']]);
});

test('a sheet with no header row is refused', function () {
    readWorkbook(workbookFixture(['Widgets' => [[], ['w-001', 'First']]]));
})->throws(SheetImportException::class, 'no column headers');

test('a file that is not a spreadsheet is refused by its magic bytes', function () {
    $path = tempnam(sys_get_temp_dir(), 'not-a-sheet');
    file_put_contents($path, 'this is plain text, not a workbook');

    readWorkbook($path);
})->throws(SheetImportException::class, 'not a spreadsheet');

test('a sheet holding more rows than the limit is refused', function () {
    readWorkbook(
        workbookFixture(['Widgets' => [['Code'], ['1'], ['2'], ['3']]]),
        new ImportLimits(maxRows: 2, maxColumns: 20, maxSheets: 5),
    );
})->throws(SheetImportException::class, 'past 2 rows');

test('a refused sheet reports its true column count, not the limit plus one', function () {
    readWorkbook(
        workbookFixture(['Wide' => [range(1, 10), range(1, 10)]]),
        new ImportLimits(maxRows: 100, maxColumns: 3, maxSheets: 5),
    );
})->throws(SheetImportException::class, 'holds 10 columns and the limit is 3');

test('every worksheet is read, each with its own headers', function () {
    $workbook = readWorkbook(workbookFixture([
        'Plant A' => [['Code', 'Name'], ['w-001', 'First']],
        // Different headers in a different order — why each tab is mapped on its own.
        'Plant B' => [['Name', 'Code'], ['Second', 'w-002'], ['Third', 'w-003']],
    ]));

    expect($workbook['sheets'])->toHaveCount(2)
        ->and($workbook['sheets'][0]['name'])->toBe('Plant A')
        ->and($workbook['sheets'][1]['index'])->toBe(1)
        ->and($workbook['sheets'][1]['headers'])->toBe(['Name', 'Code'])
        ->and($workbook['sheets'][1]['rows'])->toHaveCount(2);
});

test('a tab with no header row is skipped with a warning and the rest are read', function () {
    $reader = new SheetReader;
    $path = workbookFixture([
        'Widgets' => [['Code'], ['w-001']],
        'Notes' => [[], ['Sent on Tuesday']],
    ]);

    $workbook = $reader->read($path, 'with-notes.xlsx', new ImportLimits(maxRows: 100, maxColumns: 20, maxSheets: 5));
    unlink($path);

    expect($workbook['sheets'])->toHaveCount(1)
        ->and($workbook['skipped'][0])->toMatchArray(['name' => 'Notes', 'reason' => 'no-header'])
        ->and($reader->warningsFor($workbook['skipped'])[0])->toContain('Notes');
});

test('the staged index is the workbook index, not the position after skipping', function () {
    $workbook = readWorkbook(workbookFixture([
        'Notes' => [[], ['prose']],
        'Widgets' => [['Code'], ['w-001']],
    ]));

    expect($workbook['sheets'][0]['index'])->toBe(1)
        ->and($workbook['sheets'][0]['name'])->toBe('Widgets');
});

test('a tab with headers but no data rows is skipped', function () {
    $workbook = readWorkbook(workbookFixture([
        'Widgets' => [['Code'], ['w-001']],
        'Next year' => [['Code']],
    ]));

    expect($workbook['sheets'])->toHaveCount(1)
        ->and($workbook['skipped'][0]['reason'])->toBe('no-rows');
});

test('a workbook whose every tab lacks headers is refused as headerless', function () {
    readWorkbook(workbookFixture(['One' => [[], ['prose']], 'Two' => [[], ['more']]]));
})->throws(SheetImportException::class, 'no column headers');

test('a workbook with headers everywhere but no data anywhere says so', function () {
    readWorkbook(workbookFixture(['One' => [['Code']], 'Two' => [['Code']]]));
})->throws(SheetImportException::class, 'no data rows');

test('a tab with two identical headers refuses the whole workbook by name', function () {
    readWorkbook(workbookFixture([
        'Widgets' => [['Code'], ['w-001']],
        'Plant B' => [['Code', 'code'], ['a', 'b']],
    ]));
})->throws(SheetImportException::class, 'on the tab "Plant B"');

test('a workbook with more tabs than the limit is refused', function () {
    readWorkbook(
        workbookFixture(['One' => [['Code'], ['1']], 'Two' => [['Code'], ['2']], 'Three' => [['Code'], ['3']]]),
        new ImportLimits(maxRows: 100, maxColumns: 20, maxSheets: 2),
    );
})->throws(SheetImportException::class, 'holds 3 worksheets and the limit is 2');

test('the row limit is a workbook-wide budget and the refusal names the tab', function () {
    readWorkbook(
        workbookFixture(['One' => [['Code'], ['1'], ['2']], 'Two' => [['Code'], ['3'], ['4']]]),
        new ImportLimits(maxRows: 3, maxColumns: 20, maxSheets: 5),
    );
})->throws(SheetImportException::class, 'The tab "Two" takes this workbook past 1 rows');

test('a column is suggested when its header matches a field key or label', function () {
    $fields = [
        new ImportField('short_name', 'Abbreviation'),
        new ImportField('name', 'Full name'),
    ];

    expect((new SheetReader)->suggestMap(['SL', 'Short  Name', 'full name'], $fields))
        ->toBe(['short_name' => 1, 'name' => 2]);
});

test('a field with no matching header is left unsuggested', function () {
    expect((new SheetReader)->suggestMap(['SL', 'Code'], [new ImportField('nothing', 'Nothing Like This')]))
        ->toBe([]);
});

test('a required field left unmapped is refused, naming the field', function () {
    (new ColumnMap(['code' => 0]))->validate([
        new ImportField('code', 'Code', required: true),
        new ImportField('name', 'Name', required: true),
    ]);
})->throws(SheetImportException::class, 'Name is required');

test('an optional field left unmapped does not block validation', function () {
    (new ColumnMap([]))->validate([new ImportField('note', 'Note')]);
})->throwsNoExceptions();

test('a field applies its transform and serialises without it', function () {
    $field = new ImportField('code', 'Code', required: true, maxLength: 20, transform: fn (mixed $value): mixed => strtoupper(trim((string) $value)));

    expect($field->apply(' w-1 '))->toBe('W-1')
        ->and((new ImportField('note', 'Note'))->apply(' raw '))->toBe(' raw ')
        ->and($field->toArray())->toBe(['key' => 'code', 'label' => 'Code', 'required' => true]);
});
