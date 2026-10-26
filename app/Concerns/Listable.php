<?php

namespace App\Concerns;

use App\Enums\FilterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

/**
 * Column filtering and allow-listed sorting for a model behind an index screen.
 *
 * Every Admin list is paginated, sortable, and filtered by a row of cells under
 * its headers (ARCHITECTURE.md §8.6). This is the model half of that; the
 * request half is `App\Http\Requests\ListRequest`.
 *
 * A using model **must** declare both allow-lists:
 *
 * ```php
 * public const array FILTERABLE = [
 *     'name'   => FilterType::Contains,
 *     'status' => FilterType::Equals,
 * ];
 *
 * public const array SORTABLE = ['name', 'created_at'];
 * ```
 *
 * They stay on the model rather than in this trait for two reasons: PHP will not
 * let a class override a constant a trait defines, and controllers ship them to
 * the front end (`User::SORTABLE`) so the table knows which headers to make
 * clickable and which cell to render in each filter column.
 */
trait Listable
{
    /**
     * The column a list falls back to when none is chosen.
     *
     * Override on a model whose natural order is not by name.
     */
    public static function defaultSort(): string
    {
        return static::SORTABLE[0] ?? 'id';
    }

    /**
     * Apply the filter row's values, each column matching the way it declares.
     *
     * The allow-list is load-bearing twice over: a key absent from `FILTERABLE`
     * never reaches the query at all, and the match type comes from the model
     * rather than from the request, so no caller can turn an indexed prefix
     * lookup into a table scan by changing a query parameter.
     *
     * Blank values are skipped rather than matched, so an empty cell means "no
     * filter" instead of "rows whose name is the empty string".
     *
     * A list filter ({@see FilterType::isList()}) with nothing ticked is
     * likewise no filter.
     *
     * @param  Builder<static>  $query
     * @param  array<string, string|list<string>>  $filters
     */
    public function scopeFilterColumns(Builder $query, array $filters): void
    {
        foreach (static::FILTERABLE as $column => $type) {
            $raw = $filters[$column] ?? '';

            if ($type->isList()) {
                $values = array_values(array_filter(
                    array_map(trim(...), is_array($raw) ? $raw : [$raw]),
                    fn (string $one): bool => $one !== '',
                ));

                if ($values === []) {
                    continue;
                }

                match ($type) {
                    FilterType::In => static::whereAnyOf($query, $query->qualifyColumn($column), $values),
                    default => $query->{Str::camel($column)}($values),
                };

                continue;
            }

            $value = is_array($raw) ? '' : trim((string) $raw);

            if ($value === '') {
                continue;
            }

            match ($type) {
                FilterType::Contains => static::whereLikeLiteral($query, $column, '%'.static::escapeLike($value).'%'),
                FilterType::Prefix => static::whereLikeLiteral($query, $column, static::escapeLike($value).'%'),
                FilterType::Equals => $query->where($column, $value),
                FilterType::Scope => $query->{Str::camel($column)}($value),
                default => null,
            };
        }
    }

    /**
     * Order the list by an allow-listed column.
     *
     * The allow-list is load-bearing: passing request input straight to
     * `orderBy()` is a SQL injection. An unknown column falls back to the
     * default rather than reaching the database.
     *
     * @param  Builder<static>  $query
     */
    public function scopeSortBy(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, static::SORTABLE, true) ? $column : static::defaultSort();
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderBy($column, $direction);
    }

    /**
     * Rows whose column is any of `$values` — {@see FilterType::BLANK} among them
     * also matching a `NULL` or empty column, the checklist's "(Blanks)".
     *
     * Public and static so a `ScopeIn` scope reaching a column through a join can
     * match the same way.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>|QueryBuilder  $query
     * @param  list<string>  $values
     */
    public static function whereAnyOf(Builder|QueryBuilder $query, string $column, array $values): void
    {
        $present = array_values(array_filter($values, fn (string $value): bool => $value !== FilterType::BLANK));
        $blank = count($present) !== count($values);

        $query->where(function ($match) use ($column, $present, $blank): void {
            if ($present !== []) {
                $match->whereIn($column, $present);
            }

            if ($blank) {
                $match->orWhereNull($column)->orWhere($column, '');
            }
        });
    }

    /**
     * The character {@see self::escapeLike()} escapes with.
     *
     * Not a backslash: MySQL treats `\` as `LIKE`'s default escape but SQLite has
     * no default at all, so a backslash-escaped `%` still matched every row there.
     * `!` means nothing special in a string literal on either driver, so one
     * explicit `ESCAPE '!'` clause behaves the same everywhere.
     */
    public const string LIKE_ESCAPE = '!';

    /**
     * Neutralise the wildcards a user can type into a `LIKE` term.
     *
     * Without this a term of "%" matches every row instead of none, and "_"
     * silently becomes a single-character wildcard. Pair it with
     * {@see self::whereLikeLiteral()}, which declares the escape character.
     */
    public static function escapeLike(string $term): string
    {
        $escape = self::LIKE_ESCAPE;

        return str_replace([$escape, '%', '_'], [$escape.$escape, $escape.'%', $escape.'_'], $term);
    }

    /**
     * `column LIKE pattern ESCAPE '!'` — for a pattern built with
     * {@see self::escapeLike()}.
     *
     * The column is unquoted, so a filterable column must not be a reserved word.
     * Every caller passes a name written in code; `literal-string` makes static
     * analysis prove it.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>|QueryBuilder  $query
     * @param  literal-string  $column
     */
    public static function whereLikeLiteral(Builder|QueryBuilder $query, string $column, string $pattern): void
    {
        // The query builder has no `ESCAPE` support, so this one fragment is raw.
        // Only literal pieces go into it — `$column` is a `literal-string` from a
        // model's `FILTERABLE` keys — and the user's text travels as a binding.
        $query->whereRaw($column." like ? escape '".self::LIKE_ESCAPE."'", [$pattern]);
    }
}
