import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { WorkspaceSwitcher } from '@/components/workspace-switcher';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import contacts from '@/routes/contacts';
import prescriptions from '@/routes/prescriptions';
import products from '@/routes/products';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Bell,
    BookOpen,
    ChevronsLeftRightEllipsis,
    Eye,
    FileText,
    Folder,
    FolderSync,
    Kanban,
    LayoutGrid,
    Package,
    Receipt,
    RotateCcw,
    Settings,
    Users2,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { can } = usePermissions();
    const page = usePage();
    const { unreadNotifications } = page.props as unknown as { unreadNotifications: number };

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
                          ...(can('list payments')
                              ? [
                                    {
                                        title: 'Pagos',
                                        href: '/payments',
                                        icon: RotateCcw,
                                    },
                                ]
                              : []),
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
                                        title: 'Productos y servicios',
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
                                        title: 'Ajustes de inventario',
                                        href: '/inventory-adjustments',
                                        icon: FolderSync,
                                    },
                                    {
                                        title: 'Transferencias',
                                        href: '/stock-transfers',
                                        icon: ChevronsLeftRightEllipsis,
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
                      icon: Eye,
                      items: [
                          {
                              title: 'Recetas',
                              href: prescriptions.index(),
                              icon: Eye,
                          },
                          {
                              title: 'Recetario de productos',
                              href: '/product-recipes',
                              icon: FileText,
                          },
                      ],
                  },
              ]
            : []),
        ...(can('view reports')
            ? [
                  {
                      title: 'Reportes',
                      href: '/reports',
                      icon: BarChart3,
                  },
              ]
            : []),
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

    const electronicInvoicingItems: NavItem[] = can('view electronic invoicing')
        ? [
              {
                  title: 'Recepción',
                  href: '/electronic-invoicing/received',
                  icon: FileText,
              },
          ]
        : [];

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

                {electronicInvoicingItems.length > 0 && (
                    <>
                        <SidebarSeparator className="my-2" />
                        <SidebarGroup className="pt-0">
                            <SidebarGroupLabel>Facturación electrónica</SidebarGroupLabel>
                            <SidebarMenu>
                                {electronicInvoicingItems.map((item) => (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            tooltip={{ children: item.title }}
                                            isActive={page.url.startsWith(typeof item.href === 'string' ? item.href : item.href?.url || '')}
                                        >
                                            <Link href={item.href!} prefetch>
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroup>
                    </>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
