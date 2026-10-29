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
import EmployeeController from '@/actions/App/Http/Controllers/Hrm/EmployeeController';
import EmployeeImportController from '@/actions/App/Http/Controllers/Hrm/EmployeeImportController';
import Heading from '@/components/heading';
import EmployeeFormDialog from '@/components/hrm/employee-form-dialog';
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
import { index } from '@/routes/hrm/employees';
import type {
    CompanySummary,
    DuplicateActionOption,
    EmployeeFilters,
    EmployeeListItem,
    EmployeeLookupOption,
    Filterable,
    ImportField,
    Paginated,
    SheetImportPending,
    StatusOption,
} from '@/types';

type Props = {
    employees: Paginated<EmployeeListItem>;
    /** The selected company the list belongs to — null draws the no-company panel. */
    company: CompanySummary | null;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    /** The selected company's departments, inactive ones marked. */
    departments: EmployeeLookupOption[];
    /** Every designation (group-wide), inactive ones marked. */
    designations: EmployeeLookupOption[];
    /** `hrm.employees.view-compensation` — the salary column and field. */
    canSeeCompensation: boolean;
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: EmployeeFilters;
    counts: { active: number; trashed: number };
    /** Empty unless the viewer holds `hrm.employees.import`. */
    importFields: ImportField[];
    duplicateActions: DuplicateActionOption[];
    maxFileSizeKb: number;
    pendingImport: SheetImportPending | null;
};

const salaryFormat = new Intl.NumberFormat('en-BD', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * HRM → Employees — company-scoped and restorable, laid out as
 * `hrm/departments` (ARCHITECTURE.md §9.10): Active/Deleted tabs, every action
 * a dialog, bulk delete on both tabs, the spreadsheet import (§9.13).
 *
 * The **Account** column shows whether the employee can sign in; the account
 * itself is opened and closed from the form (owner, 2026-10-29). The salary
 * column exists only for a viewer the server sent it to.
 */
export default function EmployeesIndex({
    employees,
    company,
    statuses,
    departments,
    designations,
    canSeeCompensation,
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
    const canCreate = useCan('hrm.employees.create');
    const canUpdate = useCan('hrm.employees.update');
    const canDelete = useCan('hrm.employees.delete');
    const canRestore = useCan('hrm.employees.restore');
    const canForceDelete = useCan('hrm.employees.force-delete');
    const canManageAccount = useCan('hrm.employees.create-account');
    const canImport = useCan('hrm.employees.import') && company !== null;

    const [importOpen, setImportOpen] = useState(false);

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(employees.data);

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
        only: ['employees', 'filters'],
        refreshOnly: ['employees', 'filters', 'counts', 'pendingImport'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    const lookupFilter = (options: EmployeeLookupOption[]) => [
        { value: '', label: 'All' },
        ...options.map(({ value, label, hint }) => ({
            value: String(value),
            label,
            hint,
        })),
    ];

    const columnCount =
        (canBulkDelete ? 1 : 0) + 8 + (canSeeCompensation ? 1 : 0);

    const formProps = {
        statuses,
        departments,
        designations,
        canSeeCompensation,
        canManageAccount,
    };

    return (
        <>
            <Head title="Employees" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Employees"
                        description={
                            company === null
                                ? 'The employees of the company you are working in.'
                                : `The employees of ${company.name} — deleting one moves them to Deleted, where they can be restored.`
                        }
                    />

                    {company !== null && !isDeleted && (
                        <div className="flex flex-wrap items-center gap-2">
                            {canImport && (
                                <>
                                    <Button variant="outline" asChild>
                                        <a
                                            href={EmployeeImportController.template.url()}
                                            data-test="employee-import-template"
                                        >
                                            <Download /> Template
                                        </a>
                                    </Button>

                                    <Button
                                        variant="outline"
                                        onClick={() => setImportOpen(true)}
                                        data-test="import-employees"
                                    >
                                        <Upload /> Import
                                    </Button>
                                </>
                            )}

                            {canCreate && (
                                <EmployeeFormDialog
                                    submit={EmployeeController.store.form()}
                                    {...formProps}
                                    title="New employee"
                                    description={`Added to ${company.name}. The employee ID and email must not be used by any other employee, in any company, including a deleted one.`}
                                >
                                    <Button data-test="new-employee">
                                        <Plus /> New employee
                                    </Button>
                                </EmployeeFormDialog>
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
                                    resourceLabel="employees"
                                    uploadDescription={
                                        <>
                                            One row per employee, with an{' '}
                                            <strong>Employee ID</strong>, a{' '}
                                            <strong>Name</strong>, an{' '}
                                            <strong>Email</strong> and the{' '}
                                            <strong>Department code</strong> and{' '}
                                            <strong>Designation code</strong>;
                                            mobiles, joining date and status are
                                            optional. The rows go into{' '}
                                            {company.name} (the company selected
                                            in the header). An import never
                                            opens sign-in accounts.
                                        </>
                                    }
                                    duplicateLabel="A row whose employee ID matches an existing employee"
                                    submit={EmployeeImportController.store.form()}
                                    confirmSubmit={(id) =>
                                        EmployeeImportController.confirm.form(
                                            id,
                                        )
                                    }
                                    reviewUrl={(id) =>
                                        EmployeeImportController.review.url(id)
                                    }
                                    fields={importFields}
                                    duplicateActions={duplicateActions}
                                    maxFileSizeKb={maxFileSizeKb}
                                    pendingImport={pendingImport}
                                    open={importOpen}
                                    onOpenChange={setImportOpen}
                                    testPrefix="employee"
                                />

                                {pendingImport && !importOpen && (
                                    <PendingImportBanner
                                        pendingImport={pendingImport}
                                        cancelSubmit={EmployeeImportController.cancel.form(
                                            pendingImport.id,
                                        )}
                                        onResume={() => setImportOpen(true)}
                                        testPrefix="employee"
                                    />
                                )}

                                <ImportResultPanel
                                    rejectsUrl={(id) =>
                                        EmployeeImportController.rejects.url(id)
                                    }
                                    testPrefix="employee"
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
                                            ? EmployeeController.bulkForceDelete.form()
                                            : EmployeeController.bulkDestroy.form()
                                    }
                                    title={
                                        isDeleted
                                            ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'employee' : 'employees'}?`
                                            : `Delete ${selection.count} ${selection.count === 1 ? 'employee' : 'employees'}?`
                                    }
                                    description={
                                        isDeleted
                                            ? 'They are destroyed and their employee IDs and emails become free. Sign-in accounts stay in Admin, inactive. Their audit history stays. This cannot be undone.'
                                            : 'They move to Deleted and their sign-in accounts stop. Their employee IDs and emails stay reserved until they are permanently deleted.'
                                    }
                                    confirmLabel={
                                        isDeleted
                                            ? 'Delete permanently'
                                            : 'Delete'
                                    }
                                    onSuccess={selection.clear}
                                    testId="bulk-delete-employees"
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
                                        data-test="bulk-delete-employees"
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
                                                    aria-label="Select every employee on this page"
                                                    checked={
                                                        selection.allOnPage
                                                    }
                                                    indeterminate={
                                                        selection.someOnPage
                                                    }
                                                    onChange={
                                                        selection.toggleAll
                                                    }
                                                    data-test="select-all-employees"
                                                />
                                            </th>
                                        )}
                                        <SortableHeader
                                            {...sortProps('employee_code')}
                                        >
                                            Employee ID
                                        </SortableHeader>
                                        <SortableHeader {...sortProps('name')}>
                                            Name
                                        </SortableHeader>
                                        <SortableHeader {...sortProps('email')}>
                                            Email
                                        </SortableHeader>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Official mobile</th>
                                        {canSeeCompensation && (
                                            <th className="text-right">
                                                Monthly salary
                                            </th>
                                        )}
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
                                                column: 'employee_code',
                                                label: 'employee ID',
                                            },
                                            {
                                                type: 'text',
                                                column: 'name',
                                                label: 'name',
                                            },
                                            {
                                                type: 'text',
                                                column: 'email',
                                                label: 'email',
                                            },
                                            {
                                                type: 'select',
                                                column: 'department_id',
                                                label: 'department',
                                                testId: 'department-filter',
                                                options:
                                                    lookupFilter(departments),
                                            },
                                            {
                                                type: 'select',
                                                column: 'designation_id',
                                                label: 'designation',
                                                testId: 'designation-filter',
                                                options:
                                                    lookupFilter(designations),
                                            },
                                            {
                                                type: 'text',
                                                column: 'official_mobile_no',
                                                label: 'official mobile',
                                            },
                                            ...(canSeeCompensation
                                                ? [{ type: 'none' as const }]
                                                : []),
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
                                    {employees.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={columnCount}
                                                className="text-center text-base-content/60"
                                            >
                                                {isDeleted
                                                    ? 'No deleted employees.'
                                                    : 'No employees match these filters.'}
                                            </td>
                                        </tr>
                                    )}

                                    {employees.data.map((employee) => (
                                        <tr key={employee.id}>
                                            {canBulkDelete && (
                                                <td>
                                                    <Checkbox
                                                        aria-label={`Select ${employee.name}`}
                                                        checked={selection.isSelected(
                                                            employee.id,
                                                        )}
                                                        onChange={() =>
                                                            selection.toggle(
                                                                employee.id,
                                                            )
                                                        }
                                                        data-test="select-employee"
                                                    />
                                                </td>
                                            )}
                                            <td className="font-mono text-xs">
                                                {employee.employee_code}
                                            </td>

                                            <td>
                                                <span className="font-medium">
                                                    {employee.name}
                                                </span>
                                                {employee.is_application_user && (
                                                    <span
                                                        className="ml-2 badge badge-outline badge-xs badge-info"
                                                        title={`Signs in as ${employee.username}`}
                                                        data-test="employee-account-badge"
                                                    >
                                                        User
                                                    </span>
                                                )}
                                            </td>

                                            <td className="text-sm">
                                                {employee.email}
                                            </td>

                                            <td>{employee.department_name}</td>

                                            <td>{employee.designation_name}</td>

                                            <td className="font-mono text-xs">
                                                {employee.official_mobile_no ??
                                                    '—'}
                                            </td>

                                            {canSeeCompensation && (
                                                <td className="text-right font-mono text-xs">
                                                    {employee.monthly_salary
                                                        ? salaryFormat.format(
                                                              Number(
                                                                  employee.monthly_salary,
                                                              ),
                                                          )
                                                        : '—'}
                                                </td>
                                            )}

                                            <td>
                                                {isDeleted ? (
                                                    <span className="text-xs text-base-content/60">
                                                        {employee.deleted_at}
                                                    </span>
                                                ) : (
                                                    <span
                                                        className={`badge badge-sm ${employee.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                                    >
                                                        {employee.status ===
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
                                                                    submit={EmployeeController.restore.form(
                                                                        employee.id,
                                                                    )}
                                                                    title={`Restore ${employee.name}?`}
                                                                    description="They move back to Active with the status they had. A sign-in account stays inactive until it is re-opened from the form."
                                                                    confirmLabel="Restore"
                                                                    testId="restore-employee"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Restore ${employee.name}`}
                                                                        data-test="restore-employee"
                                                                    >
                                                                        <RotateCcw />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}

                                                            {canForceDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={EmployeeController.forceDelete.form(
                                                                        employee.id,
                                                                    )}
                                                                    title={`Permanently delete ${employee.name}?`}
                                                                    description="The employee is destroyed and their employee ID and email become free. A sign-in account stays in Admin, inactive. Their audit history stays. This cannot be undone."
                                                                    confirmLabel="Delete permanently"
                                                                    testId="force-delete-employee"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Permanently delete ${employee.name}`}
                                                                        data-test="force-delete-employee"
                                                                    >
                                                                        <Trash2 className="text-error" />
                                                                    </Button>
                                                                </ConfirmActionDialog>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {canUpdate && (
                                                                <EmployeeFormDialog
                                                                    submit={EmployeeController.update.form(
                                                                        employee.id,
                                                                    )}
                                                                    {...formProps}
                                                                    employee={
                                                                        employee
                                                                    }
                                                                    title={`Edit ${employee.name}`}
                                                                    description="Making an employee inactive keeps them on record and stops their sign-in account."
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Edit ${employee.name}`}
                                                                        data-test="edit-employee"
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                </EmployeeFormDialog>
                                                            )}

                                                            {canDelete && (
                                                                <ConfirmActionDialog
                                                                    submit={EmployeeController.destroy.form(
                                                                        employee.id,
                                                                    )}
                                                                    title={`Delete ${employee.name}?`}
                                                                    description="They move to Deleted and their sign-in account stops. Their employee ID and email stay reserved until they are permanently deleted."
                                                                    confirmLabel="Delete"
                                                                    testId="delete-employee"
                                                                >
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label={`Delete ${employee.name}`}
                                                                        data-test="delete-employee"
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

                        <Pagination page={employees} />
                    </>
                )}
            </div>
        </>
    );
}

EmployeesIndex.layout = {
    breadcrumbs: [{ title: 'Employees', href: index() }],
};
