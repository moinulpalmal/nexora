import type { ListFilters } from '@/types/ui';

/** One row of the departments list, as `DepartmentController::index()` projects it. */
export type DepartmentListItem = {
    id: number;
    code: string;
    name: string;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type DepartmentFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};
