import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import TenderTypeController from '@/actions/App/Http/Controllers/ProjectManagement/TenderTypeController';
import Heading from '@/components/heading';
import TenderTypeFormDialog from '@/components/projects/tender-type-form-dialog';
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
import { index } from '@/routes/projects/settings/tender-types';
import type {
    Filterable,
    Paginated,
    StatusOption,
    TenderTypeFilters,
    TenderTypeListItem,
} from '@/types';

type Props = {
    tenderTypes: Paginated<TenderTypeListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: TenderTypeFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Settings → Tender Types — how a project was procured, one list every company
 * shares (ARCHITECTURE.md §9.10). Laid out as `projects/settings/clients` (Active/Deleted
 * tabs, every action a dialog, bulk delete on both tabs) without the company:
 * the page works whether or not one is selected, as `hrm/designations`.
 *
 * Nothing references a tender type yet, so every row can be deleted and
 * ticked.
 */
export default function TenderTypesIndex({
    tenderTypes,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.settings.tender-types.create');
    const canUpdate = useCan('projects.settings.tender-types.update');
    const canDelete = useCan('projects.settings.tender-types.delete');
    const canRestore = useCan('projects.settings.tender-types.restore');
    const canForceDelete = useCan(
        'projects.settings.tender-types.force-delete',
    );

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(tenderTypes.data);

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
        only: ['tenderTypes', 'filters'],
        refreshOnly: ['tenderTypes', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Tender types" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Tender types"
                        description="How a project was procured — one list every company shares. Deleting one moves it to Deleted, where it can be restored."
                    />

                    {!isDeleted && canCreate && (
                        <TenderTypeFormDialog
                            submit={TenderTypeController.store.form()}
                            statuses={statuses}
                            title="New tender type"
                            description="Every company sees it — the code and name must not be used by another tender type, including a deleted one."
                        >
                            <Button data-test="new-tender-type">
                                <Plus /> New tender type
                            </Button>
                        </TenderTypeFormDialog>
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
                                    ? TenderTypeController.bulkForceDelete.form()
                                    : TenderTypeController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'tender type' : 'tender types'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'tender type' : 'tender types'}?`
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
                            testId="bulk-delete-tender-types"
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
                                data-test="bulk-delete-tender-types"
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
                                            aria-label="Select every tender type on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-tender-types"
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
                            {tenderTypes.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 5 : 4}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted tender types.'
                                            : 'No tender types match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {tenderTypes.data.map((tenderType) => (
                                <tr key={tenderType.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${tenderType.name}`}
                                                checked={selection.isSelected(
                                                    tenderType.id,
                                                )}
                                                onChange={() =>
                                                    selection.toggle(
                                                        tenderType.id,
                                                    )
                                                }
                                                data-test="select-tender-type"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {tenderType.code}
                                    </td>

                                    <td className="font-medium">
                                        {tenderType.name}
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {tenderType.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${tenderType.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {tenderType.status ===
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
                                                            submit={TenderTypeController.restore.form(
                                                                tenderType.id,
                                                            )}
                                                            title={`Restore ${tenderType.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-tender-type"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${tenderType.name}`}
                                                                data-test="restore-tender-type"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={TenderTypeController.forceDelete.form(
                                                                tenderType.id,
                                                            )}
                                                            title={`Permanently delete ${tenderType.name}?`}
                                                            description="The tender type is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            testId="force-delete-tender-type"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Permanently delete ${tenderType.name}`}
                                                                data-test="force-delete-tender-type"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <TenderTypeFormDialog
                                                            submit={TenderTypeController.update.form(
                                                                tenderType.id,
                                                            )}
                                                            statuses={statuses}
                                                            tenderType={
                                                                tenderType
                                                            }
                                                            title={`Edit ${tenderType.name}`}
                                                            description="The change shows in every company. Making a tender type inactive keeps it on record."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${tenderType.name}`}
                                                                data-test="edit-tender-type"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </TenderTypeFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={TenderTypeController.destroy.form(
                                                                tenderType.id,
                                                            )}
                                                            title={`Delete ${tenderType.name}?`}
                                                            description="It moves to Deleted for every company. Its code and name stay reserved until it is permanently deleted."
                                                            confirmLabel="Delete"
                                                            testId="delete-tender-type"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${tenderType.name}`}
                                                                data-test="delete-tender-type"
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

                <Pagination page={tenderTypes} />
            </div>
        </>
    );
}

TenderTypesIndex.layout = {
    breadcrumbs: [{ title: 'Tender types', href: index() }],
};
