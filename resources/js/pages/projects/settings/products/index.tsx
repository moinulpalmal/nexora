import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import ProductController from '@/actions/App/Http/Controllers/ProjectManagement/ProductController';
import Heading from '@/components/heading';
import ProductFormDialog from '@/components/projects/product-form-dialog';
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
import { index } from '@/routes/projects/products';
import type {
    CompanySummary,
    Filterable,
    Paginated,
    ProductCategoryOption,
    ProductFilters,
    ProductListItem,
    StatusOption,
} from '@/types';

type Props = {
    products: Paginated<ProductListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    /** The company's product categories, inactive ones marked — the form and the filter. */
    categories: ProductCategoryOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: ProductFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Products — kept per company (ARCHITECTURE.md §9.10), laid out as
 * `projects/product-categories` (Active/Deleted tabs, every action a dialog,
 * bulk delete on both tabs) with the category column and filter of
 * `projects/certifications`, without a spreadsheet import.
 *
 * Nothing references a product yet, so every row can be deleted and ticked.
 */
export default function ProductsIndex({
    products,
    company,
    statuses,
    categories,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.products.create');
    const canUpdate = useCan('projects.products.update');
    const canDelete = useCan('projects.products.delete');
    const canRestore = useCan('projects.products.restore');
    const canForceDelete = useCan('projects.products.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(products.data);

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
        only: ['products', 'filters'],
        refreshOnly: ['products', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    const plural = (count: number) => (count === 1 ? 'product' : 'products');

    return (
        <>
            <Head title="Products" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Products"
                        description={
                            company === null
                                ? 'The products of the company you are working in.'
                                : `The products of ${company.name} — deleting one moves it to Deleted, where it can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && canCreate && (
                        <ProductFormDialog
                            submit={ProductController.store.form()}
                            statuses={statuses}
                            categories={categories}
                            title="New product"
                            description={`Added to ${company.name} — the code and name must not be used by another product of this company, including a deleted one.`}
                        >
                            <Button data-test="new-product">
                                <Plus /> New product
                            </Button>
                        </ProductFormDialog>
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
                                            ? ProductController.bulkForceDelete.form()
                                            : ProductController.bulkDestroy.form()
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
                                    testId="bulk-delete-products"
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
                                        data-test="bulk-delete-products"
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
                                                    aria-label="Select every product on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-products"
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
                                            {
                                                type: 'select',
                                                column: 'product_category_id',
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
                                    {products.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canBulkDelete ? 6 : 5}
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted products.'
                                                    : 'No products match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {products.data.map((product) => (
                                        <tr key={product.id}>
                                            {canBulkDelete && (
                                                <td>
                                                    <Checkbox
                                                        aria-label={`Select ${product.name}`}
                                                        checked={selection.isSelected(
                                                            product.id,
                                                        )}
                                                        onChange={() =>
                                                            selection.toggle(
                                                                product.id,
                                                            )
                                                        }
                                                        data-test="select-product"
                                                    />
                                                </td>
                                            )}
                                            <td className="font-mono text-xs">
                                                {product.code}
                                            </td>

                                            <td className="font-medium">
                                                {product.name}
                                            </td>

                                            <td>{product.category_name}</td>

                                            <td>
                                                {isDeleted ? (
                                                    <span className="text-xs text-base-content/60">
                                                        {product.deleted_at}
                                                    </span>
                                                ) : (
                                                    <span
                                                        className={`badge badge-sm ${product.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                                    >
                                                        {product.status ===
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
                                                                    submit={ProductController.restore.form(
                                                                        product.id,
                                                                    )}
                                                                    title={`Restore ${product.name}?`}
                                                                    description="It moves back to Active with the status it had."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-product"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${product.name}`}
                                                                        data-test="restore-product"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={ProductController.forceDelete.form(
                                                                        product.id,
                                                                    )}
                                                                    title={`Permanently delete ${product.name}?`}
                                                                    description="The product is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-product"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${product.name}`}
                                                                        data-test="force-delete-product"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <ProductFormDialog
                                                                    submit={ProductController.update.form(
                                                                        product.id,
                                                                    )}
                                                                    statuses={
                                                                        statuses
                                                                    }
                                                                    categories={
                                                                        categories
                                                                    }
                                                                    product={
                                                                        product
                                                                    }
                                                                    title={`Edit ${product.name}`}
                                                                    description="Making a product inactive keeps it on record."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${product.name}`}
                                                                        data-test="edit-product"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </ProductFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={ProductController.destroy.form(
                                                                        product.id,
                                                                    )}
                                                                    title={`Delete ${product.name}?`}
                                                                    description="It moves to Deleted. Its code and name stay reserved in this company until it is permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-product"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${product.name}`}
                                                                        data-test="delete-product"
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

                        <Pagination page={products} />
                    </>
                )}
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Products', href: index() }],
};
