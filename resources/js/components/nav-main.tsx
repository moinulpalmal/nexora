import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useId } from 'react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem, NavParent } from '@/types';

/**
 * A row that opens a list of links under it, such as Projects → Settings. It
 * goes nowhere itself. `NavMain` never draws it in the icon rail.
 */
function NavParentItem({
    item,
    expanded,
    onToggle,
}: {
    item: NavParent;
    expanded: boolean;
    onToggle?: () => void;
}) {
    const { isCurrentUrl } = useCurrentUrl();
    const contentId = useId();

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                type="button"
                onClick={onToggle}
                aria-expanded={expanded}
                aria-controls={contentId}
                className="cursor-pointer"
            >
                {item.icon && <item.icon />}
                <span>{item.title}</span>
                <ChevronDown
                    className={`ml-auto transition-transform duration-200 ${expanded ? '' : '-rotate-90'}`}
                    aria-hidden
                />
            </SidebarMenuButton>

            <SidebarMenuSub id={contentId} hidden={!expanded}>
                {item.items.map((child) => (
                    <SidebarMenuSubItem key={child.title}>
                        <SidebarMenuSubButton
                            asChild
                            isActive={isCurrentUrl(child.href)}
                        >
                            <Link href={child.href} prefetch>
                                {child.icon && <child.icon />}
                                <span>{child.title}</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                ))}
            </SidebarMenuSub>
        </SidebarMenuItem>
    );
}

/**
 * One labelled group of sidebar links. `label` names the group — modules with
 * their own surfaces get their own group rather than joining "Platform".
 *
 * The label is also the group's collapse toggle. This component owns none of
 * that state: `useNavGroups` does, from `app-sidebar.tsx` (ARCHITECTURE.md §8.3).
 *
 * A parent row's links are `SidebarMenuSub` items, which are `display: none` in
 * the icon rail. So the rail draws them flat and top-level instead, or a parent
 * would be an icon leading nowhere.
 */
export function NavMain({
    items = [],
    label = 'Platform',
    expanded = true,
    onToggle,
    isParentExpanded,
    onParentToggle,
}: {
    items: (NavItem | NavParent)[];
    label?: string;
    expanded?: boolean;
    onToggle?: () => void;
    isParentExpanded?: (title: string) => boolean;
    onParentToggle?: (title: string) => void;
}) {
    const { isCurrentUrl } = useCurrentUrl();
    const { state, isMobile } = useSidebar();
    const contentId = useId();

    /*
     * The 3rem rail hides `SidebarGroupLabel` outright, so its toggle is
     * unreachable there. A group left collapsed would hide its icons with no
     * way to bring them back — so in the rail, every group is open.
     */
    const isRail = state === 'collapsed' && !isMobile;
    const isOpen = isRail || expanded;

    const rows = isRail
        ? items.flatMap((item) => ('items' in item ? item.items : [item]))
        : items;

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel asChild>
                <button
                    type="button"
                    onClick={onToggle}
                    aria-expanded={isOpen}
                    aria-controls={contentId}
                    className="w-full cursor-pointer justify-between hover:text-base-content"
                >
                    <span>{label}</span>
                    <ChevronDown
                        className={`transition-transform duration-200 ${isOpen ? '' : '-rotate-90'}`}
                        aria-hidden
                    />
                </button>
            </SidebarGroupLabel>

            <SidebarGroupContent id={contentId} hidden={!isOpen}>
                <SidebarMenu>
                    {rows.map((item) =>
                        'items' in item ? (
                            <NavParentItem
                                key={item.title}
                                item={item}
                                expanded={
                                    isParentExpanded?.(item.title) ?? true
                                }
                                onToggle={() => onParentToggle?.(item.title)}
                            />
                        ) : (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.title }}
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ),
                    )}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
