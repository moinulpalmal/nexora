import { Link } from '@inertiajs/react';
import {
    History,
    KeyRound,
    LayoutGrid,
    ShieldCheck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCan } from '@/hooks/use-can';
import { useNavGroups } from '@/hooks/use-nav-groups';
import { dashboard } from '@/routes';
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { index as permissionsIndex } from '@/routes/admin/permissions';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as usersIndex } from '@/routes/admin/users';
import type { NavGroup, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    // Hiding a link is convenience, not authorization — the routes are gated by
    // the `permission:` middleware.
    const canViewUsers = useCan('admin.users.view');
    const canViewRoles = useCan('admin.roles.view');
    const canViewPermissions = useCan('admin.permissions.view');
    const canViewAuditLogs = useCan('admin.audit-logs.view');

    const adminNavLinks: (NavItem & { visible: boolean })[] = [
        {
            title: 'Users',
            href: usersIndex(),
            icon: Users,
            visible: canViewUsers,
        },
        {
            title: 'Roles',
            href: rolesIndex(),
            icon: ShieldCheck,
            visible: canViewRoles,
        },
        {
            title: 'Permissions',
            href: permissionsIndex(),
            icon: KeyRound,
            visible: canViewPermissions,
        },
        {
            title: 'Audit Log',
            href: auditLogsIndex(),
            icon: History,
            visible: canViewAuditLogs,
        },
    ];

    const adminNavItems: NavItem[] = adminNavLinks
        .filter((link) => link.visible)
        .map(({ title, href, icon }) => ({ title, href, icon }));

    // One group per module; a module's links never join another module's group
    // (ARCHITECTURE.md §8.3). A group whose items are all hidden is not rendered
    // at all, so it never reaches the collapse state either.
    const navGroups: NavGroup[] = [
        { label: 'Platform', items: mainNavItems },
        { label: 'Admin', items: adminNavItems },
    ].filter((group) => group.items.length > 0);

    // Called once: this is the only place that knows every group and its links.
    const { isExpanded, toggle } = useNavGroups(navGroups);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <NavMain
                        key={group.label}
                        items={group.items}
                        label={group.label}
                        expanded={isExpanded(group.label)}
                        onToggle={() => toggle(group.label)}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
