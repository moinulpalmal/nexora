import { Head } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
import RoleFormDialog from '@/components/admin/role-form-dialog';
import Heading from '@/components/heading';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ConfirmDeleteDialog from '@/components/shared/confirm-delete-dialog';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { useListFilters } from '@/hooks/use-list-filters';
import { index } from '@/routes/admin/roles';
import type {
    Filterable,
    ListFilters,
    ModuleHelpMap,
    Paginated,
    PermissionGroups,
    RoleListItem,
} from '@/types';

type Props = {
    roles: Paginated<RoleListItem>;
    permissions: PermissionGroups;
    moduleHelp: ModuleHelpMap;
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: ListFilters;
};

/**
 * Admin → Roles. A role is a named bundle of permissions; create and edit
 * happen in a dialog with the grouped permission picker.
 */
export default function RolesIndex({
    roles,
    permissions,
    moduleHelp,
    sortable,
    filterable,
    perPageOptions,
    filters,
}: Props) {
    const canCreate = useCan('admin.roles.create');
    const canUpdate = useCan('admin.roles.update');
    const canDelete = useCan('admin.roles.delete');

    const {
        draft,
        visit,
        setFilter,
        clear,
        hasActiveFilter,
        refresh,
        refreshing,
    } = useListFilters({
        filters,
        url: index,
        only: ['roles', 'filters'],
        refreshOnly: ['roles', 'filters', 'permissions'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Roles" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="A role is a named bundle of permissions. Grant abilities here; code checks permissions, never role names."
                    />

                    {canCreate && (
                        <RoleFormDialog
                            submit={RoleController.store.form()}
                            permissions={permissions}
                            moduleHelp={moduleHelp}
                            title="New role"
                            description="Name it, then tick what it may do. Hover a permission to see what it allows."
                        >
                            <Button data-test="new-role">
                                <Plus /> New role
                            </Button>
                        </RoleFormDialog>
                    )}
                </div>

                <ListToolbar
                    perPage={filters.per_page}
                    perPageOptions={perPageOptions}
                    onPerPage={(per_page) => visit({ per_page })}
                    onClear={clear}
                    hasActiveFilter={hasActiveFilter}
                    onRefresh={refresh}
                    refreshing={refreshing}
                />

                <div className="overflow-x-auto rounded-box border border-base-300/70">
                    <table className="table">
                        <thead>
                            <tr>
                                <SortableHeader {...sortProps('name')}>
                                    Role
                                </SortableHeader>
                                {/* Aggregates, not columns — neither goes
                                    through the allow-list path. */}
                                <th className="text-right">Permissions</th>
                                <th className="text-right">Users</th>
                                <th className="w-24" />
                            </tr>

                            <ColumnFilterRow
                                filterable={filterable}
                                draft={draft}
                                onFilter={setFilter}
                                cells={[
                                    {
                                        type: 'text',
                                        column: 'name',
                                        label: 'role',
                                    },
                                    { type: 'none' },
                                    { type: 'none' },
                                    { type: 'none' },
                                ]}
                            />
                        </thead>
                        <tbody>
                            {roles.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="text-center text-base-content/60"
                                    >
                                        No roles match these filters.
                                    </td>
                                </tr>
                            )}

                            {roles.data.map((role) => (
                                <tr key={role.id}>
                                    <td className="font-mono">
                                        {role.name}
                                        {role.is_super_admin && (
                                            <span className="ml-2 badge font-sans badge-sm badge-neutral">
                                                bypasses all checks
                                            </span>
                                        )}
                                    </td>
                                    <td className="text-right tabular-nums">
                                        {role.is_super_admin
                                            ? 'all'
                                            : role.permissions_count}
                                    </td>
                                    <td className="text-right tabular-nums">
                                        {role.users_count}
                                    </td>
                                    <td>
                                        <div className="flex items-center justify-end gap-1">
                                            {canUpdate &&
                                                !role.is_super_admin && (
                                                    <RoleFormDialog
                                                        submit={RoleController.update.form(
                                                            role.id,
                                                        )}
                                                        permissions={
                                                            permissions
                                                        }
                                                        moduleHelp={moduleHelp}
                                                        role={role}
                                                        title={`Edit ${role.name}`}
                                                        description="Changes apply to every user holding this role, from their next request."
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Edit ${role.name}`}
                                                            data-test="edit-role"
                                                        >
                                                            <Pencil />
                                                        </Button>
                                                    </RoleFormDialog>
                                                )}

                                            {canDelete && (
                                                <span
                                                    title={
                                                        role.deletion_blocker ??
                                                        undefined
                                                    }
                                                >
                                                    <ConfirmDeleteDialog
                                                        submit={RoleController.destroy.form(
                                                            role.id,
                                                        )}
                                                        title={`Delete ${role.name}?`}
                                                        description="The role is removed. This cannot be undone."
                                                        disabled={
                                                            role.deletion_blocker !==
                                                            null
                                                        }
                                                        testId="delete-role"
                                                    />
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination page={roles} />
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Roles', href: index() }],
};
