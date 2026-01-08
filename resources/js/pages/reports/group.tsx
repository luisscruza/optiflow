import { Head, Link } from '@inertiajs/react';
import { ClipboardList, DollarSign, FileText, Package, Workflow } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface Report {
    id: number;
    type: string;
    name: string;
    description: string;
    group: string;
}

interface Group {
    value: string;
    label: string;
}

interface Props {
    group: Group;
    reports: Report[];
}

const breadcrumbs = (groupLabel: string): BreadcrumbItem[] => [
    {
        title: 'Reportes',
        href: '/reports',
    },
    {
        title: groupLabel,
        href: '#',
    },
];

const groupIcons: Record<string, typeof DollarSign> = {
    sales: DollarSign,
    prescriptions: ClipboardList,
    workflow: Workflow,
    inventory: Package,
};

const groupColors: Record<string, string> = {
    sales: 'text-green-600 bg-green-50 dark:bg-green-950 dark:text-green-400',
    prescriptions: 'text-pink-600 bg-pink-50 dark:bg-pink-950 dark:text-pink-400',
    workflow: 'text-purple-600 bg-purple-50 dark:bg-purple-950 dark:text-purple-400',
    inventory: 'text-yellow-600 bg-yellow-50 dark:bg-yellow-950 dark:text-yellow-400',
};

export default function ReportsGroup({ group, reports }: Props) {
    const Icon = groupIcons[group.value] || FileText;
    const colorClass = groupColors[group.value] || 'text-gray-600 bg-gray-50';

    return (
        <AppLayout breadcrumbs={breadcrumbs(group.label)}>
            <Head title={group.label} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center gap-4">
                        <div className={`flex h-14 w-14 items-center justify-center rounded-lg ${colorClass}`}>
                            <Icon className="h-7 w-7" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{group.label}</h1>
                            <p className="text-muted-foreground">
                                Monitorea la distribución de tus ventas y obtén información para gestionar tus operaciones comerciales.
                            </p>
                        </div>
                    </div>

                    {/* Reports Grid */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {reports.map((report) => (
                            <Button key={report.id} asChild variant="outline" className="h-auto p-0">
                                <Link href={`/reports/${report.type}`}>
                                    <Card className="w-full border-0 shadow-none">
                                        <CardHeader className="space-y-3">
                                            <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${colorClass}`}>
                                                <Icon className="h-5 w-5" />
                                            </div>
                                            <div className="space-y-1">
                                                <CardTitle className="text-left text-base font-semibold">{report.name}</CardTitle>
                                                <CardDescription className="text-left text-sm">{report.description}</CardDescription>
                                            </div>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            </Button>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
