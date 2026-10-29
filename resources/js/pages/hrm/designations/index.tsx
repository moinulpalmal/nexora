import { Head } from '@inertiajs/react';
import {
    Download,
    Pencil,
    Plus,
    RotateCcw,
    Trash2,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import DesignationController from '@/actions/App/Http/Controllers/Hrm/DesignationController';
import DesignationImportController from '@/actions/App/Http/Controllers/Hrm/DesignationImportController';
import Heading from '@/components/heading';
import DesignationFormDialog from '@/components/hrm/designation-form-dialog';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ConfirmActionDialog from '@/components/shared/confirm-action-dialog';
import ImportResultPanel from '@/components/shared/import-result-panel';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import PendingImportBanner from '@/components/shared/pending-import-banner';
import SheetImportDialog from '@/components/shared/sheet-import-dialog';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useCan } from '@/hooks/use-can';
import { useListFilters } from '@/hooks/use-list-filters';
import { useRowSelection } from '@/hooks/use-row-selection';
import { RECORD_STATUS } from '@/lib/enums';
import { index } from '@/routes/hrm/designations';
import type {
    DesignationFilters,
    DesignationListItem,
    DuplicateActionOption,
    Filterable,
    ImportField,
    Paginated,
    SheetImportPending,
    StatusOption,
} from '@/types';

type Props = {
    designations: Paginated<DesignationListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: DesignationFilters;
    counts: { active: number; trashed: number };
    /** Empty unless the viewer holds `hrm.designations.import`. */
    importFields: ImportField[];
    duplicateActions: DuplicateActionOption[];
    maxFileSizeKb: number;
    pendingImport: SheetImportPending | null;
};

/**
 * HRM → Designations — a group-wide list (ARCHITECTURE.md §9.10): every
 * company shares it, so unlike `hrm/departments` there is no company and no
 * no-company panel.
 *
 * Laid out as `hrm/departments` (Active/Deleted tabs, every action a dialog,
 * bulk delete on both tabs) plus the spreadsheet import (§9.13), a row writer
 * with a review step and a rejected-rows download.
 */
export default function DesignationsIndex({
    designations,
    statuses,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
    importFields,
    duplicateActions,
    maxFileSizeKb,
    pendingImport,
}: Props) {
    const canCreate = useCan('hrm.designations.create');
    const canUpdate = useCan('hrm.designations.update');
    const canDelete = useCan('hrm.designations.delete');
    const canRestore = useCan('hrm.designations.restore');
    const canForceDelete = useCan('hrm.designations.force-delete');
    const canImport = useCan('hrm.designations.import');

    const [importOpen, setImportOpen] = useState(false);

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(designations.data);

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
        only: ['designations', 'filters'],
        refreshOnly: ['designations', 'filters', 'counts', 'pendingImport'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Designations" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Designations"
                        description="The job titles every company shares — deleting one moves it to Deleted, where it can be restored."
                    />

                    {!isDeleted && (
                        <div className="flex flex-wrap items-center gap-2">
                            {canImport && (
                                <>
                                    <Button variant="outline" asChild>
                                        <a
                                            href={DesignationImportController.template.url()}
                                            data-test="designation-import-template"
                                        >
                                            <Download /> Template
                                        </a>
                                    </Button>

                                    <Button
                                        variant="outline"
                                        onClick={() => setImportOpen(true)}
                                        data-test="import-designations"
                                    >
                                        <Upload /> Import
                                    </Button>
                                </>
                            )}

                            {canCreate && (
                                <DesignationFormDialog
                                    submit={DesignationController.store.form()}
                                    statuses={statuses}
                                    title="New designation"
                                    description="Shared by every company — the code and name must not be used by another designation, including a deleted one."
                                >
                                    <Button data-test="new-designation">
                                        <Plus /> New designation
                                    </Button>
                                </DesignationFormDialog>
                            )}
                        </div>
                    )}
                </div>

                {canImport && (
                    <>
                        <SheetImportDialog
                            resourceLabel="designations"
                            uploadDescription={
                                <>
                                    One row per designation, with a{' '}
                                    <strong>Code</strong>, a{' '}
                                    <strong>Name</strong> and optionally a{' '}
                                    <strong>Status</strong> (Active or
                                    Inactive). Designations are shared by every
                                    company.
                                </>
                            }
                            duplicateLabel="A row whose code or name matches an existing designation"
                            submit={DesignationImportController.store.form()}
                            confirmSubmit={(id) =>
                                DesignationImportController.confirm.form(id)
                            }
                            reviewUrl={(id) =>
                                DesignationImportController.review.url(id)
                            }
                            fields={importFields}
                            duplicateActions={duplicateActions}
                            maxFileSizeKb={maxFileSizeKb}
                            pendingImport={pendingImport}
                            open={importOpen}
                            onOpenChange={setImportOpen}
                            testPrefix="designation"
                        />

                        {pendingImport && !importOpen && (
                            <PendingImportBanner
                                pendingImport={pendingImport}
                                cancelSubmit={DesignationImportController.cancel.form(
                                    pendingImport.id,
                                )}
                                onResume={() => setImportOpen(true)}
                                testPrefix="designation"
                            />
                        )}

                        <ImportResultPanel
                            rejectsUrl={(id) =>
                                DesignationImportController.rejects.url(id)
                            }
                            testPrefix="designation"
                        />
                    </>
                )}

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
                                    ? DesignationController.bulkForceDelete.form()
                                    : DesignationController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'designation' : 'designations'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'designation' : 'designations'}?`
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
                            testId="bulk-delete-designations"
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
                                data-test="bulk-delete-designations"
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
                                            aria-label="Select every designation on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-designations"
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
                            {designations.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 5 : 4}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted designations.'
                                            : 'No designations match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {designations.data.map((designation) => (
                                <tr key={designation.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${designation.name}`}
                                                checked={selection.isSelected(
                                                    designation.id,
                                                )}
                                                onChange={() =>
                                                    selection.toggle(
                                                        designation.id,
                                                    )
                                                }
                                                data-test="select-designation"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {designation.code}
                                    </td>

                                    <td className="font-medium">
                                        {designation.name}
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {designation.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${designation.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {designation.status ===
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
                                                            submit={DesignationController.restore.form(
                                                                designation.id,
                                                            )}
                                                            title={`Restore ${designation.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-designation"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${designation.name}`}
                                                                data-test="restore-designation"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={DesignationController.forceDelete.form(
                                                                designation.id,
                                                            )}
                                                            title={`Permanently delete ${designation.name}?`}
                                                            description="The designation is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            testId="force-delete-designation"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Permanently delete ${designation.name}`}
                                                                data-test="force-delete-designation"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <DesignationFormDialog
                                                            submit={DesignationController.update.form(
                                                                designation.id,
                                                            )}
                                                            statuses={statuses}
                                                            designation={
                                                                designation
                                                            }
                                                            title={`Edit ${designation.name}`}
                                                            description="The change shows in every company. Making a designation inactive keeps it on record."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${designation.name}`}
                                                                data-test="edit-designation"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </DesignationFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={DesignationController.destroy.form(
                                                                designation.id,
                                                            )}
                                                            title={`Delete ${designation.name}?`}
                                                            description="It moves to Deleted for every company. Its code and name stay reserved until it is permanently deleted."
                                                            confirmLabel="Delete"
                                                            testId="delete-designation"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${designation.name}`}
                                                                data-test="delete-designation"
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

                <Pagination page={designations} />
            </div>
        </>
    );
}

DesignationsIndex.layout = {
    breadcrumbs: [{ title: 'Designations', href: index() }],
};
