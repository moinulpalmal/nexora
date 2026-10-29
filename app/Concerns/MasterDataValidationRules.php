<?php

namespace App\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * How a master table's names and codes are cleaned and kept unique — shared by
 * the Company and Department forms and the department import, so a value typed
 * into a form and the same value read from a sheet land identically.
 *
 * **Normalise in `prepareForValidation()`, never a mutator**: a mutator runs
 * after validation, so `ab-1` and `AB-1` would both pass the unique check and
 * then collide at the driver. The static normalisers are the import fields'
 * transforms too.
 *
 * **Uniqueness ignores case on every driver.** MySQL's collation already
 * does; SQLite (the test database) does not, and `Rule::unique` compares as
 * the driver does — so the check is a `LOWER()` comparison of its own. The
 * database's unique indexes still back it.
 *
 * **A soft-deleted row keeps its value reserved**, and the refusal says so:
 * the fix is to restore that row, not to type a different name.
 */
trait MasterDataValidationRules
{
    /**
     * Trim, and collapse every run of whitespace inside to one space —
     * `"Human  Resources "` is `"Human Resources"`.
     */
    public static function normaliseName(mixed $value): mixed
    {
        return is_string($value) ? preg_replace('/\s+/u', ' ', trim($value)) : $value;
    }

    /**
     * A name, uppercased — codes are compared and shown in capitals.
     */
    public static function normaliseCode(mixed $value): mixed
    {
        $value = self::normaliseName($value);

        return is_string($value) ? mb_strtoupper($value) : $value;
    }

    /**
     * A rule refusing a value another row already holds, ignoring case and
     * counting soft-deleted rows.
     *
     * @param  Builder<covariant Model>  $rows  every row the value must be unique among, trashed included
     * @param  literal-string  $column  a column of that one table — it is written into the SQL as is
     * @param  string  $taken  the message for a live clash (`:value` is replaced)
     * @param  string  $takenByDeleted  the message when the clash is a deleted row
     */
    protected function uniqueIgnoringCase(Builder $rows, string $column, ?int $ignore, string $taken, string $takenByDeleted): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($rows, $column, $ignore, $taken, $takenByDeleted): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $clash = (clone $rows)
                ->whereRaw('LOWER('.$column.') = ?', [mb_strtolower($value)])
                ->when($ignore !== null, fn (Builder $query) => $query->whereKeyNot($ignore))
                ->first();

            if ($clash === null) {
                return;
            }

            $deleted = $clash->getAttribute('deleted_at') !== null;

            $fail(str_replace(':value', $value, $deleted ? $takenByDeleted : $taken));
        };
    }
}
