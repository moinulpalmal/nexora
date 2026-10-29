/*
 * 🧪 POC sample data for the Projects overview (ARCHITECTURE.md §5). Every name
 * and figure is invented. Delete this file when the overview reads real data.
 */
import type {
    PreviewPhase,
    PreviewStat,
} from '@/components/shared/preview-widgets';

export type SampleProject = {
    code: string;
    name: string;
    customer: string;
    manager: string;
    billing: 'Milestone' | 'Time & materials';
    status: 'Active' | 'Planned' | 'On hold' | 'Completed';
};

export const projectStats: PreviewStat[] = [
    {
        title: 'Active projects',
        value: '12',
        description: '3 starting this month',
    },
    { title: 'Open tasks', value: '148', description: '27 due this week' },
    {
        title: 'Hours this week',
        value: '386 h',
        description: '41 h awaiting approval',
    },
    {
        title: 'Ready to invoice',
        value: 'USD 18,450',
        description: '5 billing requests',
    },
];

export const recentProjects: SampleProject[] = [
    {
        code: 'PRJ-0012',
        name: 'Warehouse scanning rollout',
        customer: 'Northwind Traders',
        manager: 'Karim Ahmed',
        billing: 'Milestone',
        status: 'Active',
    },
    {
        code: 'PRJ-0011',
        name: 'Customer portal integration',
        customer: 'Bluefin Retail',
        manager: 'Sadia Rahman',
        billing: 'Time & materials',
        status: 'Active',
    },
    {
        code: 'PRJ-0010',
        name: 'Process improvement study',
        customer: 'Harbor Logistics',
        manager: 'Tanvir Hossain',
        billing: 'Time & materials',
        status: 'On hold',
    },
    {
        code: 'PRJ-0009',
        name: 'Compliance audit preparation',
        customer: 'Evergreen Brands',
        manager: 'Nadia Karim',
        billing: 'Milestone',
        status: 'Completed',
    },
    {
        code: 'PRJ-0013',
        name: 'Document archive digitisation',
        customer: 'Northwind Traders',
        manager: 'Karim Ahmed',
        billing: 'Milestone',
        status: 'Planned',
    },
];

export const projectPhases: PreviewPhase[] = [
    {
        name: 'Prerequisites',
        items: 'Companies, employees, customers',
        current: true,
    },
    {
        name: 'Delivery',
        items: 'Projects, milestones, task board, team, timesheets',
    },
    {
        name: 'Billing',
        items: 'Billing requests from milestones and approved hours',
    },
    { name: 'Profitability', items: 'Budget against actual per project' },
];
