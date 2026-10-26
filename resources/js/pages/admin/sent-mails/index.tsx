import { Head } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import SentMailController from '@/actions/App/Http/Controllers/Admin/SentMailController';
import Heading from '@/components/heading';
import ColumnFilterRow from '@/components/shared/column-filter-row';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import SortableHeader, { nextSort } from '@/components/shared/sortable-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useListFilters } from '@/hooks/use-list-filters';
import { index } from '@/routes/admin/sent-mails';
import type { Filterable, ListFilters, Paginated, StatusOption } from '@/types';

type SentMailItem = {
    id: number;
    mailable: string | null;
    to_address: string;
    subject: string | null;
    sent_at: string;
};

type Props = {
    mails: Paginated<SentMailItem>;
    mailables: StatusOption[];
    sortable: string[];
    filterable: Filterable;
    perPageOptions: number[];
    filters: ListFilters;
};

/**
 * Admin → Sent Mails: every mail the application handed to its mailer, newest
 * first. Read-only. Passwords are masked in the stored copies; the mail that
 * went out was complete.
 */
export default function SentMailsIndex({
    mails,
    mailables,
    sortable,
    filterable,
    perPageOptions,
    filters,
}: Props) {
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
        only: ['mails', 'filters'],
        refreshOnly: ['mails', 'filters', 'mailables'],
    });

    const sortProps = (column: string) => ({
        column,
        sortable,
        filters,
        onSort: (next: string) => visit(nextSort(filters, next)),
    });

    return (
        <>
            <Head title="Sent Mails" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Sent Mails"
                    description="Every mail the application sent, as stored. Passwords in the stored copies are masked."
                />

                <ListToolbar
                    perPage={filters.per_page}
                    perPageOptions={perPageOptions}
                    onPerPage={(per_page) => visit({ per_page })}
                    onClear={clear}
                    hasActiveFilter={hasActiveFilter}
                    onRefresh={refresh}
                    refreshing={refreshing}
                />

                <div className="overflow-x-auto rounded-box border border-base-300/70">
                    <table className="table table-sm">
                        <thead>
                            <tr>
                                <SortableHeader {...sortProps('sent_at')}>
                                    Sent
                                </SortableHeader>
                                <SortableHeader {...sortProps('to_address')}>
                                    To
                                </SortableHeader>
                                <SortableHeader {...sortProps('subject')}>
                                    Subject
                                </SortableHeader>
                                <th className="hidden md:table-cell">Kind</th>
                                <th className="w-16" />
                            </tr>

                            <ColumnFilterRow
                                filterable={filterable}
                                draft={draft}
                                onFilter={setFilter}
                                cells={[
                                    { type: 'none' },
                                    { type: 'text', column: 'to_address', label: 'recipient' },
                                    { type: 'text', column: 'subject', label: 'subject' },
                                    {
                                        type: 'select',
                                        column: 'mailable',
                                        label: 'kind',
                                        className: 'hidden md:table-cell',
                                        options: [{ value: '', label: 'All' }, ...mailables],
                                    },
                                    { type: 'none' },
                                ]}
                            />
                        </thead>
                        <tbody>
                            {mails.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="text-center text-base-content/60"
                                    >
                                        No mail has been sent yet.
                                    </td>
                                </tr>
                            )}

                            {mails.data.map((mail) => (
                                <tr key={mail.id}>
                                    <td className="text-xs whitespace-nowrap text-base-content/70">
                                        {new Date(mail.sent_at).toLocaleString()}
                                    </td>
                                    <td>{mail.to_address}</td>
                                    <td>{mail.subject ?? '—'}</td>
                                    <td className="hidden md:table-cell">
                                        {mail.mailable && (
                                            <span className="badge badge-ghost font-mono badge-sm">
                                                {mail.mailable}
                                            </span>
                                        )}
                                    </td>
                                    <td>
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Preview the mail to ${mail.to_address}`}
                                                    data-test="preview-mail"
                                                >
                                                    <Eye />
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent className="max-w-4xl">
                                                <DialogTitle>
                                                    {mail.subject ?? 'Mail'}
                                                </DialogTitle>
                                                <DialogDescription>
                                                    To {mail.to_address} — exactly
                                                    as stored.
                                                </DialogDescription>
                                                {/* Sandboxed (no scripts, no
                                                    forms, opaque origin), and
                                                    the response carries its own
                                                    locked-down CSP: a mail body
                                                    never runs in the app. */}
                                                <iframe
                                                    title={mail.subject ?? 'Mail'}
                                                    sandbox=""
                                                    src={SentMailController.show.url(mail.id)}
                                                    className="mt-4 h-[70vh] w-full rounded-box border border-base-300/70 bg-white"
                                                    data-test="sent-mail-frame"
                                                />
                                            </DialogContent>
                                        </Dialog>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination page={mails} />
            </div>
        </>
    );
}

SentMailsIndex.layout = {
    breadcrumbs: [{ title: 'Sent Mails', href: index() }],
};
