import { Head, Link } from '@inertiajs/react';
import { Building2, Calculator, FileText, Receipt, Settings, Users } from 'lucide-react';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
        description: 'Configura la información básica de tu empresa.',
        icon: Building2,
        color: 'text-gray-800',
        bgColor: 'bg-gray-50',
        items: [
            {
                title: 'Datos de la empresa',
                description: 'Nombre, dirección, contacto',
                href: '/company-details',
            },
                {
                title: 'Permisos',
                description: 'Gestiona los permisos de tu empresa',
                href: '/business/roles',    
            },
            {
                title: 'Usuarios',
                description: 'Gestiona los usuarios de tu empresa',
                href: '/business/users',    
            },
            {
                title: 'Sucursales',
                description: 'Gestiona las sucursales de tu empresa',
                href: '/workspaces',
            },
            {
                title: 'Monedas',
                description: 'Gestiona las monedas y tasas de cambio',
                href: '/currencies',
            },
            {
                title: 'Cuentas bancarias',
                description: 'Administra tus cuentas y transacciones',
                href: '/bank-accounts',
            },
        ],
    },
    {
        title: 'Facturación',
        description: 'Configura la información que se mostrará en tus facturas.',
        icon: FileText,
        color: 'text-gray-800',
        bgColor: 'bg-gray-50',
        items: [
            {
                title: 'Numeraciones',
                description: 'Configuración de documentos',
                href: '/document-subtypes',
            },
        ],
    },
    {
        title: 'Impuestos',
        description: 'Define aquí los tipos de impuestos y retenciones que aplican a tus facturas.',
        icon: Calculator,
        color: 'text-gray-800',
        bgColor: 'bg-gray-50',
        items: [
            {
                title: 'Impuestos',
                description: 'Retenciones',
                href: '/taxes',
            },
        ],
    },
];

export default function ConfigurationIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración" />

            <div className="max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                            <Settings className="h-6 w-6 text-gray-600 dark:text-gray-300" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">Configuración</h1>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Configura la información de tu empresa y adapta Alegra a tu negocio.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Configuration Sections */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2">
                    {configurationSections.map((section) => {
                        const IconComponent = section.icon;

                        return (
                            <Card key={section.title} className="transition-shadow hover:shadow-md">
                                <CardHeader className="pb-4">
                                    <div className="flex items-start gap-4">
                                        <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${section.bgColor} dark:bg-gray-700`}>
                                            <IconComponent className={`h-6 w-6 ${section.color} dark:text-gray-200`} />
                                        </div>
                                        <div className="flex-1">
                                            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{section.title}</CardTitle>
                                            <CardDescription className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {section.description}
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    <div className="space-y-3">
                                        {section.items.map((item) => (
                                            <Link
                                                key={item.title}
                                                href={item.href}
                                                className="block rounded-md border border-gray-200 bg-white p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-transparent dark:hover:bg-gray-800"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <h4 className="font-medium text-gray-900 dark:text-gray-100">{item.title}</h4>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">{item.description}</p>
                                                    </div>
                                                    <Receipt className="h-4 w-4 text-gray-400 dark:text-gray-400" />
                                                </div>
                                            </Link>
                                        ))}
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
