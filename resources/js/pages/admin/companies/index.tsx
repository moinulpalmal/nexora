import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import CompanyController from '@/actions/App/Http/Controllers/Admin/CompanyController';
import CompanyFormDialog from '@/components/admin/company-form-dialog';
import Heading from '@/components/heading';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ConfirmActionDialog from '@/components/shared/confirm-action-dialog';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useCan } from '@/hooks/use-can';
import { useListFilters } from '@/hooks/use-list-filters';
import { useRowSelection } from '@/hooks/use-row-selection';
import { RECORD_STATUS } from '@/lib/enums';
import { index } from '@/routes/admin/companies';
import type {
    CompanyFilters,
    CompanyListItem,
    Filterable,
    Paginated,
    StatusOption,
} from '@/types';

type Props = {
    companies: Paginated<CompanyListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: CompanyFilters;
    counts: { active: number; trashed: number };
};

/**
 * Admin → Companies (ARCHITECTURE.md §9.10). One page with Active/Deleted
 * tabs, as Users; every action opens a dialog on it. Buttons are shown only to
 * a holder of their permission — convenience, not authorization.
 *
 * Delete is disabled while anything belongs to the company, with the reason
 * on hover — the server refuses it the same way (`CompanyService::deletionBlocker()`).
 *
 * **Bulk delete** on both tabs: tick rows (the header box ticks every row on
 * this page) and delete them — moved to Deleted on the Active tab, removed for
 * good on the Deleted tab. A company something belongs to cannot be ticked,
 * and the server re-checks each one.
 */
export default function CompaniesIndex({
    companies,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('admin.companies.create');
    const canUpdate = useCan('admin.companies.update');
    const canDelete = useCan('admin.companies.delete');
    const canRestore = useCan('admin.companies.restore');
    const canForceDelete = useCan('admin.companies.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(
        companies.data,
        (company) => company.deletion_blocker === null,
    );

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
        only: ['companies', 'filters'],
        refreshOnly: ['companies', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Companies" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Companies"
                        description="The group companies every Projects, Accounting and HRM record belongs to. Retire a company by making it inactive; it can be deleted only while nothing belongs to it."
                    />

                    {canCreate && !isDeleted && (
                        <CompanyFormDialog
                            submit={CompanyController.store.form()}
                            statuses={statuses}
                            title="New company"
                            description="The name and short code must not be used by any other company, including a deleted one."
                        >
                            <Button data-test="new-company">
                                <Plus /> New company
                            </Button>
                        </CompanyFormDialog>
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

                {canBulkDelete && (
                    <BulkActionBar
                        count={selection.count}
                        onClear={selection.clear}
                        note="Companies that departments belong to cannot be ticked."
                    >
                        <ConfirmActionDialog
                            submit={
                                isDeleted
                                    ? CompanyController.bulkForceDelete.form()
                                    : CompanyController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'company' : 'companies'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'company' : 'companies'}?`
                            }
                            description={
                                isDeleted
                                    ? 'They are destroyed and their names and short codes become free. Their audit history stays. This cannot be undone.'
                                    : 'They move to Deleted and can no longer be selected. Their names and short codes stay reserved.'
                            }
                            confirmLabel={
                                isDeleted ? 'Delete permanently' : 'Delete'
                            }
                            onSuccess={selection.clear}
                            testId="bulk-delete-companies"
                            fields={selection.selected.map((id) => (
                                <input
                                    key={id}
                                    type="hidden"
                                    name="ids[]"
                                    value={id}
                                />
                            ))}
                        >
                            <Button
                                size="sm"
                                variant="destructive"
                                data-test="bulk-delete-companies"
                            >
                                <Trash2 />
                                {isDeleted ? 'Delete permanently' : 'Delete'}
                            </Button>
                        </ConfirmActionDialog>
                    </BulkActionBar>
                )}

                <div className="overflow-x-auto rounded-box border border-base-300/70">
                    <table className="table table-sm">
                        <thead>
                            <tr>
                                {canBulkDelete && (
                                    <th className="w-10">
                                        <Checkbox
                                            aria-label="Select every company on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-companies"
                                        />
                                    </th>
                                )}
                                <SortableHeader {...sortProps('short_code')}>
                                    Code
                                </SortableHeader>
                                <SortableHeader {...sortProps('name')}>
                                    Name
                                </SortableHeader>
                                <th className="hidden md:table-cell">Users</th>
                                <th>{isDeleted ? 'Deleted' : 'Status'}</th>
                                <th className="w-32" />
                            </tr>

                            <ColumnFilterRow
                                filterable={filterable}
                                draft={draft}
                                onFilter={setFilter}
                                cells={[
                                    ...(canBulkDelete
                                        ? [{ type: 'none' as const }]
                                        : []),
                                    {
                                        type: 'text',
                                        column: 'short_code',
                                        label: 'code',
                                    },
                                    {
                                        type: 'text',
                                        column: 'name',
                                        label: 'name',
                                    },
                                    {
                                        type: 'none',
                                        className: 'hidden md:table-cell',
                                    },
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
                                    { type: 'none' },
                                ]}
                            />
                        </thead>
                        <tbody>
                            {companies.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 6 : 5}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted companies.'
                                            : 'No companies match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {companies.data.map((company) => (
                                <tr key={company.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${company.name}`}
                                                checked={selection.isSelected(
                                                    company.id,
                                                )}
                                                disabled={
                                                    !selection.canSelect(
                                                        company,
                                                    )
                                                }
                                                onChange={() =>
                                                    selection.toggle(company.id)
                                                }
                                                data-test="select-company"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {company.short_code}
                                    </td>

                                    <td className="font-medium">
                                        {company.name}
                                    </td>

                                    <td className="hidden text-xs text-base-content/70 md:table-cell">
                                        {company.users_count}
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {company.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${company.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {company.status ===
                                                RECORD_STATUS.Active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </span>
                                        )}
                                    </td>

                                    <td>
                                        <div className="flex items-center justify-end gap-1">
                                            {isDeleted ? (
                                                <>
                                                    {canRestore && (
                                                        <ConfirmActionDialog
                                                            submit={CompanyController.restore.form(
                                                                company.id,
                                                            )}
                                                            title={`Restore ${company.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-company"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${company.name}`}
                                                                data-test="restore-company"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <span
                                                            title={
                                                                company.deletion_blocker ??
                                                                undefined
                                                            }
                                                        >
                                                            <ConfirmActionDialog
                                                                submit={CompanyController.forceDelete.form(
                                                                    company.id,
                                                                )}
                                                                title={`Permanently delete ${company.name}?`}
                                                                description="The company is destroyed and its name and short code become free. Its audit history stays. This cannot be undone."
                                                                confirmLabel="Delete permanently"
                                                                disabled={
                                                                    company.deletion_blocker !==
                                                                    null
                                                                }
                                                                testId="force-delete-company"
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    disabled={
                                                                        company.deletion_blocker !==
                                                                        null
                                                                    }
                                                                    aria-label={`Permanently delete ${company.name}`}
                                                                    data-test="force-delete-company"
                                                                >
                                                                    <Trash2 className="text-error" />
                                                                </Button>
                                                            </ConfirmActionDialog>
                                                        </span>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <CompanyFormDialog
                                                            submit={CompanyController.update.form(
                                                                company.id,
                                                            )}
                                                            statuses={statuses}
                                                            company={company}
                                                            title={`Edit ${company.name}`}
                                                            description="Making a company inactive stops anyone selecting it; its records stay."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${company.name}`}
                                                                data-test="edit-company"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </CompanyFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <span
                                                            title={
                                                                company.deletion_blocker ??
                                                                undefined
                                                            }
                                                        >
                                                            <ConfirmActionDialog
                                                                submit={CompanyController.destroy.form(
                                                                    company.id,
                                                                )}
                                                                title={`Delete ${company.name}?`}
                                                                description="It moves to Deleted and can no longer be selected. Its name and short code stay reserved until it is permanently deleted."
                                                                confirmLabel="Delete"
                                                                disabled={
                                                                    company.deletion_blocker !==
                                                                    null
                                                                }
                                                                testId="delete-company"
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    disabled={
                                                                        company.deletion_blocker !==
                                                                        null
                                                                    }
                                                                    aria-label={`Delete ${company.name}`}
                                                                    data-test="delete-company"
                                                                >
                                                                    <Trash2 className="text-error" />
                                                                </Button>
                                                            </ConfirmActionDialog>
                                                        </span>
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

                <Pagination page={companies} />
            </div>
        </>
    );
}

CompaniesIndex.layout = {
    breadcrumbs: [{ title: 'Companies', href: index() }],
};
