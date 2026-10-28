<?php

namespace App\Enums\Imports;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;

/**
 * What an import does when a sheet row's unique key already exists.
 *
 * Asked **once per file**, on the mapping step — never per row. A spreadsheet
 * import routinely tops up or re-sends the same list, and re-asking the
 * question for every one of a few thousand rows would make the import unusable.
 */
enum DuplicateAction: string implements LabelledEnum
{
    use ProvidesOptions;

    /** Leave the existing row exactly as it is. */
    case Skip = 'skip';

    /** Overwrite the existing row with the sheet's values. */
    case Update = 'update';

    public static function id(): string
    {
        return 'duplicate-action';
    }

    public function label(): string
    {
        return match ($this) {
            self::Skip => __('Skip existing rows'),
            self::Update => __('Update existing rows'),
        };
    }
}
