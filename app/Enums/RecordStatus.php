<?php

namespace App\Enums;

use App\Concerns\HasStatus;
use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;

/**
 * Whether a record is in active use.
 *
 * The application's one active/inactive vocabulary, stored as a single
 * character. Applied through {@see HasStatus}, which supplies the cast and the
 * scopes so a model does not re-declare them.
 *
 * At the root of `app/Enums/` because it belongs to no module.
 *
 * **This is not a workflow status.** A document moving through
 * Draft → Approved → Cancelled is a different concept with its own lifecycle,
 * and belongs in its own module-scoped enum — which is why this one is named
 * `RecordStatus` rather than `Status`.
 */
enum RecordStatus: string implements LabelledEnum
{
    use ProvidesOptions;

    case Active = 'A';
    case Inactive = 'I';

    public static function id(): string
    {
        return 'record-status';
    }

    /**
     * The label rendered in a status select.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Inactive => __('Inactive'),
        };
    }
}
