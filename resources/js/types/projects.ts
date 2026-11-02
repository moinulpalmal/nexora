import type { ListFilters } from '@/types/ui';

/** One row of the clients list, as `ClientController::index()` projects it. */
export type ClientListItem = {
    id: number;
    code: string;
    name: string;
    address: string | null;
    description: string | null;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type ClientFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};
