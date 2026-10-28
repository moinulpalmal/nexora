<?php

namespace App\Enums\Imports;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;

/**
 * Where a `sheet_imports` row stands.
 *
 * A plain string column cast through a PHP enum, not a database enum type —
 * the same choice every other status column in this application makes
 * (ARCHITECTURE.md §6.3).
 *
 * **There is no `Failed` case.** A confirm that fails validation writes
 * nothing and leaves the row `Pending`: the uploader adjusts the
 * mapping or the duplicate choice and confirms again, on the same staged
 * rows, without re-uploading. A row invalid no matter how it is mapped stays
 * `Pending` indefinitely, which is honest: it genuinely has not been resolved.
 *
 * **`Cancelled` is not `Failed` under another name.** `Failed` was rejected
 * because it describes a transient condition the uploader can still resolve on
 * the same staged rows. `Cancelled` is the opposite: a deliberate human
 * decision that this file will *never* be imported — the uploader (or anyone
 * with the import permission) has abandoned it from the pending banner. That
 * is resolved, terminally, so it earns a case where `Failed` did
 * not. Only a `Pending` row can be cancelled; the staged rows and the retained
 * workbook are kept (ARCHITECTURE.md §9.13), and the `updated` audit event on
 * the row is the whole record of who cancelled and when.
 */
enum ImportStatus: string implements LabelledEnum
{
    use ProvidesOptions;

    /** Staged — headers and rows read, waiting for the mapping to be confirmed. */
    case Pending = 'pending';

    /** The mapping was confirmed and the rows were written. */
    case Confirmed = 'confirmed';

    /** The uploader abandoned the staged import from the pending banner; nothing was written. */
    case Cancelled = 'cancelled';

    public static function id(): string
    {
        return 'import-status';
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Confirmed => __('Confirmed'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
