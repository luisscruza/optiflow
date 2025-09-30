import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { WorkspaceSwitcher } from '@/components/workspace-switcher';
import { dashboard } from '@/routes';
import contacts from '@/routes/contacts';
import inventory from '@/routes/inventory';
import products from '@/routes/products';
import productImports from '@/routes/product-imports';
import workspaces from '@/routes/workspaces';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Building2, Folder, LayoutGrid, Package, RotateCcw, Settings, Users2, Receipt, Upload } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Tablero',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Facturación',
        icon: Receipt,
        items: [
            {
                title: 'Facturas',
                href: '/invoices',
                icon: Folder,
            },
                {
                title: 'Cotizaciones',
                href: '/quotations',
                icon: BookOpen,
            },
            {
                title: 'Productos',
                href: products.index(),
                icon: Package,
            },
            {
                title: 'Importar Productos',
                href: productImports.index(),
                icon: Upload,
            },
            {
                title: 'Inventario',
                href: inventory.index(),
                icon: RotateCcw,
            },
        ],
    },
    {
        title: 'Contactos',
        href: contacts.index(),
        icon: Users2,
    },
    {
        title: 'Configuración',
        href: '/configuration',
        icon: Settings,
    },
];

export function AppSidebar() {
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
                <WorkspaceSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
