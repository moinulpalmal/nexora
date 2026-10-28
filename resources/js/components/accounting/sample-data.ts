/*
 * 🧪 POC sample data for the Accounts overview (ARCHITECTURE.md §5). Every name
 * and figure is invented. Delete this file when the overview reads real data.
 */
import type {
    PreviewPhase,
    PreviewStat,
} from '@/components/shared/preview-widgets';

export type SampleVoucher = {
    number: string;
    date: string;
    type: 'Payment' | 'Receipt' | 'Journal' | 'Contra';
    narration: string;
    amount: string;
    status: 'Draft' | 'Submitted' | 'Posted';
};

export const accountingStats: PreviewStat[] = [
    {
        title: 'Cash and bank',
        value: 'BDT 4.82 Cr',
        description: 'Across 6 accounts',
    },
    {
        title: 'Receivables',
        value: 'BDT 1.37 Cr',
        description: 'BDT 22 L overdue',
    },
    {
        title: 'Awaiting approval',
        value: '9 vouchers',
        description: '2 from payroll',
    },
    {
        title: 'Income this month',
        value: 'BDT 86.4 L',
        description: 'Up 6% on last month',
    },
];

export const recentVouchers: SampleVoucher[] = [
    {
        number: 'RV-2026-0418',
        date: '27 Oct 2026',
        type: 'Receipt',
        narration: 'Export proceeds — Northwind Apparel (USD 42,000)',
        amount: 'BDT 51,24,000',
        status: 'Posted',
    },
    {
        number: 'PV-2026-0733',
        date: '27 Oct 2026',
        type: 'Payment',
        narration: 'Electricity bill — September',
        amount: 'BDT 3,85,200',
        status: 'Submitted',
    },
    {
        number: 'JV-2026-0192',
        date: '26 Oct 2026',
        type: 'Journal',
        narration: 'Payroll accrual — October',
        amount: 'BDT 62,10,500',
        status: 'Draft',
    },
    {
        number: 'CV-2026-0057',
        date: '25 Oct 2026',
        type: 'Contra',
        narration: 'Cash withdrawal for petty cash',
        amount: 'BDT 2,00,000',
        status: 'Posted',
    },
    {
        number: 'PV-2026-0732',
        date: '24 Oct 2026',
        type: 'Payment',
        narration: 'Freight forwarding — Chattogram',
        amount: 'BDT 1,47,850',
        status: 'Posted',
    },
];

export const accountingPhases: PreviewPhase[] = [
    {
        name: 'Customers',
        items: 'The customer master, shipped with Projects',
        current: true,
    },
    {
        name: 'Ledger',
        items: 'Chart of accounts, fiscal years, vouchers, statements',
    },
    { name: 'Receivables', items: 'Sales invoices, receipts, customer ageing' },
    {
        name: 'Payables and banking',
        items: 'Suppliers, bills, bank reconciliation',
    },
    { name: 'Tax and analysis', items: 'VAT/TDS, cost centres, budgets' },
];
