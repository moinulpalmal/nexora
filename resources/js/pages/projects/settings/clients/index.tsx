import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import ClientController from '@/actions/App/Http/Controllers/ProjectManagement/ClientController';
import Heading from '@/components/heading';
import ClientFormDialog from '@/components/projects/client-form-dialog';
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
import { index } from '@/routes/projects/settings/clients';
import type {
    ClientFilters,
    ClientListItem,
    CompanySummary,
    Filterable,
    Paginated,
    StatusOption,
} from '@/types';

type Props = {
    clients: Paginated<ClientListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: ClientFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Settings → Clients — the ERP's one customer list, kept per company
 * (ARCHITECTURE.md §9.10). Laid out as `hrm/departments` (Active/Deleted tabs,
 * every action a dialog, bulk delete on both tabs), without the spreadsheet
 * import: clients are entered by hand (owner, 2026-11-02).
 *
 * Nothing references a client yet, so every row can be deleted and ticked.
 */
export default function ClientsIndex({
    clients,
    company,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.settings.clients.create');
    const canUpdate = useCan('projects.settings.clients.update');
    const canDelete = useCan('projects.settings.clients.delete');
    const canRestore = useCan('projects.settings.clients.restore');
    const canForceDelete = useCan('projects.settings.clients.force-delete');

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(clients.data);

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
        only: ['clients', 'filters'],
        refreshOnly: ['clients', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Clients" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Clients"
                        description={
                            company === null
                                ? 'The clients of the company you are working in.'
                                : `The clients of ${company.name} — deleting one moves it to Deleted, where it can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && canCreate && (
                        <ClientFormDialog
                            submit={ClientController.store.form()}
                            statuses={statuses}
                            title="New client"
                            description={`Added to ${company.name} — the code and name must not be used by another client of this company, including a deleted one.`}
                        >
                            <Button data-test="new-client">
                                <Plus /> New client
                            </Button>
                        </ClientFormDialog>
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
                                            ? ClientController.bulkForceDelete.form()
                                            : ClientController.bulkDestroy.form()
                                    }
                                    title={
                                        isDeleted
                                            ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'client' : 'clients'}?`
                                            : `Delete ${selection.count} ${selection.count === 1 ? 'client' : 'clients'}?`
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
                                    testId="bulk-delete-clients"
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
                                        data-test="bulk-delete-clients"
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
                                                    aria-label="Select every client on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-clients"
                                                />
                                            </th>
                                        )}
                                        <SortableHeader {...sortProps('code')}>
                                            Code
                                        </SortableHeader>
                                        <SortableHeader {...sortProps('name')}>
                                            Name
                                        </SortableHeader>
                                        <th>Contact person</th>
                                        <th>Phone</th>
                                        <th>Email</th>
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
                                    {clients.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canBulkDelete ? 8 : 7}
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted clients.'
                                                    : 'No clients match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {clients.data.map((client) => (
                                        <tr key={client.id}>
                                            {canBulkDelete && (
                                                <td>
                                                    <Checkbox
                                                        aria-label={`Select ${client.name}`}
                                                        checked={selection.isSelected(
                                                            client.id,
                                                        )}
                                                        onChange={() =>
                                                            selection.toggle(
                                                                client.id,
                                                            )
                                                        }
                                                        data-test="select-client"
                                                    />
                                                </td>
                                            )}
                                            <td className="font-mono text-xs">
                                                {client.code}
                                            </td>

                                            <td className="font-medium">
                                                {client.name}
                                            </td>

                                            <td>{client.contact_person}</td>

                                            <td className="whitespace-nowrap">
                                                {client.phone}
                                            </td>

                                            <td>{client.email}</td>

                                            <td>
                                                {isDeleted ? (
                                                    <span className="text-xs text-base-content/60">
                                                        {client.deleted_at}
                                                    </span>
                                                ) : (
                                                    <span
                                                        className={`badge badge-sm ${client.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                                    >
                                                        {client.status ===
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
                                                                    submit={ClientController.restore.form(
                                                                        client.id,
                                                                    )}
                                                                    title={`Restore ${client.name}?`}
                                                                    description="It moves back to Active with the status it had."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-client"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${client.name}`}
                                                                        data-test="restore-client"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={ClientController.forceDelete.form(
                                                                        client.id,
                                                                    )}
                                                                    title={`Permanently delete ${client.name}?`}
                                                                    description="The client is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-client"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${client.name}`}
                                                                        data-test="force-delete-client"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <ClientFormDialog
                                                                    submit={ClientController.update.form(
                                                                        client.id,
                                                                    )}
                                                                    statuses={
                                                                        statuses
                                                                    }
                                                                    client={
                                                                        client
                                                                    }
                                                                    title={`Edit ${client.name}`}
                                                                    description="Making a client inactive keeps it on record."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${client.name}`}
                                                                        data-test="edit-client"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </ClientFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={ClientController.destroy.form(
                                                                        client.id,
                                                                    )}
                                                                    title={`Delete ${client.name}?`}
                                                                    description="It moves to Deleted. Its code and name stay reserved in this company until it is permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-client"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${client.name}`}
                                                                        data-test="delete-client"
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

                        <Pagination page={clients} />
                    </>
                )}
            </div>
        </>
    );
}

ClientsIndex.layout = {
    breadcrumbs: [{ title: 'Clients', href: index() }],
};
