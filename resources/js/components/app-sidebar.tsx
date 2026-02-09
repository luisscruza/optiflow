import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { WorkspaceSwitcher } from '@/components/workspace-switcher';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import contacts from '@/routes/contacts';
import inventory from '@/routes/inventory';
import prescriptions from '@/routes/prescriptions';
import products from '@/routes/products';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BarChart3, Bell, BookOpen, Eye, Folder, FolderSync, Kanban, LayoutGrid, Package, Receipt, RotateCcw, Settings, Users2 } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { can } = usePermissions();
    const { unreadNotifications } = usePage().props as { unreadNotifications: number };

    const mainNavItems: NavItem[] = [
        {
            title: 'Tablero',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Notificaciones',
            href: '/notifications',
            icon: Bell,
            badge: unreadNotifications > 0 ? unreadNotifications : undefined,
        },
        ...(can('view workflows')
            ? [
                  {
                      title: 'Procesos',
                      href: '/workflows',
                      icon: Kanban,
                  },
              ]
            : []),
        ...(can('view invoices') || can('view quotations') || can('view payments')
            ? [
                  {
                      title: 'Ingresos',
                      icon: Receipt,
                      items: [
                          ...(can('view invoices')
                              ? [
                                    {
                                        title: 'Facturas de venta',
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
                          //   ...(can('view payments')
                          //       ? [
                          //             {
                          //                 title: 'Pagos recibidos',
                          //                 href: payments.index(),
                          //                 icon: DollarSign,
                          //             },
                          //         ]
                          //       : []),
                      ],
                  } as NavItem,
              ]
            : []),
        ...(can('view products') || can('view inventory')
            ? [
                  {
                      title: 'Inventario',
                      icon: Package,
                      items: [
                          ...(can('view products')
                              ? [
                                    {
                                        title: 'Productos/Servicios',
                                        href: products.index(),
                                        icon: Package,
                                    },
                                    // {
                                    //     title: 'Importar Productos',
                                    //     href: productImports.index(),
                                    //     icon: Upload,
                                    // },
                                ]
                              : []),
                          ...(can('view inventory')
                              ? [
                                    {
                                        title: 'Inventario',
                                        href: inventory.index(),
                                        icon: RotateCcw,
                                    },
                                    {
                                        title: 'Ajustes de inventario',
                                        href: '/inventory-adjustments',
                                        icon: RotateCcw,
                                    },
                                    {
                                        title: 'Transferencias de inventario',
                                        href: '/stock-transfers',
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
        {
            title: 'Reportes',
            href: '/reports',
            icon: BarChart3,
        },
        ...(can('view configuration')
            ? [
                  {
                      title: 'Automatizaciones',
                      href: '/automations',
                      icon: FolderSync,
                  },
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
