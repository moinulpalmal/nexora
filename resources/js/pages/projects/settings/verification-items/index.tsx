import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import VerificationItemController from '@/actions/App/Http/Controllers/ProjectManagement/VerificationItemController';
import Heading from '@/components/heading';
import VerificationItemFormDialog from '@/components/projects/verification-item-form-dialog';
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
import { index } from '@/routes/projects/verification-items';
import type {
    CompanySummary,
    Filterable,
    Paginated,
    StatusOption,
    VerificationItemFilters,
    VerificationItemListItem,
} from '@/types';

type Props = {
    items: Paginated<VerificationItemListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: VerificationItemFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Verification Items — kept per company (ARCHITECTURE.md §9.10),
 * laid out as `projects/product-categories` (Active/Deleted tabs, every action
 * a dialog, bulk delete on both tabs) without a code column and without a
 * spreadsheet import (owner, 2026-11-03).
 *
 * Nothing references an item yet, so every row can be deleted.
 */
export default function VerificationItemsIndex({
    items,
    company,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.verification-items.create');
    const canUpdate = useCan('projects.verification-items.update');
    const canDelete = useCan('projects.verification-items.delete');
    const canRestore = useCan('projects.verification-items.restore');
    const canForceDelete = useCan('projects.verification-items.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(items.data);

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
        only: ['items', 'filters'],
        refreshOnly: ['items', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    const plural = (count: number) =>
        count === 1 ? 'verification item' : 'verification items';

    return (
        <>
            <Head title="Verification items" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Verification items"
                        description={
                            company === null
                                ? 'The verification items of the company you are working in.'
                                : `The verification items of ${company.name} — deleting one moves it to Deleted, where it can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && canCreate && (
                        <VerificationItemFormDialog
                            submit={VerificationItemController.store.form()}
                            statuses={statuses}
                            title="New verification item"
                            description={`Added to ${company.name} — the name must not be used by another verification item of this company, including a deleted one.`}
                        >
                            <Button data-test="new-verification-item">
                                <Plus /> New verification item
                            </Button>
                        </VerificationItemFormDialog>
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
                                            ? VerificationItemController.bulkForceDelete.form()
                                            : VerificationItemController.bulkDestroy.form()
                                    }
                                    title={
                                        isDeleted
                                            ? `Permanently delete ${selection.count} ${plural(selection.count)}?`
                                            : `Delete ${selection.count} ${plural(selection.count)}?`
                                    }
                                    description={
                                        isDeleted
                                            ? 'They are destroyed and their names become free in this company. Their audit history stays. This cannot be undone.'
                                            : 'They move to Deleted. Their names stay reserved in this company until they are permanently deleted.'
                                    }
                                    confirmLabel={
                                        isDeleted
                                            ? 'Delete permanently'
                                            : 'Delete'
                                    }
                                    onSuccess={selection.clear}
                                    testId="bulk-delete-verification-items"
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
                                        data-test="bulk-delete-verification-items"
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
                                                    aria-label="Select every verification item on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-verification-items"
                                                />
                                            </th>
                                        )}
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
                                    {items.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canBulkDelete ? 4 : 3}
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted verification items.'
                                                    : 'No verification items match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {items.data.map((item) => (
                                        <tr key={item.id}>
                                            {canBulkDelete && (
                                                <td>
                                                    <Checkbox
                                                        aria-label={`Select ${item.name}`}
                                                        checked={selection.isSelected(
                                                            item.id,
                                                        )}
                                                        onChange={() =>
                                                            selection.toggle(
                                                                item.id,
                                                            )
                                                        }
                                                        data-test="select-verification-item"
                                                    />
                                                </td>
                                            )}
                                            <td className="font-medium">
                                                {item.name}
                                            </td>

                                            <td>
                                                {isDeleted ? (
                                                    <span className="text-xs text-base-content/60">
                                                        {item.deleted_at}
                                                    </span>
                                                ) : (
                                                    <span
                                                        className={`badge badge-sm ${item.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                                    >
                                                        {item.status ===
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
                                                                    submit={VerificationItemController.restore.form(
                                                                        item.id,
                                                                    )}
                                                                    title={`Restore ${item.name}?`}
                                                                    description="It moves back to Active with the status it had."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-verification-item"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${item.name}`}
                                                                        data-test="restore-verification-item"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={VerificationItemController.forceDelete.form(
                                                                        item.id,
                                                                    )}
                                                                    title={`Permanently delete ${item.name}?`}
                                                                    description="The verification item is destroyed and its name becomes free. Its audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-verification-item"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${item.name}`}
                                                                        data-test="force-delete-verification-item"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <VerificationItemFormDialog
                                                                    submit={VerificationItemController.update.form(
                                                                        item.id,
                                                                    )}
                                                                    statuses={
                                                                        statuses
                                                                    }
                                                                    item={item}
                                                                    title={`Edit ${item.name}`}
                                                                    description="Making a verification item inactive keeps it on record."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${item.name}`}
                                                                        data-test="edit-verification-item"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </VerificationItemFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={VerificationItemController.destroy.form(
                                                                        item.id,
                                                                    )}
                                                                    title={`Delete ${item.name}?`}
                                                                    description="It moves to Deleted. Its name stays reserved in this company until it is permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-verification-item"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${item.name}`}
                                                                        data-test="delete-verification-item"
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

                        <Pagination page={items} />
                    </>
                )}
            </div>
        </>
    );
}

VerificationItemsIndex.layout = {
    breadcrumbs: [{ title: 'Verification items', href: index() }],
};
