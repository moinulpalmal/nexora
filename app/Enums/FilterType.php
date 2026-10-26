<?php

namespace App\Enums;

use App\Contracts\IdentifiedEnum;

/**
 * How one column's filter cell matches.
 *
 * Every list screen has a filter row under its headers, and each model declares
 * what each of its columns does there via `FILTERABLE` (ARCHITECTURE.md §8.6).
 * The choice is **declared per column, never inferred from the column type** —
 * identifiers and phone numbers are `varchar` too, so type inference would make
 * everything `Contains` and quietly retire the indexes that serve them.
 *
 * The trade-off, stated once:
 *
 * - {@see self::Contains} finds mid-string ("868" finds 15868) and **cannot use
 *   an index**, at any selectivity. Right for names and emails.
 * - {@see self::Prefix} is indexable. Right for identifiers.
 *
 * Unlabelled on purpose: it is never rendered to a person. At the root of
 * `app/Enums/` because it belongs to no module.
 */
enum FilterType: string implements IdentifiedEnum
{
    /** `LIKE '%term%'` — finds mid-string, never uses an index. */
    case Contains = 'contains';

    /** `LIKE 'term%'` — indexable; "868" will not find 15868. */
    case Prefix = 'prefix';

    /** `= value` — for the columns whose cell is a dropdown. */
    case Equals = 'equals';

    /**
     * Hands the value to the model's scope of the same name, for a cell that
     * filters on something which is not a column of its own.
     */
    case Scope = 'scope';

    /**
     * `IN (…)` — any of several ticked values, from a checklist filter. The wire
     * format is `?filter[column][]=a&filter[column][]=b`; {@see self::BLANK}
     * among them also matches a `NULL` or empty column.
     */
    case In = 'in';

    /**
     * Hands the *list* of ticked values to the model's scope of the same name —
     * {@see self::In} for a filter that is not a column of its own.
     */
    case ScopeIn = 'scope-in';

    /**
     * The checklist entry "(Blanks)": rows with no value. A sentinel rather than
     * an empty string, because `ConvertEmptyStringsToNull` turns `filter[x][]=`
     * into a `null` that cannot be told from an absent value.
     */
    public const string BLANK = '__blank';

    /**
     * The most values one list filter may carry. Every tick travels in the URL,
     * and web servers refuse request lines over ~8 KB. Ticking every value sends
     * none at all (the filter is dropped), so the cap only binds a hand-picked
     * subset.
     */
    public const int MAX_LIST_VALUES = 100;

    /** Whether the filter takes a list of values rather than one. */
    public function isList(): bool
    {
        return $this === self::In || $this === self::ScopeIn;
    }

    public static function id(): string
    {
        return 'filter-type';
    }
}
