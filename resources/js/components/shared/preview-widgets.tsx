import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type PreviewStat = {
    title: string;
    value: string;
    description: string;
};

export type PreviewPhase = {
    name: string;
    items: string;
    current?: boolean;
};

/**
 * The KPI row of a 🧪 POC overview: daisyUI `stats`, stacked on small screens.
 */
export function PreviewStats({ stats }: { stats: PreviewStat[] }) {
    return (
        <div className="stats w-full stats-vertical border border-base-300 bg-base-100 lg:stats-horizontal">
            {stats.map((stat) => (
                <div key={stat.title} className="stat">
                    <div className="stat-title">{stat.title}</div>
                    <div className="stat-value text-2xl">{stat.value}</div>
                    <div className="stat-desc">{stat.description}</div>
                </div>
            ))}
        </div>
    );
}

/**
 * A titled card around a 🧪 POC overview's content.
 */
export function PreviewCard({
    title,
    className,
    children,
}: {
    title: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn('card border border-base-300 bg-base-100', className)}
        >
            <div className="card-body gap-3">
                <h2 className="card-title text-base">{title}</h2>
                {children}
            </div>
        </div>
    );
}

/**
 * The module's phases from its documentation, as a vertical step list.
 */
export function PreviewRoadmap({ phases }: { phases: PreviewPhase[] }) {
    return (
        <PreviewCard title="Coming next">
            <ul className="steps steps-vertical">
                {phases.map((phase) => (
                    <li
                        key={phase.name}
                        className={cn('step', phase.current && 'step-primary')}
                    >
                        <div className="text-start">
                            <div className="font-medium">{phase.name}</div>
                            <div className="text-sm text-base-content/60">
                                {phase.items}
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </PreviewCard>
    );
}
