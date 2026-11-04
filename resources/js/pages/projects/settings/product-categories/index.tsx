import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import ProductCategoryController from '@/actions/App/Http/Controllers/ProjectManagement/ProductCategoryController';
import Heading from '@/components/heading';
import ProductCategoryFormDialog from '@/components/projects/product-category-form-dialog';
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
import { index } from '@/routes/projects/product-categories';
import type {
    CompanySummary,
    Filterable,
    Paginated,
    ProductCategoryFilters,
    ProductCategoryListItem,
    StatusOption,
} from '@/types';

type Props = {
    categories: Paginated<ProductCategoryListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: ProductCategoryFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Product Categories — kept per company (ARCHITECTURE.md §9.10),
 * laid out as `projects/clients` (Active/Deleted tabs, every action a dialog,
 * bulk delete on both tabs), without a spreadsheet import. Every company starts
 * with a starter set (owner, 2026-11-03).
 *
 * Every row can be ticked; the server refuses to delete a category any product
 * is in, and names it in a warning toast (bulk delete deletes the rest).
 */
export default function ProductCategoriesIndex({
    categories,
    company,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.product-categories.create');
    const canUpdate = useCan('projects.product-categories.update');
    const canDelete = useCan('projects.product-categories.delete');
    const canRestore = useCan('projects.product-categories.restore');
    const canForceDelete = useCan('projects.product-categories.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(categories.data);

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
        only: ['categories', 'filters'],
        refreshOnly: ['categories', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    const plural = (count: number) =>
        count === 1 ? 'product category' : 'product categories';

    return (
        <>
            <Head title="Product categories" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Product categories"
                        description={
                            company === null
                                ? 'The product categories of the company you are working in.'
                                : `The product categories of ${company.name} — deleting one moves it to Deleted, where it can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && canCreate && (
                        <ProductCategoryFormDialog
                            submit={ProductCategoryController.store.form()}
                            statuses={statuses}
                            title="New product category"
                            description={`Added to ${company.name} — the code and name must not be used by another product category of this company, including a deleted one.`}
                        >
                            <Button data-test="new-product-category">
                                <Plus /> New product category
                            </Button>
                        </ProductCategoryFormDialog>
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
                                            ? ProductCategoryController.bulkForceDelete.form()
                                            : ProductCategoryController.bulkDestroy.form()
                                    }
                                    title={
                                        isDeleted
                                            ? `Permanently delete ${selection.count} ${plural(selection.count)}?`
                                            : `Delete ${selection.count} ${plural(selection.count)}?`
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
                                    testId="bulk-delete-product-categories"
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
                                        data-test="bulk-delete-product-categories"
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
                                                    aria-label="Select every product category on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-product-categories"
                                                />
                                            </th>
                                        )}
                                        <SortableHeader {...sortProps('code')}>
                                            Code
                                        </SortableHeader>
                                        <SortableHeader {...sortProps('name')}>
                                            Name
                                        </SortableHeader>
                                        <SortableHeader
                                            {...sortProps('status')}
                                        >
                                            {isDeleted ? 'Deleted' : 'Status'}
                                        </SortableHeader>
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
                                    {categories.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canBulkDelete ? 5 : 4}
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted product categories.'
                                                    : 'No product categories match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {categories.data.map((category) => (
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
                                                        data-test="select-product-category"
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
                                                                    submit={ProductCategoryController.restore.form(
                                                                        category.id,
                                                                    )}
                                                                    title={`Restore ${category.name}?`}
                                                                    description="It moves back to Active with the status it had."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-product-category"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${category.name}`}
                                                                        data-test="restore-product-category"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={ProductCategoryController.forceDelete.form(
                                                                        category.id,
                                                                    )}
                                                                    title={`Permanently delete ${category.name}?`}
                                                                    description="The product category is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-product-category"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${category.name}`}
                                                                        data-test="force-delete-product-category"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <ProductCategoryFormDialog
                                                                    submit={ProductCategoryController.update.form(
                                                                        category.id,
                                                                    )}
                                                                    statuses={
                                                                        statuses
                                                                    }
                                                                    category={
                                                                        category
                                                                    }
                                                                    title={`Edit ${category.name}`}
                                                                    description="Making a product category inactive keeps it on record."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${category.name}`}
                                                                        data-test="edit-product-category"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </ProductCategoryFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={ProductCategoryController.destroy.form(
                                                                        category.id,
                                                                    )}
                                                                    title={`Delete ${category.name}?`}
                                                                    description="It moves to Deleted. Its code and name stay reserved in this company until it is permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-product-category"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${category.name}`}
                                                                        data-test="delete-product-category"
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

                        <Pagination page={categories} />
                    </>
                )}
            </div>
        </>
    );
}

ProductCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Product categories', href: index() }],
};
