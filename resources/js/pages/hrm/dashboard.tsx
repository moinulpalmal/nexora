import { Head } from '@inertiajs/react';
import {
    hrmPhases,
    hrmStats,
    recentJoiners,
} from '@/components/hrm/sample-data';
import { PreviewBanner } from '@/components/shared/preview-banner';
import {
    PreviewCard,
    PreviewRoadmap,
    PreviewStats,
} from '@/components/shared/preview-widgets';
import { dashboard } from '@/routes/hrm';

/**
 * 🧪 POC — the HRM overview, on sample data (ARCHITECTURE.md §5).
 */
export default function HrmDashboard() {
    return (
        <>
            <Head title="HRM" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <PreviewBanner />
                <PreviewStats stats={hrmStats} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <PreviewCard
                        title="Recent joiners"
                        className="lg:col-span-2"
                    >
                        <div className="overflow-x-auto rounded-box border border-base-300/70">
                            <table className="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentJoiners.map((joiner) => (
                                        <tr key={joiner.code}>
                                            <td className="font-mono text-xs whitespace-nowrap">
                                                {joiner.code}
                                            </td>
                                            <td>{joiner.name}</td>
                                            <td>{joiner.department}</td>
                                            <td>{joiner.designation}</td>
                                            <td className="whitespace-nowrap">
                                                {joiner.joined}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </PreviewCard>

                    <PreviewRoadmap phases={hrmPhases} />
                </div>
            </div>
        </>
    );
}

HrmDashboard.layout = {
    breadcrumbs: [
        {
            title: 'HRM',
            href: dashboard(),
        },
    ],
};
