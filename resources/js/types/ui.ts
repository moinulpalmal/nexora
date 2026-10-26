import type { ReactNode } from 'react';
import type { FilterType } from '@/lib/enums';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type AuthLayoutProps = {
    children?: ReactNode;
    name?: string;
    title?: string;
    description?: string;
};

/**
 * A status option as `RecordStatus::options()` serialises it.
 *
 * `RecordStatus` belongs to no module (ARCHITECTURE.md §9.3.1), so neither does
 * this.
 */
export type StatusOption = {
    value: string;
    label: string;
};

/**
 * How one column's filter cell matches — `App\Enums\FilterType`, via
 * `@/lib/enums` (ARCHITECTURE.md §8.2).
 *
 * `contains` finds mid-string and cannot use an index; `prefix` is indexable and
 * is why "868" does not find code 15868. The page shows the difference in
 * each cell's placeholder rather than leaving people to guess.
 */
export type FilterMatch = FilterType;

/** A model's `FILTERABLE` map, as the controller ships it. */
export type Filterable = Record<string, FilterMatch>;

/**
 * The query state every list screen carries, as `ListRequest::filters()`
 * serialises it. Surface-specific keys extend this.
 *
 * `V` is what one filter cell holds: a string, or for a checklist filter
 * (`FilterType` `in` / `scope-in`) the ticked values — `[]`
 * when unfiltered.
 */
export type ListFilters<V extends string | string[] = string> = {
    /** Allow-listed column name; anything else is rejected server-side. */
    sort: string;
    direction: 'asc' | 'desc';
    /** One of `perPageOptions`; anything else is rejected server-side. */
    per_page: number;
    /**
     * The filter row's values, keyed by column. Every filterable column is
     * present, blank when unfiltered, so the row renders from this alone.
     */
    filter: Record<string, V>;
};

/** One page of a Laravel paginator, as Inertia serialises it. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
};
