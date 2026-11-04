import { Head } from '@inertiajs/react';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';
import CertificationController from '@/actions/App/Http/Controllers/ProjectManagement/CertificationController';
import Heading from '@/components/heading';
import CertificationFormDialog from '@/components/projects/certification-form-dialog';
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
import { index } from '@/routes/projects/settings/certifications';
import type {
    CertificationCategoryOption,
    CertificationFilters,
    CertificationListItem,
    Filterable,
    Paginated,
    StatusOption,
} from '@/types';

type Props = {
    certifications: Paginated<CertificationListItem>;
    /** `RecordStatus::options()` — drives both the form and the filter. */
    statuses: StatusOption[];
    /** Every certification category, inactive ones marked — the form and the filter. */
    categories: CertificationCategoryOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: CertificationFilters;
    counts: { active: number; trashed: number };
};

/**
 * Projects → Settings → Certifications — a catalogue of standards (ISO 9001, amfori
 * BSCI, CE…), one list every company shares (ARCHITECTURE.md §9.10). Laid out
 * as `projects/settings/tender-types` (Active/Deleted tabs, every action a dialog, bulk
 * delete on both tabs) with the category column and filter of
 * `projects/settings/suppliers`; the page works whether or not a company is selected.
 *
 * Nothing references a certification yet, so every row can be deleted and
 * ticked.
 */
export default function CertificationsIndex({
    certifications,
    statuses,
    categories,
    sortable,
    filterable,
    perPageOptions,
    filters,
    counts,
}: Props) {
    const canCreate = useCan('projects.settings.certifications.create');
    const canUpdate = useCan('projects.settings.certifications.update');
    const canDelete = useCan('projects.settings.certifications.delete');
    const canRestore = useCan('projects.settings.certifications.restore');
    const canForceDelete = useCan(
        'projects.settings.certifications.force-delete',
    );

    const isDeleted = filters.view === 'trashed';
    const canBulkDelete = isDeleted ? canForceDelete : canDelete;

    const selection = useRowSelection(certifications.data);

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
        only: ['certifications', 'filters'],
        refreshOnly: ['certifications', 'filters', 'counts'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Certifications" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Certifications"
                        description="The standards a supplier may hold or a tender may demand — ISO 9001, BSCI, CE and the like. One list every company shares. Deleting one moves it to Deleted, where it can be restored."
                    />

                    {!isDeleted && canCreate && (
                        <CertificationFormDialog
                            submit={CertificationController.store.form()}
                            statuses={statuses}
                            categories={categories}
                            title="New certification"
                            description="Every company sees it — the code must not be used by another certification, nor the name within the same version, including a deleted one."
                        >
                            <Button data-test="new-certification">
                                <Plus /> New certification
                            </Button>
                        </CertificationFormDialog>
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
                                    ? CertificationController.bulkForceDelete.form()
                                    : CertificationController.bulkDestroy.form()
                            }
                            title={
                                isDeleted
                                    ? `Permanently delete ${selection.count} ${selection.count === 1 ? 'certification' : 'certifications'}?`
                                    : `Delete ${selection.count} ${selection.count === 1 ? 'certification' : 'certifications'}?`
                            }
                            description={
                                isDeleted
                                    ? 'They are destroyed and their codes and names become free. Their audit history stays. This cannot be undone.'
                                    : 'They move to Deleted for every company. Their codes, and names within their versions, stay reserved until they are permanently deleted.'
                            }
                            confirmLabel={
                                isDeleted ? 'Delete permanently' : 'Delete'
                            }
                            onSuccess={selection.clear}
                            testId="bulk-delete-certifications"
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
                                data-test="bulk-delete-certifications"
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
                                            aria-label="Select every certification on this page"
                                            checked={selection.allOnPage}
                                            indeterminate={selection.someOnPage}
                                            onChange={selection.toggleAll}
                                            data-test="select-all-certifications"
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
                                <SortableHeader {...sortProps('issuing_body')}>
                                    Issuing body
                                </SortableHeader>
                                <SortableHeader {...sortProps('version')}>
                                    Version
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
                                        type: 'select',
                                        column: 'certification_category_id',
                                        label: 'category',
                                        testId: 'category-filter',
                                        options: [
                                            { value: '', label: 'All' },
                                            ...categories.map(
                                                ({ value, label, hint }) => ({
                                                    value: String(value),
                                                    label,
                                                    hint,
                                                }),
                                            ),
                                        ],
                                    },
                                    {
                                        type: 'text',
                                        column: 'issuing_body',
                                        label: 'issuing body',
                                    },
                                    {
                                        type: 'text',
                                        column: 'version',
                                        label: 'version',
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
                            {certifications.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canBulkDelete ? 8 : 7}
                                        className="text-center text-base-content/60"
                                    >
                                        {isDeleted
                                            ? 'No deleted certifications.'
                                            : 'No certifications match these filters.'}
                                    </td>
                                </tr>
                            )}

                            {certifications.data.map((certification) => (
                                <tr key={certification.id}>
                                    {canBulkDelete && (
                                        <td>
                                            <Checkbox
                                                aria-label={`Select ${certification.name}`}
                                                checked={selection.isSelected(
                                                    certification.id,
                                                )}
                                                onChange={() =>
                                                    selection.toggle(
                                                        certification.id,
                                                    )
                                                }
                                                data-test="select-certification"
                                            />
                                        </td>
                                    )}
                                    <td className="font-mono text-xs">
                                        {certification.code}
                                    </td>

                                    <td className="font-medium">
                                        {certification.name}
                                    </td>

                                    <td>{certification.category_name}</td>

                                    <td>{certification.issuing_body}</td>

                                    <td className="text-xs">
                                        {certification.version}
                                    </td>

                                    <td>
                                        {isDeleted ? (
                                            <span className="text-xs text-base-content/60">
                                                {certification.deleted_at}
                                            </span>
                                        ) : (
                                            <span
                                                className={`badge badge-sm ${certification.status === RECORD_STATUS.Active ? 'badge-success' : 'badge-warning'}`}
                                            >
                                                {certification.status ===
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
                                                            submit={CertificationController.restore.form(
                                                                certification.id,
                                                            )}
                                                            title={`Restore ${certification.name}?`}
                                                            description="It moves back to Active with the status it had."
                                                            confirmLabel="Restore"
                                                            testId="restore-certification"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Restore ${certification.name}`}
                                                                data-test="restore-certification"
                                                            >
                                                                <RotateCcw />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}

                                                    {canForceDelete && (
                                                        <ConfirmActionDialog
                                                            submit={CertificationController.forceDelete.form(
                                                                certification.id,
                                                            )}
                                                            title={`Permanently delete ${certification.name}?`}
                                                            description="The certification is destroyed and its code and name become free. Its audit history stays. This cannot be undone."
                                                            confirmLabel="Delete permanently"
                                                            testId="force-delete-certification"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Permanently delete ${certification.name}`}
                                                                data-test="force-delete-certification"
                                                            >
                                                                <Trash2 className="text-error" />
                                                            </Button>
                                                        </ConfirmActionDialog>
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    {canUpdate && (
                                                        <CertificationFormDialog
                                                            submit={CertificationController.update.form(
                                                                certification.id,
                                                            )}
                                                            statuses={statuses}
                                                            categories={
                                                                categories
                                                            }
                                                            certification={
                                                                certification
                                                            }
                                                            title={`Edit ${certification.name}`}
                                                            description="The change shows in every company. Making a certification inactive keeps it on record."
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${certification.name}`}
                                                                data-test="edit-certification"
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        </CertificationFormDialog>
                                                    )}

                                                    {canDelete && (
                                                        <ConfirmActionDialog
                                                            submit={CertificationController.destroy.form(
                                                                certification.id,
                                                            )}
                                                            title={`Delete ${certification.name}?`}
                                                            description="It moves to Deleted for every company. Its code and name stay reserved until it is permanently deleted."
                                                            confirmLabel="Delete"
                                                            testId="delete-certification"
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${certification.name}`}
                                                                data-test="delete-certification"
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

                <Pagination page={certifications} />
            </div>
        </>
    );
}

CertificationsIndex.layout = {
    breadcrumbs: [{ title: 'Certifications', href: index() }],
};
