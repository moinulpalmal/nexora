import { Link } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    FolderKanban,
    Network,
    GraduationCap,
    History,
    KeyRound,
    Landmark,
    LayoutGrid,
    Mail,
    ShieldCheck,
    SlidersHorizontal,
    Users,
    UsersRound,
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
import { dashboard as accountingDashboard } from '@/routes/accounting';
import { edit as applicationSettingsEdit } from '@/routes/admin/application-settings';
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { index as companiesIndex } from '@/routes/admin/companies';
import { index as permissionsIndex } from '@/routes/admin/permissions';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as sentMailsIndex } from '@/routes/admin/sent-mails';
import { index as usersIndex } from '@/routes/admin/users';
import { dashboard as hrmDashboard } from '@/routes/hrm';
import { index as departmentsIndex } from '@/routes/hrm/departments';
import { index as designationsIndex } from '@/routes/hrm/designations';
import { dashboard as projectsDashboard } from '@/routes/projects';
import { index as trainingIndex } from '@/routes/training';
import type { NavGroup, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    /*
     * The one link with no `useCan` guard: the manual is open to every
     * signed-in user, including for screens they cannot open yet
     * (ARCHITECTURE.md §8.3).
     */
    {
        title: 'Training',
        href: trainingIndex(),
        icon: GraduationCap,
    },
];

export function AppSidebar() {
    // Hiding a link is convenience, not authorization — the routes are gated by
    // the `permission:` middleware.
    const canViewUsers = useCan('admin.users.view');
    const canViewRoles = useCan('admin.roles.view');
    const canViewCompanies = useCan('admin.companies.view');
    const canViewPermissions = useCan('admin.permissions.view');
    const canViewAuditLogs = useCan('admin.audit-logs.view');
    const canViewSentMails = useCan('admin.sent-mails.view');

    // Either tab's `.view` opens the hub, as on its route. Both hooks are
    // called unconditionally; only their results are combined.
    const canViewMailSettings = useCan('admin.mail-settings.view');
    const canViewQuickLogin = useCan('admin.quick-login.view');

    const canViewProjects = useCan('projects.dashboard.view');
    const canViewAccounting = useCan('accounting.dashboard.view');
    const canViewHrm = useCan('hrm.dashboard.view');
    const canViewDepartments = useCan('hrm.departments.view');
    const canViewDesignations = useCan('hrm.designations.view');

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
            title: 'Companies',
            href: companiesIndex(),
            icon: Building2,
            visible: canViewCompanies,
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
        {
            title: 'Sent Mails',
            href: sentMailsIndex(),
            icon: Mail,
            visible: canViewSentMails,
        },
        {
            title: 'Application Settings',
            href: applicationSettingsEdit(),
            icon: SlidersHorizontal,
            visible: canViewMailSettings || canViewQuickLogin,
        },
    ];

    const adminNavItems: NavItem[] = adminNavLinks
        .filter((link) => link.visible)
        .map(({ title, href, icon }) => ({ title, href, icon }));

    // HRM's first real screen turned its group into a list of links, each
    // behind its own permission, like Admin's.
    const hrmNavLinks: (NavItem & { visible: boolean })[] = [
        {
            title: 'Overview',
            href: hrmDashboard(),
            icon: UsersRound,
            visible: canViewHrm,
        },
        {
            title: 'Departments',
            href: departmentsIndex(),
            icon: Network,
            visible: canViewDepartments,
        },
        {
            title: 'Designations',
            href: designationsIndex(),
            icon: Briefcase,
            visible: canViewDesignations,
        },
    ];

    const hrmNavItems: NavItem[] = hrmNavLinks
        .filter((link) => link.visible)
        .map(({ title, href, icon }) => ({ title, href, icon }));

    // One group per module; a module's links never join another module's group
    // (ARCHITECTURE.md §8.3). A group whose items are all hidden is not rendered
    // at all, so it never reaches the collapse state either.
    const navGroups: NavGroup[] = [
        { label: 'Platform', items: mainNavItems },
        { label: 'Admin', items: adminNavItems },
        {
            label: 'Projects',
            items: canViewProjects
                ? [
                      {
                          title: 'Overview',
                          href: projectsDashboard(),
                          icon: FolderKanban,
                      },
                  ]
                : [],
        },
        {
            label: 'Accounts',
            items: canViewAccounting
                ? [
                      {
                          title: 'Overview',
                          href: accountingDashboard(),
                          icon: Landmark,
                      },
                  ]
                : [],
        },
        { label: 'HRM', items: hrmNavItems },
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
