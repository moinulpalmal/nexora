import { Head } from '@inertiajs/react';
import {
    accountingPhases,
    accountingStats,
    recentVouchers,
} from '@/components/accounting/sample-data';
import type { SampleVoucher } from '@/components/accounting/sample-data';
import { PreviewBanner } from '@/components/shared/preview-banner';
import {
    PreviewCard,
    PreviewRoadmap,
    PreviewStats,
} from '@/components/shared/preview-widgets';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/accounting';

const statusBadge: Record<SampleVoucher['status'], string> = {
    Draft: 'badge-ghost',
    Submitted: 'badge-warning',
    Posted: 'badge-success',
};

/**
 * 🧪 POC — the Accounts overview, on sample data (ARCHITECTURE.md §5).
 */
export default function AccountingDashboard() {
    return (
        <>
            <Head title="Accounts" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <PreviewBanner />
                <PreviewStats stats={accountingStats} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <PreviewCard
                        title="Recent vouchers"
                        className="lg:col-span-2"
                    >
                        <div className="overflow-x-auto rounded-box border border-base-300/70">
                            <table className="table table-sm">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Narration</th>
                                        <th className="text-end">Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentVouchers.map((voucher) => (
                                        <tr key={voucher.number}>
                                            <td className="font-mono text-xs whitespace-nowrap">
                                                {voucher.number}
                                            </td>
                                            <td className="whitespace-nowrap">
                                                {voucher.date}
                                            </td>
                                            <td>{voucher.type}</td>
                                            <td>{voucher.narration}</td>
                                            <td className="text-end whitespace-nowrap tabular-nums">
                                                {voucher.amount}
                                            </td>
                                            <td>
                                                <span
                                                    className={cn(
                                                        'badge badge-soft badge-sm',
                                                        statusBadge[
                                                            voucher.status
                                                        ],
                                                    )}
                                                >
                                                    {voucher.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </PreviewCard>

                    <PreviewRoadmap phases={accountingPhases} />
                </div>
            </div>
        </>
    );
}

AccountingDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Accounts',
            href: dashboard(),
        },
    ],
};
