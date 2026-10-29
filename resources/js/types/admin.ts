import type { ListFilters } from '@/types/ui';

/** One row of the users list, as `UserController::describe()` ships it. */
export type UserListItem = {
    id: number;
    name: string;
    username: string;
    email: string;
    phone: string | null;
    employee_code: string | null;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. */
    status: string;
    avatar_url: string | null;
    roles: string[];
    /** Ids of the companies the user was granted (ARCHITECTURE.md §9.10). */
    companies: number[];
    last_login_at: string | null;
    created_at: string | null;
    deleted_at: string | null;
    /** The signed-in user's own row — they cannot delete, deactivate or re-role it. */
    is_self: boolean;
    /** The only active super admin — the guards refuse to remove them. */
    is_last_super_admin: boolean;
};

export type UserFilters = ListFilters & {
    /**
     * Which record set is listed — the live table or the soft-deleted users.
     * A tab, not a column filter.
     */
    view: 'active' | 'trashed';
};

/** One row of the companies list, as `CompanyController::index()` projects it. */
export type CompanyListItem = {
    id: number;
    name: string;
    short_code: string;
    /** `App\Enums\RecordStatus` — `'A'` or `'I'`. Inactive companies cannot be selected. */
    status: string;
    users_count: number;
    created_at: string | null;
    deleted_at: string | null;
    /** Why the company cannot be deleted right now, or null when it can. */
    deletion_blocker: string | null;
};

export type CompanyFilters = ListFilters & {
    /** Which record set is listed — a tab, not a column filter. */
    view: 'active' | 'trashed';
};

/** A company offered in a user's Companies dialog. */
export type CompanyGrantOption = {
    id: number;
    name: string;
    short_code: string;
    /** An inactive company may be granted; it is usable once reactivated. */
    is_active: boolean;
};

/** Help for one permission — `PermissionDescriptions::for()`. */
export type PermissionHelp = {
    summary: string;
    note: string | null;
    where: string | null;
    curated: boolean;
};

/** Help for one module — `PermissionDescriptions::forModule()`. */
export type ModuleHelp = {
    label: string;
    summary: string;
    where: string | null;
};

/** One permission in the role picker — `PermissionService::describe()`. */
export type PermissionOption = {
    id: number;
    name: string;
    resource: string;
    action: string;
    help: PermissionHelp;
};

/** One row of the roles list. */
export type RoleListItem = {
    id: number;
    name: string;
    permissions: string[];
    permissions_count: number;
    users_count: number;
    is_super_admin: boolean;
    /** Why the role cannot be deleted right now, or null when it can. */
    deletion_blocker: string | null;
};

/** One row of the permissions catalogue. */
export type PermissionListItem = {
    id: number;
    name: string;
    module: string;
    roles: string[];
    help: PermissionHelp;
};

/** One audit row — `AuditLogService::describe()`. */
export type AuditLogItem = {
    id: number;
    event: string;
    event_label: string;
    auditable_type: string | null;
    auditable_id: number | null;
    model_label: string | null;
    actor_name: string | null;
    actor_username: string | null;
    user_id: number | null;
    /** Every key present on either side, in the order first seen. */
    changed: string[];
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    ip_address: string | null;
    url: string | null;
    user_agent: string | null;
    created_at: string | null;
};

/** The role picker's data: permissions grouped by module. */
export type PermissionGroups = Record<string, PermissionOption[]>;

/** Help per module, keyed by module name. */
export type ModuleHelpMap = Record<string, ModuleHelp>;
