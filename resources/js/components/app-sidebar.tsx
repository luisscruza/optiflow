import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { WorkspaceSwitcher } from '@/components/workspace-switcher';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import contacts from '@/routes/contacts';
import inventory from '@/routes/inventory';
import prescriptions from '@/routes/prescriptions';
import productImports from '@/routes/product-imports';
import products from '@/routes/products';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Eye, Folder, LayoutGrid, Package, Receipt, RotateCcw, Settings, Upload, Users2 } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { can } = usePermissions();

    const mainNavItems: NavItem[] = [
        {
            title: 'Tablero',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(can('view invoices') || can('view quotations') || can('view products') || can('view inventory')
            ? [
                  {
                      title: 'Facturación',
                      icon: Receipt,
                      items: [
                          ...(can('view invoices')
                              ? [
                                    {
                                        title: 'Facturas',
                                        href: '/invoices',
                                        icon: Folder,
                                    },
                                ]
                              : []),
                          ...(can('view quotations')
                              ? [
                                    {
                                        title: 'Cotizaciones',
                                        href: '/quotations',
                                        icon: BookOpen,
                                    },
                                ]
                              : []),
                          ...(can('view products')
                              ? [
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
                                ]
                              : []),
                          ...(can('view inventory')
                              ? [
                                    {
                                        title: 'Inventario',
                                        href: inventory.index(),
                                        icon: RotateCcw,
                                    },
                                ]
                              : []),
                      ],
                  } as NavItem,
              ]
            : []),
        ...(can('view contacts')
            ? [
                  {
                      title: 'Contactos',
                      href: contacts.index(),
                      icon: Users2,
                  },
              ]
            : []),
        ...(can('view prescriptions')
            ? [
                  {
                      title: 'Recetas',
                      href: prescriptions.index(),
                      icon: Eye,
                  },
              ]
            : []),
        ...(can('view configuration')
            ? [
                  {
                      title: 'Configuración',
                      href: '/configuration',
                      icon: Settings,
                  },
              ]
            : []),
    ];

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
