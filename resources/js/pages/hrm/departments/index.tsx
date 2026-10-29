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
import DepartmentController from '@/actions/App/Http/Controllers/Hrm/DepartmentController';
import DepartmentImportController from '@/actions/App/Http/Controllers/Hrm/DepartmentImportController';
import Heading from '@/components/heading';
import DepartmentFormDialog from '@/components/hrm/department-form-dialog';
import NoCompanyPanel from '@/components/hrm/no-company-panel';
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
import { index } from '@/routes/hrm/departments';
import type {
    CompanySummary,
    DepartmentFilters,
    DepartmentListItem,
    DuplicateActionOption,
    Filterable,
    ImportField,
    Paginated,
    SheetImportPending,
    StatusOption,
} from '@/types';

type Props = {
    departments: Paginated<DepartmentListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: DepartmentFilters;
    counts: { active: number; trashed: number };
    /** Empty unless the viewer holds `hrm.departments.import`. */
    importFields: ImportField[];
    duplicateActions: DuplicateActionOption[];
    maxFileSizeKb: number;
    pendingImport: SheetImportPending | null;
};

/**
 * HRM → Departments — the first company-scoped list (ARCHITECTURE.md §9.10).
 * Every row is the selected company's; with none selected the table gives way
 * to a panel saying so, and create and import are hidden.
 *
 * Laid out as `admin/users` (Active/Deleted tabs, every action a dialog),
 * plus the spreadsheet import (§9.13): a row writer, so it has a review step
 * and a rejected-rows download.
 *
 * **Bulk delete** on both tabs, as `admin/companies`: the header box ticks
 * every row on this page; the Active tab moves them to Deleted, the Deleted tab
 * removes them for good.
 */
export default function DepartmentsIndex({
    departments,
    company,
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
    const canCreate = useCan('hrm.departments.create');
    const canUpdate = useCan('hrm.departments.update');
    const canDelete = useCan('hrm.departments.delete');
    const canRestore = useCan('hrm.departments.restore');
    const canForceDelete = useCan('hrm.departments.force-delete');
    const canImport = useCan('hrm.departments.import') && company !== null;

    const [importOpen, setImportOpen] = useState(false);

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(departments.data);

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
        only: ['departments', 'filters'],
        refreshOnly: ['departments', 'filters', 'counts', 'pendingImport'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Departments" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Departments"
                        description={
                            company === null
                                ? 'The departments of the company you are working in.'
                                : `The departments of ${company.name} — deleting one moves it to Deleted, where it can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && (
                        <div className="flex flex-wrap items-center gap-2">
                            {canImport && (
                                <>
                                    <Button variant="outline" asChild>
                                        <a
                                            href={DepartmentImportController.template.url()}
                                            data-test="department-import-template"
                                        >
                                            <Download /> Template
                                        </a>
                                    </Button>

                                    <Button
                                        variant="outline"
                                        onClick={() => setImportOpen(true)}
                                        data-test="import-departments"
                                    >
                                        <Upload /> Import
                                    </Button>
                                </>
                            )}

                            {canCreate && (
                                <DepartmentFormDialog
                                    submit={DepartmentController.store.form()}
                                    statuses={statuses}
                                    title="New department"
                                    description={`Added to ${company.name} — the code and name must not be used by another department of this company, including a deleted one.`}
                                >
                                    <Button data-test="new-department">
                                        <Plus /> New department
                                    </Button>
                                </DepartmentFormDialog>
                            )}
                        </div>
                    )}
                </div>

                {company === null ? (
                    <NoCompanyPanel />
                ) : (
                    <>
                        {canImport && (
                            <>
                                <SheetImportDialog
                                    resourceLabel="departments"
                                    uploadDescription={
                                        <>
                                            One row per department, with a{' '}
                                            <strong>Code</strong>, a{' '}
                                            <strong>Name</strong> and optionally
                                            a <strong>Status</strong> (Active or
                                            Inactive). The rows go into{' '}
                                            {company.name} (the company selected
                                            in the header).
                                        </>
                                    }
                                    duplicateLabel="A row whose code or name matches an existing department"
                                    submit={DepartmentImportController.store.form()}
                                    confirmSubmit={(id) =>
                                        DepartmentImportController.confirm.form(
                                            id,
                                        )
                                    }
                                    reviewUrl={(id) =>
                                        DepartmentImportController.review.url(
                                            id,
                                        )
                                    }
                                    fields={importFields}
                                    duplicateActions={duplicateActions}
                                    maxFileSizeKb={maxFileSizeKb}
                                    pendingImport={pendingImport}
                                    open={importOpen}
                                    onOpenChange={setImportOpen}
                                    testPrefix="department"
                                />

                                {pendingImport && !importOpen && (
                                    <PendingImportBanner
                                        pendingImport={pendingImport}
                                        cancelSubmit={DepartmentImportController.cancel.form(
                                            pendingImport.id,
                                        )}
                                        onResume={() => setImportOpen(true)}
                                        testPrefix="department"
                                    />
                                )}

                                <ImportResultPanel
                                    rejectsUrl={(id) =>
                                        DepartmentImportController.rejects.url(
                                            id,
                                        )
                                    }
                                    testPrefix="department"
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
                                            ? DepartmentController.bulkForceDelete.form()
                                            : DepartmentController.bulkDestroy.form()
                                    }
                                    title={
                                        isDeleted
                                            ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'department' : 'departments'}?`
                                            : `Delete ${selection.count} ${selection.count === 1 ? 'department' : 'departments'}?`
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
                                    testId="bulk-delete-departments"
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
                                        data-test="bulk-delete-departments"
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
                                                    aria-label="Select every department on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-departments"
                                                />
                                            </th>
                                        )}
                                        <SortableHeader {...sortProps('code')}>
                                            Code
                                        </SortableHeader>
                                        <SortableHeader {...sortProps('name')}>
                                            Name
                                        </SortableHeader>
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
                                    {departments.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={canBulkDelete ? 5 : 4}
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted departments.'
                                                    : 'No departments match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {departments.data.map((department) => (
                                        <tr key={department.id}>
                                            {canBulkDelete && (
                                                <td>
                                                    <Checkbox
                                                        aria-label={`Select ${department.name}`}
                                                        checked={selection.isSelected(
                                                            department.id,
                                                        )}
                                                        onChange={() =>
                                                            selection.toggle(
                                                                department.id,
                                                            )
                                                        }
                                                        data-test="select-department"
                                                    />
                                                </td>
                                            )}
                                            <td className="font-mono text-xs">
                                                {department.code}
                                            </td>

                                            <td className="font-medium">
                                                {department.name}
                                            </td>

                                            <td>
                                                {isDeleted ? (
                                                    <span className="text-xs text-base-content/60">
                                                        {department.deleted_at}
                                                    </span>
                                                ) : (
                                                    <span
                                                        className={`badge badge-sm ${department.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                                    >
                                                        {department.status ===
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
                                                                    submit={DepartmentController.restore.form(
                                                                        department.id,
                                                                    )}
                                                                    title={`Restore ${department.name}?`}
                                                                    description="It moves back to Active with the status it had."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-department"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${department.name}`}
                                                                        data-test="restore-department"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={DepartmentController.forceDelete.form(
                                                                        department.id,
                                                                    )}
                                                                    title={`Permanently delete ${department.name}?`}
                                                                    description="The department is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-department"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${department.name}`}
                                                                        data-test="force-delete-department"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <DepartmentFormDialog
                                                                    submit={DepartmentController.update.form(
                                                                        department.id,
                                                                    )}
                                                                    statuses={
                                                                        statuses
                                                                    }
                                                                    department={
                                                                        department
                                                                    }
                                                                    title={`Edit ${department.name}`}
                                                                    description="Making a department inactive keeps it on record."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${department.name}`}
                                                                        data-test="edit-department"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </DepartmentFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={DepartmentController.destroy.form(
                                                                        department.id,
                                                                    )}
                                                                    title={`Delete ${department.name}?`}
                                                                    description="It moves to Deleted. Its code and name stay reserved in this company until it is permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-department"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${department.name}`}
                                                                        data-test="delete-department"
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

                        <Pagination page={departments} />
                    </>
                )}
            </div>
        </>
    );
}

DepartmentsIndex.layout = {
    breadcrumbs: [{ title: 'Departments', href: index() }],
};
