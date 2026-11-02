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

/** One row of the tender types list, as `TenderTypeController::index()` projects it. */
export type TenderTypeListItem = {
    id: number;
    code: string;
    name: string;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type TenderTypeFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/** One row of the supplier categories list, as `SupplierCategoryController::index()` projects it. */
export type SupplierCategoryListItem = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type SupplierCategoryFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/** One row of the units of measure list, as `UnitController::index()` projects it. */
export type UnitListItem = {
    id: number;
    code: string;
    name: string;
    /** The printed form, case kept — `kg`, `m²`. */
    symbol: string | null;
    /** How many decimals a quantity in this unit takes, 0–6. */
    decimal_places: number;
    description: string | null;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type UnitFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/** A supplier category in the suppliers form and filter, as `SupplierController::categoryOptions()` builds it. */
export type SupplierCategoryOption = {
    value: number;
    label: string;
    /** The code, shown after the name. */
    hint: string;
    /** `App\Enums\RecordStatus`. */
    status: string;
};

/** One row of the suppliers list, as `SupplierController::index()` projects it. */
export type SupplierListItem = {
    id: number;
    supplier_category_id: number;
    category_name: string;
    code: string;
    name: string;
    address: string | null;
    description: string | null;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    tin: string | null;
    bin: string | null;
    trade_license_no: string | null;
    bank_name: string | null;
    bank_branch: string | null;
    bank_account_name: string | null;
    bank_account_number: string | null;
    bank_routing_number: string | null;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Not a deletion. */
    status: string;
    created_at: string | null;
    deleted_at: string | null;
};

export type SupplierFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};
