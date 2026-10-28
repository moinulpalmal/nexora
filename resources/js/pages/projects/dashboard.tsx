import { Head } from '@inertiajs/react';
import {
    recentProjects,
    projectPhases,
    projectStats,
} from '@/components/projects/sample-data';
import type { SampleProject } from '@/components/projects/sample-data';
import { PreviewBanner } from '@/components/shared/preview-banner';
import {
    PreviewCard,
    PreviewRoadmap,
    PreviewStats,
} from '@/components/shared/preview-widgets';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/projects';

const statusBadge: Record<SampleProject['status'], string> = {
    Active: 'badge-success',
    Planned: 'badge-info',
    'On hold': 'badge-warning',
    Completed: 'badge-ghost',
};

/**
 * 🧪 POC — the Projects overview, on sample data (ARCHITECTURE.md §5).
 */
export default function ProjectsDashboard() {
    return (
        <>
            <Head title="Projects" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <PreviewBanner />
                <PreviewStats stats={projectStats} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <PreviewCard
                        title="Recent projects"
                        className="lg:col-span-2"
                    >
                        <div className="overflow-x-auto rounded-box border border-base-300/70">
                            <table className="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Project</th>
                                        <th>Customer</th>
                                        <th>Manager</th>
                                        <th>Billing</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentProjects.map((project) => (
                                        <tr key={project.code}>
                                            <td className="font-mono text-xs whitespace-nowrap">
                                                {project.code}
                                            </td>
                                            <td>{project.name}</td>
                                            <td>{project.customer}</td>
                                            <td>{project.manager}</td>
                                            <td>{project.billing}</td>
                                            <td>
                                                <span
                                                    className={cn(
                                                        'badge badge-soft badge-sm',
                                                        statusBadge[
                                                            project.status
                                                        ],
                                                    )}
                                                >
                                                    {project.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </PreviewCard>

                    <PreviewRoadmap phases={projectPhases} />
                </div>
            </div>
        </>
    );
}

ProjectsDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: dashboard(),
        },
    ],
};
