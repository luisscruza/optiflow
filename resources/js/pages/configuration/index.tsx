import { Head, Link } from '@inertiajs/react';
import { Banknote, Building2, Calculator, ChevronRight, Coins, FileText, Hash, MapPin, Settings, Shield, Users } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
];

const configurationSections = [
    {
        title: 'Empresa',
        icon: Building2,
        items: [
            { title: 'Datos de la empresa', href: '/company-details', icon: Building2 },
            { title: 'Permisos', href: '/business/roles', icon: Shield },
            { title: 'Usuarios', href: '/business/users', icon: Users },
            { title: 'Sucursales', href: '/workspaces', icon: MapPin },
            { title: 'Monedas', href: '/currencies', icon: Coins },
            { title: 'Cuentas bancarias', href: '/bank-accounts', icon: Banknote },
        ],
    },
    {
        title: 'Facturación',
        icon: FileText,
        items: [
            { title: 'Numeraciones', href: '/document-subtypes', icon: Hash },
            { title: 'Vendedores', href: '/salesmen', icon: Users },
        ],
    },
    {
        title: 'Impuestos',
        icon: Calculator,
        items: [{ title: 'Impuestos', href: '/taxes', icon: Calculator }],
    },
];

export default function ConfigurationIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración" />

            <div className="max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                        <Settings className="h-5 w-5 text-gray-600 dark:text-gray-300" />
                    </div>
                    <h1 className="text-xl font-semibold text-gray-900 dark:text-gray-100">Configuración</h1>
                </div>

                {/* Configuration Sections */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {configurationSections.map((section) => {
                        const SectionIcon = section.icon;

                        return (
                            <Card key={section.title} className="overflow-hidden">
                                <CardHeader className="border-b bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <SectionIcon className="h-4 w-4" />
                                        {section.title}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="divide-y divide-gray-100 dark:divide-gray-700">
                                        {section.items.map((item) => {
                                            const ItemIcon = item.icon;
                                            return (
                                                <Link
                                                    key={item.title}
                                                    href={item.href}
                                                    className="flex items-center justify-between px-4 py-2.5 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <ItemIcon className="h-4 w-4 text-gray-400" />
                                                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">{item.title}</span>
                                                    </div>
                                                    <ChevronRight className="h-4 w-4 text-gray-400" />
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
