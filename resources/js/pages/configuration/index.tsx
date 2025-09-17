import { Head, Link } from '@inertiajs/react';
import { Calculator, FileText, Receipt, Settings } from 'lucide-react';

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
        title: 'Facturación',
        description: 'Configura la información que se mostrará en tus facturas.',
        icon: FileText,
        color: 'text-blue-600',
        bgColor: 'bg-blue-50',
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
        color: 'text-green-600',
        bgColor: 'bg-green-50',
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
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100">
                            <Settings className="h-6 w-6 text-gray-600" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">Configuración</h1>
                            <p className="text-sm text-gray-600">Configura la información de tu empresa y adapta Alegra a tu negocio.</p>
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
                                        <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${section.bgColor}`}>
                                            <IconComponent className={`h-6 w-6 ${section.color}`} />
                                        </div>
                                        <div className="flex-1">
                                            <CardTitle className="text-lg font-semibold text-gray-900">{section.title}</CardTitle>
                                            <CardDescription className="mt-1 text-sm text-gray-600">{section.description}</CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    <div className="space-y-3">
                                        {section.items.map((item) => (
                                            <Link
                                                key={item.title}
                                                href={item.href}
                                                className="block rounded-md border border-gray-200 p-3 transition-colors hover:bg-gray-50"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <h4 className="font-medium text-gray-900">{item.title}</h4>
                                                        <p className="text-sm text-gray-500">{item.description}</p>
                                                    </div>
                                                    <Receipt className="h-4 w-4 text-gray-400" />
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Additional Info */}
                <Card className="border-blue-200 bg-blue-50">
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                <Settings className="h-4 w-4 text-blue-600" />
                            </div>
                            <div>
                                <h3 className="font-medium text-blue-900">Centro Óptico Visión Integral</h3>
                                <p className="mt-1 text-sm text-blue-700">
                                    Identificación: 130382573 | Versión de Alegra: República Dominicana | Plan Actual: PLUS
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
