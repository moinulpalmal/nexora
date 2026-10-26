<?php

namespace App\Concerns;

use App\Contracts\LabelledEnum;

/**
 * The standard `options()` body for a {@see LabelledEnum}.
 *
 * One body instead of one per enum, so no enum's option list can silently drift
 * from its siblings' shape. An enum whose options carry an extra key overrides
 * `options()`; `array{value, label, extra}` is still a valid subtype.
 */
trait ProvidesOptions
{
    /**
     * Every case as the option list a combobox or filter cell renders.
     *
     * @return list<array{value: int|string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
