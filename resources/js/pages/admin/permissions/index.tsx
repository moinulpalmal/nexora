import { Head } from '@inertiajs/react';
import { PermissionHelpCard } from '@/components/admin/permission-help-card';
import Heading from '@/components/heading';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { HoverCard } from '@/components/ui/hover-card';
import { useListFilters } from '@/hooks/use-list-filters';
import { index } from '@/routes/admin/permissions';
import type {
    Filterable,
    ListFilters,
    Paginated,
    PermissionListItem,
    StatusOption,
} from '@/types';

type Props = {
    permissions: Paginated<PermissionListItem>;
    modules: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: ListFilters;
};

/**
 * Admin → Permissions: the read-only catalogue.
 *
 * Permissions are defined in code (`RolePermissionSeeder`) because one that no
 * code checks does nothing; this page explains what each allows and which roles
 * hold it. Granting happens on Roles. A flat table rather than groups, because
 * grouping and pagination cannot both hold — so the module is a filter cell.
 */
export default function PermissionsIndex({
    permissions,
    modules,
    sortable,
    filterable,
    perPageOptions,
    filters,
}: Props) {
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
        only: ['permissions', 'filters'],
        refreshOnly: ['permissions', 'filters'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Permissions" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Permissions"
                    description="What each permission allows and which roles hold it. Permissions are defined in code; grant them to roles on the Roles page."
                />

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
                                    Permission
                                </SortableHeader>
                                <th>Module</th>
                                <th className="hidden md:table-cell">Allows</th>
                                <th>Roles</th>
                            </tr>

                            <ColumnFilterRow
                                filterable={filterable}
                                draft={draft}
                                onFilter={setFilter}
                                cells={[
                                    {
                                        type: 'text',
                                        column: 'name',
                                        label: 'permission',
                                    },
                                    {
                                        type: 'select',
                                        column: 'module',
                                        label: 'module',
                                        testId: 'module-filter',
                                        options: [
                                            { value: '', label: 'All' },
                                            ...modules,
                                        ],
                                    },
                                    {
                                        type: 'none',
                                        className: 'hidden md:table-cell',
                                    },
                                    { type: 'none' },
                                ]}
                            />
                        </thead>
                        <tbody>
                            {permissions.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="text-center text-base-content/60"
                                    >
                                        No permissions match these filters.
                                    </td>
                                </tr>
                            )}

                            {permissions.data.map((permission) => (
                                <tr key={permission.id}>
                                    <td className="font-mono text-sm">
                                        <HoverCard
                                            content={
                                                <PermissionHelpCard
                                                    name={permission.name}
                                                    help={permission.help}
                                                />
                                            }
                                            testId="permission-help"
                                        >
                                            {permission.name}
                                        </HoverCard>
                                    </td>

                                    <td>
                                        <span className="badge badge-ghost badge-sm">
                                            {permission.module}
                                        </span>
                                    </td>

                                    <td className="hidden max-w-md text-sm text-base-content/80 md:table-cell">
                                        {permission.help.summary}
                                        {permission.help.note && (
                                            <span className="block text-xs text-base-content/60">
                                                {permission.help.note}
                                            </span>
                                        )}
                                    </td>

                                    <td>
                                        <div className="flex flex-wrap gap-1">
                                            {permission.roles.length === 0 ? (
                                                <span className="text-base-content/60">
                                                    —
                                                </span>
                                            ) : (
                                                permission.roles.map((role) => (
                                                    <span
                                                        key={role}
                                                        className="badge badge-ghost font-mono badge-sm"
                                                    >
                                                        {role}
                                                    </span>
                                                ))
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination page={permissions} />
            </div>
        </>
    );
}

PermissionsIndex.layout = {
    breadcrumbs: [{ title: 'Permissions', href: index() }],
};
