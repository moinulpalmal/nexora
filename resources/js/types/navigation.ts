import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

/**
 * A sidebar row that opens a list of links instead of going anywhere itself.
 */
export type NavParent = {
    title: string;
    icon?: LucideIcon | null;
    items: NavItem[];
};

/**
 * One module's sidebar links. `label` is both the group heading and the key its
 * collapsed state is remembered under — see ARCHITECTURE.md §8.3.
 */
export type NavGroup = {
    label: string;
    items: (NavItem | NavParent)[];
};
