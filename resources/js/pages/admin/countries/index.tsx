import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import CountryController from '@/actions/App/Http/Controllers/Admin/CountryController';
import CountryFormDialog from '@/components/admin/country-form-dialog';
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
import { index } from '@/routes/admin/countries';
import type {
    CountryFilters,
    CountryListItem,
    Filterable,
    Paginated,
    StatusOption,
} from '@/types';

type Props = {
    countries: Paginated<CountryListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: CountryFilters;
    counts: { active: number; trashed: number };
};

/**
 * Admin → Countries — a group-wide list seeded with the ISO 3166-1 countries
 * (ARCHITECTURE.md §9.14). Laid out as `hrm/designations` (Active/Deleted
 * tabs, every action a dialog, bulk delete on both tabs) without the
 * spreadsheet import.
 */
export default function CountriesIndex({
    countries,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('admin.countries.create');
    const canUpdate = useCan('admin.countries.update');
    const canDelete = useCan('admin.countries.delete');
    const canRestore = useCan('admin.countries.restore');
    const canForceDelete = useCan('admin.countries.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(countries.data);

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
        only: ['countries', 'filters'],
        refreshOnly: ['countries', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Countries" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Countries"
                        description="The ISO countries every company shares — deactivate one to stop offering it; deleting moves it to Deleted, where it can be restored."
                    />

                    {!isDeleted && canCreate && (
                        <CountryFormDialog
                            submit={CountryController.store.form()}
                            statuses={statuses}
                            title="New country"
                            description="Shared by every company — the name and ISO codes must not be used by another country, including a deleted one."
                        >
                            <Button data-test="new-country">
                                <Plus /> New country
                            </Button>
                        </CountryFormDialog>
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
                                    ? CountryController.bulkForceDelete.form()
                                    : CountryController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'country' : 'countries'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'country' : 'countries'}?`
                            }
                            description={
                                isDeleted
                                    ? 'They are destroyed and their names and ISO codes become free. Their audit history stays. This cannot be undone.'
                                    : 'They move to Deleted for every company. Their names and ISO codes stay reserved until they are permanently deleted.'
                            }
                            confirmLabel={
                                isDeleted ? 'Delete permanently' : 'Delete'
                            }
                            onSuccess={selection.clear}
                            testId="bulk-delete-countries"
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
                                data-test="bulk-delete-countries"
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
                                            aria-label="Select every country on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-countries"
                                        />
                                    </th>
                                )}
                                <SortableHeader {...sortProps('iso2')}>
                                    ISO 2
                                </SortableHeader>
                                <SortableHeader {...sortProps('iso3')}>
                                    ISO 3
                                </SortableHeader>
                                <SortableHeader {...sortProps('name')}>
                                    Name
                                </SortableHeader>
                                <SortableHeader {...sortProps('dial_code')}>
                                    Dial code
                                </SortableHeader>
                                <SortableHeader {...sortProps('currency_code')}>
                                    Currency
                                </SortableHeader>
                                <th>Nationality</th>
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
                                        column: 'iso2',
                                        label: 'ISO 2 code',
                                    },
                                    {
                                        type: 'text',
                                        column: 'iso3',
                                        label: 'ISO 3 code',
                                    },
                                    {
                                        type: 'text',
                                        column: 'name',
                                        label: 'name',
                                    },
                                    {
                                        type: 'text',
                                        column: 'dial_code',
                                        label: 'dial code',
                                    },
                                    {
                                        type: 'text',
                                        column: 'currency_code',
                                        label: 'currency',
                                    },
                                    {
                                        type: 'text',
                                        column: 'nationality',
                                        label: 'nationality',
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
                            {countries.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 9 : 8}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted countries.'
                                            : 'No countries match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {countries.data.map((country) => (
                                <tr key={country.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${country.name}`}
                                                checked={selection.isSelected(
                                                    country.id,
                                                )}
                                                onChange={() =>
                                                    selection.toggle(country.id)
                                                }
                                                data-test="select-country"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {country.iso2}
                                    </td>
                                    <td className="font-mono text-xs">
                                        {country.iso3}
                                    </td>
                                    <td className="font-medium">
                                        {country.name}
                                    </td>
                                    <td className="font-mono text-xs">
                                        {country.dial_code}
                                    </td>
                                    <td className="font-mono text-xs">
                                        {country.currency_code}
                                    </td>
                                    <td>{country.nationality}</td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {country.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${country.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {country.status ===
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
                                                            submit={CountryController.restore.form(
                                                                country.id,
                                                            )}
                                                            title={`Restore ${country.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-country"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${country.name}`}
                                                                data-test="restore-country"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={CountryController.forceDelete.form(
                                                                country.id,
                                                            )}
                                                            title={`Permanently delete ${country.name}?`}
                                                            description="The country is destroyed and its name and ISO codes become free. Its audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            testId="force-delete-country"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Permanently delete ${country.name}`}
                                                                data-test="force-delete-country"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <CountryFormDialog
                                                            submit={CountryController.update.form(
                                                                country.id,
                                                            )}
                                                            statuses={statuses}
                                                            country={country}
                                                            title={`Edit ${country.name}`}
                                                            description="The change shows in every company. Making a country inactive keeps it on record."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${country.name}`}
                                                                data-test="edit-country"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </CountryFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={CountryController.destroy.form(
                                                                country.id,
                                                            )}
                                                            title={`Delete ${country.name}?`}
                                                            description="It moves to Deleted for every company. Its name and ISO codes stay reserved until it is permanently deleted."
                                                            confirmLabel="Delete"
                                                            testId="delete-country"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${country.name}`}
                                                                data-test="delete-country"
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

                <Pagination page={countries} />
            </div>
        </>
    );
}

CountriesIndex.layout = {
    breadcrumbs: [{ title: 'Countries', href: index() }],
};
