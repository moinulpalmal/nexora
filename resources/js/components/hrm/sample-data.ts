/*
 * 🧪 POC sample data for the HRM overview (ARCHITECTURE.md §5). Every name and
 * figure is invented. Delete this file when the overview reads real data.
 */
import type {
    PreviewPhase,
    PreviewStat,
} from '@/components/shared/preview-widgets';

export type SampleJoiner = {
    code: string;
    name: string;
    department: string;
    designation: string;
    joined: string;
};

export const hrmStats: PreviewStat[] = [
    {
        title: 'Headcount',
        value: '2,418',
        description: '2,301 workers, 117 staff',
    },
    { title: 'Present today', value: '2,287', description: '94.6% attendance' },
    { title: 'On leave', value: '64', description: '11 starting tomorrow' },
    { title: 'Leave requests', value: '23', description: 'Awaiting approval' },
];

export const recentJoiners: SampleJoiner[] = [
    {
        code: 'E-24181',
        name: 'Rafiq Islam',
        department: 'Sewing',
        designation: 'Operator',
        joined: '27 Oct 2026',
    },
    {
        code: 'E-24180',
        name: 'Shirin Akter',
        department: 'Sewing',
        designation: 'Line Supervisor',
        joined: '26 Oct 2026',
    },
    {
        code: 'E-24179',
        name: 'Mahmud Hasan',
        department: 'Quality',
        designation: 'Quality Inspector',
        joined: '22 Oct 2026',
    },
    {
        code: 'E-24178',
        name: 'Farzana Begum',
        department: 'Cutting',
        designation: 'Cutter',
        joined: '20 Oct 2026',
    },
    {
        code: 'E-24177',
        name: 'Arif Chowdhury',
        department: 'Accounts',
        designation: 'Executive',
        joined: '15 Oct 2026',
    },
];

export const hrmPhases: PreviewPhase[] = [
    {
        name: 'HRM-lite',
        items: 'Employees, departments, designations',
        current: true,
    },
    {
        name: 'Workforce',
        items: 'Sections and lines, shifts, attendance, leave',
    },
    {
        name: 'Payroll',
        items: 'Wage grades, overtime, salary sheet, posting to Accounts',
    },
    { name: 'Lifecycle', items: 'Recruitment, appraisal, separation' },
];
