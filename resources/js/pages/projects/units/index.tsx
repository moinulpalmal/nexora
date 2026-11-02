import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import UnitController from '@/actions/App/Http/Controllers/ProjectManagement/UnitController';
import Heading from '@/components/heading';
import UnitFormDialog from '@/components/projects/unit-form-dialog';
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
import { index } from '@/routes/projects/units';
import type {
    Filterable,
    Paginated,
    StatusOption,
    UnitFilters,
    UnitListItem,
} from '@/types';

type Props = {
    units: Paginated<UnitListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: UnitFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Units of Measure — what a quantity is counted in, one list every
 * company shares (ARCHITECTURE.md §9.10). Laid out as `projects/tender-types`
 * (Active/Deleted tabs, every action a dialog, bulk delete on both tabs) plus
 * the symbol and decimal places columns; the description shows in the form
 * only, to keep the list narrow.
 *
 * Nothing references a unit yet, so every row can be deleted and ticked.
 */
export default function UnitsIndex({
    units,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.units.create');
    const canUpdate = useCan('projects.units.update');
    const canDelete = useCan('projects.units.delete');
    const canRestore = useCan('projects.units.restore');
    const canForceDelete = useCan('projects.units.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(units.data);

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
        only: ['units', 'filters'],
        refreshOnly: ['units', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Units of measure" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Units of measure"
                        description="What quantities are counted in — one list every company shares. Deleting one moves it to Deleted, where it can be restored."
                    />

                    {!isDeleted && canCreate && (
                        <UnitFormDialog
                            submit={UnitController.store.form()}
                            statuses={statuses}
                            title="New unit"
                            description="Every company sees it — the code and name must not be used by another unit, including a deleted one."
                        >
                            <Button data-test="new-unit">
                                <Plus /> New unit
                            </Button>
                        </UnitFormDialog>
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
                                    ? UnitController.bulkForceDelete.form()
                                    : UnitController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'unit' : 'units'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'unit' : 'units'}?`
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
                            testId="bulk-delete-units"
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
                                data-test="bulk-delete-units"
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
                                            aria-label="Select every unit on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-units"
                                        />
                                    </th>
                                )}
                                <SortableHeader {...sortProps('code')}>
                                    Code
                                </SortableHeader>
                                <SortableHeader {...sortProps('name')}>
                                    Name
                                </SortableHeader>
                                <SortableHeader {...sortProps('symbol')}>
                                    Symbol
                                </SortableHeader>
                                <SortableHeader
                                    {...sortProps('decimal_places')}
                                >
                                    Decimals
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
                                    {
                                        type: 'text',
                                        column: 'symbol',
                                        label: 'symbol',
                                    },
                                    { type: 'none' },
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
                            {units.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 7 : 6}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted units.'
                                            : 'No units match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {units.data.map((unit) => (
                                <tr key={unit.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${unit.name}`}
                                                checked={selection.isSelected(
                                                    unit.id,
                                                )}
                                                onChange={() =>
                                                    selection.toggle(unit.id)
                                                }
                                                data-test="select-unit"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {unit.code}
                                    </td>

                                    <td className="font-medium">{unit.name}</td>

                                    <td>{unit.symbol}</td>

                                    <td className="tabular-nums">
                                        {unit.decimal_places}
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {unit.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${unit.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {unit.status ===
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
                                                            submit={UnitController.restore.form(
                                                                unit.id,
                                                            )}
                                                            title={`Restore ${unit.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-unit"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${unit.name}`}
                                                                data-test="restore-unit"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={UnitController.forceDelete.form(
                                                                unit.id,
                                                            )}
                                                            title={`Permanently delete ${unit.name}?`}
                                                            description="The unit is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            testId="force-delete-unit"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Permanently delete ${unit.name}`}
                                                                data-test="force-delete-unit"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <UnitFormDialog
                                                            submit={UnitController.update.form(
                                                                unit.id,
                                                            )}
                                                            statuses={statuses}
                                                            unit={unit}
                                                            title={`Edit ${unit.name}`}
                                                            description="The change shows in every company. Making a unit inactive keeps it on record."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${unit.name}`}
                                                                data-test="edit-unit"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </UnitFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={UnitController.destroy.form(
                                                                unit.id,
                                                            )}
                                                            title={`Delete ${unit.name}?`}
                                                            description="It moves to Deleted for every company. Its code and name stay reserved until it is permanently deleted."
                                                            confirmLabel="Delete"
                                                            testId="delete-unit"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${unit.name}`}
                                                                data-test="delete-unit"
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

                <Pagination page={units} />
            </div>
        </>
    );
}

UnitsIndex.layout = {
    breadcrumbs: [{ title: 'Units of measure', href: index() }],
};
