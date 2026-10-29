import { Head } from '@inertiajs/react';
import {
    Building2,
    KeyRound,
    Pencil,
    Plus,
    RotateCcw,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserCompanyDialog from '@/components/admin/user-company-dialog';
import UserFormDialog from '@/components/admin/user-form-dialog';
import UserPasswordDialog from '@/components/admin/user-password-dialog';
import UserRoleDialog from '@/components/admin/user-role-dialog';
import Heading from '@/components/heading';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ConfirmActionDialog from '@/components/shared/confirm-action-dialog';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { useInitials } from '@/hooks/use-initials';
import { useListFilters } from '@/hooks/use-list-filters';
import { RECORD_STATUS } from '@/lib/enums';
import { index } from '@/routes/admin/users';
import type {
    CompanyGrantOption,
    Filterable,
    Paginated,
    StatusOption,
    UserFilters,
    UserListItem,
} from '@/types';

type Props = {
    users: Paginated<UserListItem>;
    /** Assignable role names — `super-admin` only for a super admin. */
    roles: string[];
    /** Every undeleted company — only for a holder of `admin.users.assign-companies`. */
    companies: CompanyGrantOption[];
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: UserFilters;
    counts: { active: number; trashed: number };
};

/**
 * Admin → Users. One page; every action opens a dialog on it
 * (ARCHITECTURE.md §8.6, §8.10). Each button is shown only to a holder of its
 * permission — convenience, not authorization: the routes enforce it.
 */
export default function UsersIndex({
    users,
    roles,
    companies,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('admin.users.create');
    const canUpdate = useCan('admin.users.update');
    const canDelete = useCan('admin.users.delete');
    const canRestore = useCan('admin.users.restore');
    const canForceDelete = useCan('admin.users.force-delete');
    const canResetPassword = useCan('admin.users.reset-password');
    const canAssignRoles = useCan('admin.users.assign-roles');
    const canAssignCompanies = useCan('admin.users.assign-companies');
    const getInitials = useInitials();

    const isDeleted = filters.view === 'trashed';

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
        only: ['users', 'filters'],
        refreshOnly: ['users', 'filters', 'counts', 'roles', 'companies'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Users"
                        description="Everyone who can sign in. Deleting a user moves them to Deleted, where they can be restored or removed for good."
                    />

                    {canCreate && !isDeleted && (
                        <UserFormDialog
                            submit={UserController.store.form()}
                            statuses={statuses}
                            roles={roles}
                            title="New user"
                            description="They sign in with either their username or their email."
                        >
                            <Button data-test="new-user">
                                <Plus /> New user
                            </Button>
                        </UserFormDialog>
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
                >
                    {/* Which record set, not which column value — so a tab,
                        not a filter cell. */}
                    <div role="tablist" className="tabs tabs-box">
                        <button
                            type="button"
                            role="tab"
                            className={`tab ${isDeleted ? '' : 'tab-active'}`}
                            onClick={() => visit({ view: 'active' })}
                        >
                            Active
                            <span className="ml-2 badge badge-sm">
                                {counts.active}
                            </span>
                        </button>

                        <button
                            type="button"
                            role="tab"
                            className={`tab ${isDeleted ? 'tab-active' : ''}`}
                            onClick={() => visit({ view: 'trashed' })}
                            data-test="deleted-tab"
                        >
                            Deleted
                            <span className="ml-2 badge badge-sm">
                                {counts.trashed}
                            </span>
                        </button>
                    </div>
                </ListToolbar>

                <div className="overflow-x-auto rounded-box border border-base-300/70">
                    <table className="table table-sm">
                        <thead>
                            <tr>
                                <SortableHeader {...sortProps('name')}>
                                    Name
                                </SortableHeader>
                                <SortableHeader {...sortProps('username')}>
                                    Username
                                </SortableHeader>
                                <SortableHeader
                                    {...sortProps('employee_code')}
                                    className="hidden lg:table-cell"
                                >
                                    Code
                                </SortableHeader>
                                <th className="hidden md:table-cell">Phone</th>
                                <th>Roles</th>
                                <th>{isDeleted ? 'Deleted' : 'Status'}</th>
                                <SortableHeader
                                    {...sortProps('last_login_at')}
                                    className="hidden xl:table-cell"
                                >
                                    Last sign-in
                                </SortableHeader>
                                <th className="w-48" />
                            </tr>

                            <ColumnFilterRow
                                filterable={filterable}
                                draft={draft}
                                onFilter={setFilter}
                                cells={[
                                    /* One heading, two columns underneath it —
                                       so the cell stacks. */
                                    {
                                        type: 'stack',
                                        cells: [
                                            {
                                                type: 'text',
                                                column: 'name',
                                                label: 'name',
                                            },
                                            {
                                                type: 'text',
                                                column: 'email',
                                                label: 'email',
                                                placeholder: 'Email…',
                                            },
                                        ],
                                    },
                                    {
                                        type: 'text',
                                        column: 'username',
                                        label: 'username',
                                    },
                                    {
                                        type: 'text',
                                        column: 'employee_code',
                                        label: 'code',
                                        className: 'hidden lg:table-cell',
                                    },
                                    {
                                        type: 'text',
                                        column: 'phone',
                                        label: 'phone',
                                        className: 'hidden md:table-cell',
                                    },
                                    {
                                        type: 'select',
                                        column: 'role',
                                        label: 'role',
                                        testId: 'role-filter',
                                        options: [
                                            { value: '', label: 'All' },
                                            ...roles.map((role) => ({
                                                value: role,
                                                label: role,
                                            })),
                                        ],
                                    },
                                    /* The column becomes "Deleted" on that
                                       tab, where a status filter would sit
                                       under a heading it does not describe. */
                                    isDeleted
                                        ? { type: 'none' }
                                        : {
                                              type: 'select',
                                              column: 'status',
                                              label: 'status',
                                              testId: 'status-filter',
                                              options: [
                                                  { value: '', label: 'All' },
                                                  ...statuses,
                                              ],
                                          },
                                    {
                                        type: 'none',
                                        className: 'hidden xl:table-cell',
                                    },
                                    { type: 'none' },
                                ]}
                            />
                        </thead>
                        <tbody>
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted users.'
                                            : 'No users match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {users.data.map((user) => (
                                <tr key={user.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <Avatar className="size-8 overflow-hidden rounded-full">
                                                <AvatarImage
                                                    src={
                                                        user.avatar_url ??
                                                        undefined
                                                    }
                                                    alt=""
                                                />
                                                <AvatarFallback className="text-xs">
                                                    {getInitials(user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div className="font-medium">
                                                    {user.name}
                                                    {user.is_self && (
                                                        <span className="ml-2 badge badge-sm badge-neutral">
                                                            you
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="text-xs text-base-content/60">
                                                    {user.email}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td className="font-mono text-xs">
                                        {user.username}
                                    </td>

                                    <td className="hidden font-mono text-xs lg:table-cell">
                                        {user.employee_code ?? '—'}
                                    </td>

                                    <td className="hidden text-xs text-base-content/70 md:table-cell">
                                        {user.phone ?? '—'}
                                    </td>

                                    <td>
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles.length === 0 && (
                                                <span className="text-xs text-base-content/50">
                                                    none
                                                </span>
                                            )}

                                            {user.roles.map((role) => (
                                                <span
                                                    key={role}
                                                    className="badge badge-ghost font-mono badge-sm"
                                                >
                                                    {role}
                                                </span>
                                            ))}
                                        </div>
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {user.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${user.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {user.status ===
                                                RECORD_STATUS.Active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </span>
                                        )}
                                    </td>

                                    <td className="hidden text-xs text-base-content/60 xl:table-cell">
                                        {user.last_login_at
                                            ? new Date(
                                                  user.last_login_at,
                                              ).toLocaleString()
                                            : 'never'}
                                    </td>

                                    <td>
                                        <div className="flex items-center justify-end gap-1">
                                            {isDeleted ? (
                                                <>
                                                    {canRestore && (
                                                        <ConfirmActionDialog
                                                            submit={UserController.restore.form(
                                                                user.id,
                                                            )}
                                                            title={`Restore ${user.name}?`}
                                                            description="They move back to Active and can sign in again with their old password."
                                                            confirmLabel="Restore"
                                                            testId="restore-user"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${user.name}`}
                                                                data-test="restore-user"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={UserController.forceDelete.form(
                                                                user.id,
                                                            )}
                                                            title={`Permanently delete ${user.name}?`}
                                                            description="The account is destroyed and its username and email become free. Their audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            disabled={
                                                                user.is_self ||
                                                                user.is_last_super_admin
                                                            }
                                                            testId="force-delete-user"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                disabled={
                                                                    user.is_self ||
                                                                    user.is_last_super_admin
                                                                }
                                                                aria-label={`Permanently delete ${user.name}`}
                                                                data-test="force-delete-user"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <UserFormDialog
                                                            submit={UserController.update.form(
                                                                user.id,
                                                            )}
                                                            statuses={statuses}
                                                            user={user}
                                                            title={`Edit ${user.name}`}
                                                            description="Roles and passwords are changed with their own actions."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${user.name}`}
                                                                data-test="edit-user"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </UserFormDialog>
                                                    )}

                                                    {canAssignRoles && (
                                                        <UserRoleDialog
                                                            submit={UserController.updateRoles.form(
                                                                user.id,
                                                            )}
                                                            user={user}
                                                            roles={roles}
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                disabled={
                                                                    user.is_self
                                                                }
                                                                aria-label={`Roles for ${user.name}`}
                                                                data-test="user-roles"
                                                            >
                                                                <ShieldCheck />
                                                            </Button>
                                                        </UserRoleDialog>
                                                    )}

                                                    {canAssignCompanies && (
                                                        <UserCompanyDialog
                                                            submit={UserController.updateCompanies.form(
                                                                user.id,
                                                            )}
                                                            user={user}
                                                            companies={
                                                                companies
                                                            }
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                disabled={
                                                                    user.is_self
                                                                }
                                                                aria-label={`Companies for ${user.name}`}
                                                                data-test="user-companies"
                                                            >
                                                                <Building2 />
                                                            </Button>
                                                        </UserCompanyDialog>
                                                    )}

                                                    {canResetPassword && (
                                                        <UserPasswordDialog
                                                            submit={UserController.updatePassword.form(
                                                                user.id,
                                                            )}
                                                            user={user}
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Set password for ${user.name}`}
                                                                data-test="user-password"
                                                            >
                                                                <KeyRound />
                                                            </Button>
                                                        </UserPasswordDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={UserController.destroy.form(
                                                                user.id,
                                                            )}
                                                            title={`Delete ${user.name}?`}
                                                            description="They move to Deleted and can no longer sign in. Their username and email stay reserved until they are permanently deleted."
                                                            confirmLabel="Delete"
                                                            disabled={
                                                                user.is_self ||
                                                                user.is_last_super_admin
                                                            }
                                                            testId="delete-user"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                disabled={
                                                                    user.is_self ||
                                                                    user.is_last_super_admin
                                                                }
                                                                aria-label={`Delete ${user.name}`}
                                                                data-test="delete-user"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination page={users} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Users', href: index() }],
};
