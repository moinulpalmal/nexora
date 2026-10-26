<?php

namespace App\Contracts;

use App\Concerns\ProvidesOptions;

/**
 * An {@see IdentifiedEnum} that is shown to a person.
 *
 * Adds `label()` and `options()` on top of the bare id — the shape a combobox,
 * a badge or a filter cell renders. {@see ProvidesOptions} supplies the
 * standard `options()` body.
 */
interface LabelledEnum extends IdentifiedEnum
{
    /**
     * The human-readable text for this case.
     */
    public function label(): string;

    /**
     * Every case as the option list a combobox or filter cell renders.
     *
     * @return list<array{value: int|string, label: string}>
     */
    public static function options(): array;
}
