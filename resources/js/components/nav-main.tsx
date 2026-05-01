import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();

    const isItemActive = (item: NavItem): boolean => {
        if (item.isActive !== undefined) {
            return item.isActive;
        }

        const href = typeof item.href === 'string' ? item.href : item.href?.url || '';

        return href !== '' && page.url.startsWith(href);
    };

    const renderNavItem = (item: NavItem) => {
        if (item.items && item.items.length > 0) {
            return (
                <Collapsible key={item.title} asChild defaultOpen={item.items.some(isItemActive)}>
                    <SidebarMenuItem>
                        <CollapsibleTrigger asChild>
                            <SidebarMenuButton tooltip={{ children: item.title }}>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                                <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                {item.items.map((subItem) => (
                                    <SidebarMenuSubItem key={subItem.title}>
                                        <SidebarMenuSubButton asChild isActive={isItemActive(subItem)}>
                                            <Link href={subItem.href!} prefetch>
                                                {subItem.icon && <subItem.icon />}
                                                <span>{subItem.title}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                ))}
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>
            );
        } else {
            return (
                <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton asChild isActive={isItemActive(item)} tooltip={{ children: item.title }}>
                        <Link href={item.href!} prefetch>
                            {item.icon && <item.icon />}
                            <span>{item.title}</span>
                            {item.badge !== undefined && (
                                <span className="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs font-medium text-primary-foreground">
                                    {item.badge}
                                </span>
                            )}
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            );
        }
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarMenu>{items.map(renderNavItem)}</SidebarMenu>
        </SidebarGroup>
    );
}
