import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import SupplierCategoryController from '@/actions/App/Http/Controllers/ProjectManagement/SupplierCategoryController';
import Heading from '@/components/heading';
import SupplierCategoryFormDialog from '@/components/projects/supplier-category-form-dialog';
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
import { index } from '@/routes/projects/supplier-categories';
import type {
    Filterable,
    Paginated,
    StatusOption,
    SupplierCategoryFilters,
    SupplierCategoryListItem,
} from '@/types';

type Props = {
    supplierCategories: Paginated<SupplierCategoryListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: SupplierCategoryFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Supplier Categories — what kind of supplier each one is, one
 * list every company shares (ARCHITECTURE.md §9.10). Laid out as
 * `projects/tender-types` (Active/Deleted tabs, every action a dialog, bulk
 * delete on both tabs); the page works whether or not a company is selected.
 *
 * Suppliers point here, so the server refuses to delete a category in use
 * (one row with a warning toast; in bulk, the rest go and the toast names the
 * kept ones). Every row stays tickable: whether a category is in use is
 * answered by the server, not by this page.
 */
export default function SupplierCategoriesIndex({
    supplierCategories,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.supplier-categories.create');
    const canUpdate = useCan('projects.supplier-categories.update');
    const canDelete = useCan('projects.supplier-categories.delete');
    const canRestore = useCan('projects.supplier-categories.restore');
    const canForceDelete = useCan('projects.supplier-categories.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(supplierCategories.data);

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
        only: ['supplierCategories', 'filters'],
        refreshOnly: ['supplierCategories', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Supplier categories" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Supplier categories"
                        description="What kind of supplier each one is — one list every company shares. A category still used by suppliers cannot be deleted; make it inactive instead."
                    />

                    {!isDeleted && canCreate && (
                        <SupplierCategoryFormDialog
                            submit={SupplierCategoryController.store.form()}
                            statuses={statuses}
                            title="New supplier category"
                            description="Every company sees it — the code and name must not be used by another supplier category, including a deleted one."
                        >
                            <Button data-test="new-supplier-category">
                                <Plus /> New supplier category
                            </Button>
                        </SupplierCategoryFormDialog>
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
                    >
                        <ConfirmActionDialog
                            submit={
                                isDeleted
                                    ? SupplierCategoryController.bulkForceDelete.form()
                                    : SupplierCategoryController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'supplier category' : 'supplier categories'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'supplier category' : 'supplier categories'}?`
                            }
                            description={
                                isDeleted
                                    ? 'They are destroyed and their codes and names become free. Their audit history stays. This cannot be undone.'
                                    : 'They move to Deleted for every company. Their codes and names stay reserved until they are permanently deleted.'
                            }
                            confirmLabel={
                                isDeleted ? 'Delete permanently' : 'Delete'
                            }
                            onSuccess={selection.clear}
                            testId="bulk-delete-supplier-categories"
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
                                data-test="bulk-delete-supplier-categories"
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
                                            aria-label="Select every supplier category on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-supplier-categories"
                                        />
                                    </th>
                                )}
                                <SortableHeader {...sortProps('code')}>
                                    Code
                                </SortableHeader>
                                <SortableHeader {...sortProps('name')}>
                                    Name
                                </SortableHeader>
                                {isDeleted ? (
                                    <th>Deleted</th>
                                ) : (
                                    <SortableHeader {...sortProps('status')}>
                                        Status
                                    </SortableHeader>
                                )}
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
                            {supplierCategories.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 5 : 4}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted supplier categories.'
                                            : 'No supplier categories match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {supplierCategories.data.map((category) => (
                                <tr key={category.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${category.name}`}
                                                checked={selection.isSelected(
                                                    category.id,
                                                )}
                                                onChange={() =>
                                                    selection.toggle(
                                                        category.id,
                                                    )
                                                }
                                                data-test="select-supplier-category"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {category.code}
                                    </td>

                                    <td className="font-medium">
                                        {category.name}
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {category.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${category.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {category.status ===
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
                                                            submit={SupplierCategoryController.restore.form(
                                                                category.id,
                                                            )}
                                                            title={`Restore ${category.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-supplier-category"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${category.name}`}
                                                                data-test="restore-supplier-category"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={SupplierCategoryController.forceDelete.form(
                                                                category.id,
                                                            )}
                                                            title={`Permanently delete ${category.name}?`}
                                                            description="The supplier category is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            testId="force-delete-supplier-category"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Permanently delete ${category.name}`}
                                                                data-test="force-delete-supplier-category"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <SupplierCategoryFormDialog
                                                            submit={SupplierCategoryController.update.form(
                                                                category.id,
                                                            )}
                                                            statuses={statuses}
                                                            category={category}
                                                            title={`Edit ${category.name}`}
                                                            description="The change shows in every company. Making a supplier category inactive keeps it on record."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${category.name}`}
                                                                data-test="edit-supplier-category"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </SupplierCategoryFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={SupplierCategoryController.destroy.form(
                                                                category.id,
                                                            )}
                                                            title={`Delete ${category.name}?`}
                                                            description="It moves to Deleted for every company. Its code and name stay reserved until it is permanently deleted."
                                                            confirmLabel="Delete"
                                                            testId="delete-supplier-category"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${category.name}`}
                                                                data-test="delete-supplier-category"
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

                <Pagination page={supplierCategories} />
            </div>
        </>
    );
}

SupplierCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Supplier categories', href: index() }],
};
