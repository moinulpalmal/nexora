import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import SupplierController from '@/actions/App/Http/Controllers/ProjectManagement/SupplierController';
import Heading from '@/components/heading';
import SupplierFormDialog from '@/components/projects/supplier-form-dialog';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ConfirmActionDialog from '@/components/shared/confirm-action-dialog';
import ListToolbar from '@/components/shared/list-toolbar';
import NoCompanyPanel from '@/components/shared/no-company-panel';
import Pagination from '@/components/shared/pagination';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useCan } from '@/hooks/use-can';
import { useListFilters } from '@/hooks/use-list-filters';
import { useRowSelection } from '@/hooks/use-row-selection';
import { RECORD_STATUS } from '@/lib/enums';
import { index } from '@/routes/projects/settings/suppliers';
import type {
    SupplierFilters,
    SupplierListItem,
    CompanySummary,
    Filterable,
    Paginated,
    StatusOption,
    SupplierCategoryOption,
} from '@/types';

type Props = {
    suppliers: Paginated<SupplierListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    /** Every supplier category (group-wide), inactive ones marked. */
    categories: SupplierCategoryOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: SupplierFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Settings → Suppliers — the ERP's one supplier list, kept per company
 * (ARCHITECTURE.md §9.10). Laid out as `projects/settings/clients` (Active/Deleted
 * tabs, every action a dialog, bulk delete on both tabs) plus the group-wide
 * category, filtered as `hrm/employees` filters designations, and the tax
 * numbers. No spreadsheet import: suppliers are entered by hand (owner,
 * 2026-11-02).
 *
 * Nothing references a supplier yet, so every row can be deleted and ticked.
 */
export default function SuppliersIndex({
    suppliers,
    company,
    statuses,
    categories,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.settings.suppliers.create');
    const canUpdate = useCan('projects.settings.suppliers.update');
    const canDelete = useCan('projects.settings.suppliers.delete');
    const canRestore = useCan('projects.settings.suppliers.restore');
    const canForceDelete = useCan('projects.settings.suppliers.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(suppliers.data);

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
        only: ['suppliers', 'filters'],
        refreshOnly: ['suppliers', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Suppliers" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Suppliers"
                        description={
                            company === null
                                ? 'The suppliers of the company you are working in.'
                                : `The suppliers of ${company.name} — deleting one moves it to Deleted, where it can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && canCreate && (
                        <SupplierFormDialog
                            submit={SupplierController.store.form()}
                            statuses={statuses}
                            categories={categories}
                            title="New supplier"
                            description={`Added to ${company.name} — the code and name must not be used by another supplier of this company, including a deleted one.`}
                        >
                            <Button data-test="new-supplier">
                                <Plus /> New supplier
                            </Button>
                        </SupplierFormDialog>
                    )}
                </div>

                {company === null ? (
                    <NoCompanyPanel />
                ) : (
                    <>
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
                            >
                                <ConfirmActionDialog
                                    submit={
                                        isDeleted
                                            ? SupplierController.bulkForceDelete.form()
                                            : SupplierController.bulkDestroy.form()
                                    }
                                    title={
                                        isDeleted
                                            ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'supplier' : 'suppliers'}?`
                                            : `Delete ${selection.count} ${selection.count === 1 ? 'supplier' : 'suppliers'}?`
                                    }
                                    description={
                                        isDeleted
                                            ? 'They are destroyed and their codes and names become free in this company. Their audit history stays. This cannot be undone.'
                                            : 'They move to Deleted. Their codes and names stay reserved in this company until they are permanently deleted.'
                                    }
                                    confirmLabel={
                                        isDeleted
                                            ? 'Delete permanently'
                                            : 'Delete'
                                    }
                                    onSuccess={selection.clear}
                                    testId="bulk-delete-suppliers"
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
                                        data-test="bulk-delete-suppliers"
                                    >
                                        <Trash2 />
                                        {isDeleted
                                            ? 'Delete permanently'
                                            : 'Delete'}
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
                                                    aria-label="Select every supplier on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-suppliers"
                                                />
                                            </th>
                                        )}
                                        <SortableHeader {...sortProps('code')}>
                                            Code
                                        </SortableHeader>
                                        <SortableHeader {...sortProps('name')}>
                                            Name
                                        </SortableHeader>
                                        <th>Category</th>
                                        <th>Contact person</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>TIN</th>
                                        <th>BIN</th>
                                        <th>
                                            {isDeleted ? 'Deleted' : 'Status'}
                                        </th>
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
                                                column: 'code',
                                                label: 'code',
                                            },
                                            {
                                                type: 'text',
                                                column: 'name',
                                                label: 'name',
                                            },
                                            {
                                                type: 'select',
                                                column: 'supplier_category_id',
                                                label: 'category',
                                                testId: 'category-filter',
                                                options: [
                                                    { value: '', label: 'All' },
                                                    ...categories.map(
                                                        ({
                                                            value,
                                                            label,
                                                            hint,
                                                        }) => ({
                                                            value: String(
                                                                value,
                                                            ),
                                                            label,
                                                            hint,
                                                        }),
                                                    ),
                                                ],
                                            },
                                            {
                                                type: 'text',
                                                column: 'contact_person',
                                                label: 'contact person',
                                            },
                                            {
                                                type: 'text',
                                                column: 'phone',
                                                label: 'phone',
                                            },
                                            {
                                                type: 'text',
                                                column: 'email',
                                                label: 'email',
                                            },
                                            {
                                                type: 'text',
                                                column: 'tin',
                                                label: 'TIN',
                                            },
                                            {
                                                type: 'text',
                                                column: 'bin',
                                                label: 'BIN',
                                            },
                                            isDeleted
                                                ? { type: 'none' }
                                                : {
                                                      type: 'select',
                                                      column: 'status',
                                                      label: 'status',
                                                      testId: 'status-filter',
                                                      options: [
                                                          {
                                                              value: '',
                                                              label: 'All',
                                                          },
                                                          ...statuses,
                                                      ],
                                                  },
                                            { type: 'none' },
                                        ]}
                                    />
                                </thead>
                                <tbody>
                                    {suppliers.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={
                                                    canBulkDelete ? 11 : 10
                                                }
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted suppliers.'
                                                    : 'No suppliers match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {suppliers.data.map((supplier) => (
                                        <tr key={supplier.id}>
                                            {canBulkDelete && (
                                                <td>
                                                    <Checkbox
                                                        aria-label={`Select ${supplier.name}`}
                                                        checked={selection.isSelected(
                                                            supplier.id,
                                                        )}
                                                        onChange={() =>
                                                            selection.toggle(
                                                                supplier.id,
                                                            )
                                                        }
                                                        data-test="select-supplier"
                                                    />
                                                </td>
                                            )}
                                            <td className="font-mono text-xs">
                                                {supplier.code}
                                            </td>

                                            <td className="font-medium">
                                                {supplier.name}
                                            </td>

                                            <td>{supplier.category_name}</td>

                                            <td>{supplier.contact_person}</td>

                                            <td className="whitespace-nowrap">
                                                {supplier.phone}
                                            </td>

                                            <td>{supplier.email}</td>

                                            <td className="font-mono text-xs">
                                                {supplier.tin}
                                            </td>

                                            <td className="font-mono text-xs whitespace-nowrap">
                                                {supplier.bin}
                                            </td>

                                            <td>
                                                {isDeleted ? (
                                                    <span className="text-xs text-base-content/60">
                                                        {supplier.deleted_at}
                                                    </span>
                                                ) : (
                                                    <span
                                                        className={`badge badge-sm ${supplier.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                                    >
                                                        {supplier.status ===
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
                                                                    submit={SupplierController.restore.form(
                                                                        supplier.id,
                                                                    )}
                                                                    title={`Restore ${supplier.name}?`}
                                                                    description="It moves back to Active with the status it had."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-supplier"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${supplier.name}`}
                                                                        data-test="restore-supplier"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={SupplierController.forceDelete.form(
                                                                        supplier.id,
                                                                    )}
                                                                    title={`Permanently delete ${supplier.name}?`}
                                                                    description="The supplier is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-supplier"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${supplier.name}`}
                                                                        data-test="force-delete-supplier"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <SupplierFormDialog
                                                                    submit={SupplierController.update.form(
                                                                        supplier.id,
                                                                    )}
                                                                    statuses={
                                                                        statuses
                                                                    }
                                                                    categories={
                                                                        categories
                                                                    }
                                                                    supplier={
                                                                        supplier
                                                                    }
                                                                    title={`Edit ${supplier.name}`}
                                                                    description="Making a supplier inactive keeps it on record."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${supplier.name}`}
                                                                        data-test="edit-supplier"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </SupplierFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={SupplierController.destroy.form(
                                                                        supplier.id,
                                                                    )}
                                                                    title={`Delete ${supplier.name}?`}
                                                                    description="It moves to Deleted. Its code and name stay reserved in this company until it is permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-supplier"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${supplier.name}`}
                                                                        data-test="delete-supplier"
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

                        <Pagination page={suppliers} />
                    </>
                )}
            </div>
        </>
    );
}

SuppliersIndex.layout = {
    breadcrumbs: [{ title: 'Suppliers', href: index() }],
};
