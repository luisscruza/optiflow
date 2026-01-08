import { Head, Link } from '@inertiajs/react';
import { ClipboardList, DollarSign, FileText, Package, Search, Workflow } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reportes',
        href: '/reports',
    },
];

interface GroupStat {
    value: string;
    label: string;
    count: number;
}

interface RecentReport {
    id: number;
    type: string;
    name: string;
    description: string;
    group: string;
    groupLabel: string;
}

interface Props {
    groupStats: GroupStat[];
    recentReports: RecentReport[];
}

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

export default function ReportsIndex({ groupStats, recentReports }: Props) {
    const [searchQuery, setSearchQuery] = useState('');

    const filteredGroups = groupStats.filter((stat) => stat.label.toLowerCase().includes(searchQuery.toLowerCase()));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Reportes</h1>
                            <p className="text-muted-foreground">
                                Monitorea la distribución de tus ventas y obtén información para gestionar tus operaciones comerciales.
                            </p>
                        </div>
                        <Button asChild variant="outline">
                            <Link href="/reports/history">Historial de exportables</Link>
                        </Button>
                    </div>

                    {/* Search */}
                    <div className="flex items-center gap-4">
                        <div className="relative max-w-md flex-1">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Buscar reporte"
                                className="pl-9"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>

                    {/* Classification by category */}
                    <div>
                        <h2 className="mb-4 text-xl font-semibold">Clasificación por categoría</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {filteredGroups.map((stat) => {
                                const Icon = groupIcons[stat.value] || FileText;
                                const colorClass = groupColors[stat.value] || 'text-gray-600 bg-gray-50';

                                return (
                                    <Button key={stat.value} asChild variant="outline" className="h-auto p-0">
                                        <Link href={`/reports/group/${stat.value}`}>
                                            <Card className="w-full border-0 shadow-none">
                                                <CardHeader className="space-y-4">
                                                    <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${colorClass}`}>
                                                        <Icon className="h-6 w-6" />
                                                    </div>
                                                    <div>
                                                        <CardTitle className="text-left text-base">{stat.label}</CardTitle>
                                                        <CardDescription className="text-left">{stat.count} reportes</CardDescription>
                                                    </div>
                                                </CardHeader>
                                            </Card>
                                        </Link>
                                    </Button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Recent Reports */}
                    <div>
                        <h2 className="mb-4 text-xl font-semibold">Recientes</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {recentReports.map((report) => {
                                const Icon = groupIcons[report.group] || FileText;
                                const colorClass = groupColors[report.group] || 'text-gray-600 bg-gray-50';

                                return (
                                    <Button key={report.id} asChild variant="outline" className="h-auto p-0">
                                        <Link href={`/reports/${report.type}`}>
                                            <Card className="w-full border-0 shadow-none">
                                                <CardHeader className="space-y-3">
                                                    <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${colorClass}`}>
                                                        <Icon className="h-5 w-5" />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <CardTitle className="text-left text-sm font-medium">{report.name}</CardTitle>
                                                        <CardDescription className="text-left text-xs">{report.description}</CardDescription>
                                                    </div>
                                                </CardHeader>
                                            </Card>
                                        </Link>
                                    </Button>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
