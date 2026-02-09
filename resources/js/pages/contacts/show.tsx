import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    Building2,
    Calendar,
    ChevronRight,
    ClipboardList,
    CreditCard,
    Edit,
    Eye,
    FileText,
    Glasses,
    Mail,
    MapPin,
    MessageSquare,
    Phone,
    ReceiptText,
    Settings2,
    Trash2,
    TrendingUp,
    Users,
    Workflow,
} from 'lucide-react';
import { useState } from 'react';

import { CommentList } from '@/components/CommentList';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Address, ContactStats, Invoice, Prescription, Quotation, SharedData, WorkflowJob, type BreadcrumbItem, type Contact } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Props {
    contact: Contact;
    invoices: Invoice[];
    quotations: Quotation[];
    prescriptions: Prescription[];
    workflowJobs: WorkflowJob[];
    stats: ContactStats;
}

export default function ContactShow({ contact, invoices, quotations, prescriptions, workflowJobs, stats }: Props) {
    const { auth } = usePage<SharedData>().props;
    const { can } = usePermissions();
    const { format: formatCurrency } = useCurrency();
    const [activeTab, setActiveTab] = useState('overview');

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Contactos',
            href: '/contacts',
        },
        {
            title: contact.name,
            href: `/contacts/${contact.id}`,
        },
    ];

    const handleDelete = () => {
        if (confirm('¿Estás seguro de que deseas eliminar este contacto? Esta acción no se puede deshacer.')) {
            router.delete(`/contacts/${contact.id}`);
        }
    };

    const getContactTypeConfig = (type: string) => {
        const configs: Record<string, { icon: React.ReactNode; label: string; color: string; bgColor: string }> = {
            customer: {
                icon: <Users className="h-4 w-4" />,
                label: 'Cliente',
                color: 'text-blue-700 dark:text-blue-400',
                bgColor: 'bg-blue-100 dark:bg-blue-900/30',
            },
            supplier: {
                icon: <Building2 className="h-4 w-4" />,
                label: 'Proveedor',
                color: 'text-purple-700 dark:text-purple-400',
                bgColor: 'bg-purple-100 dark:bg-purple-900/30',
            },
            optometrist: {
                icon: <Glasses className="h-4 w-4" />,
                label: 'Optometrista',
                color: 'text-yellow-700 dark:text-yellow-400',
                bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
            },
        };
        return configs[type] || configs.customer;
    };

    const getStatusConfig = (status: string) => {
        return status === 'active'
            ? { label: 'Activo', variant: 'default' as const, className: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }
            : { label: 'Inactivo', variant: 'secondary' as const, className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' };
    };

    const formatAddress = (address: Address | null | undefined) => {
        if (!address) return null;
        const parts = [];
        if (address.description) parts.push(address.description);
        if (address.municipality) parts.push(address.municipality);
        if (address.province) parts.push(address.province);
        if (address.country) parts.push(address.country);
        return parts.length > 0 ? parts.join(', ') : null;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getInvoiceStatusBadge = (status: string) => {
        const configs: Record<string, { label: string; className: string }> = {
            draft: { label: 'Borrador', className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' },
            sent: { label: 'Enviada', className: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
            paid: { label: 'Pagada', className: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
            partially_paid: { label: 'Parcial', className: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' },
            pending_payment: { label: 'Pendiente', className: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
            overdue: { label: 'Vencida', className: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
            cancelled: { label: 'Cancelada', className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' },
        };
        const config = configs[status] || configs.draft;
        return <Badge className={cn('font-medium', config.className)}>{config.label}</Badge>;
    };

    const getQuotationStatusBadge = (status: string) => {
        const configs: Record<string, { label: string; className: string }> = {
            draft: { label: 'Borrador', className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' },
            sent: { label: 'Enviada', className: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
            converted: { label: 'Convertida', className: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
            non_converted: { label: 'No convertida', className: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' },
        };
        const config = configs[status] || configs.draft;
        return <Badge className={cn('font-medium', config.className)}>{config.label}</Badge>;
    };

    const getPriorityBadge = (priority: string | null | undefined) => {
        const configs: Record<string, { label: string; className: string }> = {
            low: { label: 'Baja', className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' },
            medium: { label: 'Media', className: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
            high: { label: 'Alta', className: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
            urgent: { label: 'Urgente', className: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
        };
        const config = configs[priority || 'medium'] || configs.medium;
        return <Badge className={cn('font-medium', config.className)}>{config.label}</Badge>;
    };

    const contactTypeConfig = getContactTypeConfig(contact.contact_type);
    const statusConfig = getStatusConfig(contact.status);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    // Summary cards data
    const summaryCards = [
        {
            title: 'Total facturado',
            value: formatCurrency(stats.total_invoiced),
            icon: <TrendingUp className="h-5 w-5" />,
            description: `${stats.total_invoices} facturas`,
            color: 'text-blue-600 dark:text-blue-400',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            title: 'Total cobrado',
            value: formatCurrency(stats.total_paid),
            icon: <CreditCard className="h-5 w-5" />,
            description: 'Pagos recibidos',
            color: 'text-green-600 dark:text-green-400',
            bgColor: 'bg-green-50 dark:bg-green-900/20',
        },
        {
            title: 'Pendiente',
            value: formatCurrency(stats.pending_amount),
            icon: <ReceiptText className="h-5 w-5" />,
            description: 'Por cobrar',
            color: 'text-amber-600 dark:text-amber-400',
            bgColor: 'bg-amber-50 dark:bg-amber-900/20',
        },
        {
            title: 'Procesos activos',
            value: stats.pending_workflow_jobs.toString(),
            icon: <Workflow className="h-5 w-5" />,
            description: `de ${stats.total_workflow_jobs} total`,
            color: 'text-purple-600 dark:text-purple-400',
            bgColor: 'bg-purple-50 dark:bg-purple-900/20',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${contact.name} - Contacto`} />

            <div className="min-h-screen bg-gray-50/50 dark:bg-gray-950">
                {/* Header Section */}
                <div className="border-b bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            {/* Contact Info */}
                            <div className="flex items-start gap-4">
                                <Avatar className="h-16 w-16 border-2 border-gray-200 dark:border-gray-700">
                                    <AvatarFallback className={cn('text-lg font-semibold', contactTypeConfig.bgColor, contactTypeConfig.color)}>
                                        {getInitials(contact.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{contact.name}</h1>
                                        <Badge className={cn('flex items-center gap-1', contactTypeConfig.bgColor, contactTypeConfig.color)}>
                                            {contactTypeConfig.icon}
                                            {contactTypeConfig.label}
                                        </Badge>
                                        <Badge className={statusConfig.className}>{statusConfig.label}</Badge>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        {contact.email && (
                                            <a href={`mailto:${contact.email}`} className="flex items-center gap-1 hover:text-blue-600">
                                                <Mail className="h-4 w-4" />
                                                {contact.email}
                                            </a>
                                        )}
                                        {contact.phone_primary && (
                                            <a href={`tel:${contact.phone_primary}`} className="flex items-center gap-1 hover:text-blue-600">
                                                <Phone className="h-4 w-4" />
                                                {contact.phone_primary}
                                            </a>
                                        )}
                                        {contact.identification_object && (
                                            <span className="flex items-center gap-1">
                                                <FileText className="h-4 w-4" />
                                                {contact.identification_object.type}: {contact.identification_object.number}
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-500">
                                        <Calendar className="h-3 w-3" />
                                        Cliente desde {formatDate(contact.created_at)}
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex flex-wrap gap-2">
                                {can('edit contacts') && (
                                    <Button variant="outline" asChild>
                                        <Link href={`/contacts/${contact.id}/edit`}>
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </Link>
                                    </Button>
                                )}
                                {can('delete contacts') && (
                                    <Button variant="outline" className="text-red-600 hover:bg-red-50 hover:text-red-700" onClick={handleDelete}>
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Eliminar
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {summaryCards.map((card, index) => (
                            <Card key={index} className="border-0 shadow-sm">
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between">
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">{card.title}</p>
                                            <p className="text-2xl font-bold text-gray-900 dark:text-white">{card.value}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-500">{card.description}</p>
                                        </div>
                                        <div className={cn('rounded-lg p-2', card.bgColor)}>
                                            <span className={card.color}>{card.icon}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Tabs Content */}
                <div className="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                        <TabsList className="inline-flex h-auto gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                            <TabsTrigger
                                value="overview"
                                className="flex items-center gap-2 rounded-md px-4 py-2 data-[state=active]:bg-white data-[state=active]:shadow-sm dark:data-[state=active]:bg-gray-900"
                            >
                                <Activity className="h-4 w-4" />
                                Resumen
                            </TabsTrigger>
                            <TabsTrigger
                                value="invoices"
                                className="flex items-center gap-2 rounded-md px-4 py-2 data-[state=active]:bg-white data-[state=active]:shadow-sm dark:data-[state=active]:bg-gray-900"
                            >
                                <ReceiptText className="h-4 w-4" />
                                Facturas
                                {stats.total_invoices > 0 && (
                                    <Badge variant="secondary" className="ml-1 h-5 px-1.5 text-xs">
                                        {stats.total_invoices}
                                    </Badge>
                                )}
                            </TabsTrigger>
                            <TabsTrigger
                                value="quotations"
                                className="flex items-center gap-2 rounded-md px-4 py-2 data-[state=active]:bg-white data-[state=active]:shadow-sm dark:data-[state=active]:bg-gray-900"
                            >
                                <ClipboardList className="h-4 w-4" />
                                Cotizaciones
                                {stats.total_quotations > 0 && (
                                    <Badge variant="secondary" className="ml-1 h-5 px-1.5 text-xs">
                                        {stats.total_quotations}
                                    </Badge>
                                )}
                            </TabsTrigger>
                            <TabsTrigger
                                value="prescriptions"
                                className="flex items-center gap-2 rounded-md px-4 py-2 data-[state=active]:bg-white data-[state=active]:shadow-sm dark:data-[state=active]:bg-gray-900"
                            >
                                <Glasses className="h-4 w-4" />
                                Recetas
                                {stats.total_prescriptions > 0 && (
                                    <Badge variant="secondary" className="ml-1 h-5 px-1.5 text-xs">
                                        {stats.total_prescriptions}
                                    </Badge>
                                )}
                            </TabsTrigger>
                            <TabsTrigger
                                value="workflows"
                                className="flex items-center gap-2 rounded-md px-4 py-2 data-[state=active]:bg-white data-[state=active]:shadow-sm dark:data-[state=active]:bg-gray-900"
                            >
                                <Workflow className="h-4 w-4" />
                                Procesos
                                {stats.pending_workflow_jobs > 0 && (
                                    <Badge variant="secondary" className="ml-1 h-5 px-1.5 text-xs">
                                        {stats.pending_workflow_jobs}
                                    </Badge>
                                )}
                            </TabsTrigger>
                            <TabsTrigger
                                value="comments"
                                className="flex items-center gap-2 rounded-md px-4 py-2 data-[state=active]:bg-white data-[state=active]:shadow-sm dark:data-[state=active]:bg-gray-900"
                            >
                                <MessageSquare className="h-4 w-4" />
                                Comentarios
                            </TabsTrigger>
                        </TabsList>

                        {/* Overview Tab */}
                        <TabsContent value="overview" className="space-y-6">
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                {/* Main Content */}
                                <div className="space-y-6 lg:col-span-2">
                                    {/* Contact Details Card */}
                                    <Card className="border-0 shadow-sm">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2 text-lg">
                                                <Phone className="h-5 w-5 text-gray-500" />
                                                Información de Contacto
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                {contact.email && (
                                                    <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                        <Mail className="h-5 w-5 text-gray-400" />
                                                        <div>
                                                            <p className="text-xs text-gray-500">Email</p>
                                                            <a
                                                                href={`mailto:${contact.email}`}
                                                                className="text-sm font-medium text-blue-600 hover:underline"
                                                            >
                                                                {contact.email}
                                                            </a>
                                                        </div>
                                                    </div>
                                                )}
                                                {contact.phone_primary && (
                                                    <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                        <Phone className="h-5 w-5 text-gray-400" />
                                                        <div>
                                                            <p className="text-xs text-gray-500">Teléfono Principal</p>
                                                            <a
                                                                href={`tel:${contact.phone_primary}`}
                                                                className="text-sm font-medium text-blue-600 hover:underline"
                                                            >
                                                                {contact.phone_primary}
                                                            </a>
                                                        </div>
                                                    </div>
                                                )}
                                                {contact.phone_secondary && (
                                                    <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                        <Phone className="h-5 w-5 text-gray-400" />
                                                        <div>
                                                            <p className="text-xs text-gray-500">Teléfono Secundario</p>
                                                            <a
                                                                href={`tel:${contact.phone_secondary}`}
                                                                className="text-sm font-medium text-blue-600 hover:underline"
                                                            >
                                                                {contact.phone_secondary}
                                                            </a>
                                                        </div>
                                                    </div>
                                                )}
                                                {contact.mobile && (
                                                    <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                        <Phone className="h-5 w-5 text-gray-400" />
                                                        <div>
                                                            <p className="text-xs text-gray-500">Móvil</p>
                                                            <a
                                                                href={`tel:${contact.mobile}`}
                                                                className="text-sm font-medium text-blue-600 hover:underline"
                                                            >
                                                                {contact.mobile}
                                                            </a>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                            {!contact.email && !contact.phone_primary && !contact.phone_secondary && !contact.mobile && (
                                                <div className="py-8 text-center text-gray-500">No hay información de contacto disponible</div>
                                            )}
                                        </CardContent>
                                    </Card>

                                    {/* Address Card */}
                                    {contact.primary_address && (
                                        <Card className="border-0 shadow-sm">
                                            <CardHeader className="pb-3">
                                                <CardTitle className="flex items-center gap-2 text-lg">
                                                    <MapPin className="h-5 w-5 text-gray-500" />
                                                    Dirección
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                                                    {formatAddress(contact.primary_address) || 'No hay dirección registrada'}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    {/* Recent Activity */}
                                    <Card className="border-0 shadow-sm">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2 text-lg">
                                                <Activity className="h-5 w-5 text-gray-500" />
                                                Actividad reciente
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="space-y-4">
                                                {/* Recent Invoices Preview */}
                                                {invoices.slice(0, 3).map((invoice) => (
                                                    <div
                                                        key={invoice.id}
                                                        className="flex items-center justify-between rounded-lg border border-gray-100 p-3 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/50"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                                                                <ReceiptText className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                            </div>
                                                            <div>
                                                                <p className="text-sm font-medium">{invoice.document_number}</p>
                                                                <p className="text-xs text-gray-500">{formatDate(invoice.issue_date)}</p>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-3">
                                                            <div className="text-right">
                                                                <p className="text-sm font-semibold">{formatCurrency(invoice.total_amount)}</p>
                                                                {getInvoiceStatusBadge(invoice.status)}
                                                            </div>
                                                            <Link href={`/invoices/${invoice.id}`}>
                                                                <ChevronRight className="h-4 w-4 text-gray-400" />
                                                            </Link>
                                                        </div>
                                                    </div>
                                                ))}

                                                {/* Recent Workflow Jobs Preview */}
                                                {workflowJobs.slice(0, 2).map((job) => (
                                                    <div
                                                        key={job.id}
                                                        className="flex items-center justify-between rounded-lg border border-gray-100 p-3 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/50"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                                                                <Workflow className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                                            </div>
                                                            <div>
                                                                <p className="text-sm font-medium">{job.workflow?.name || 'Proceso'}</p>
                                                                <p className="text-xs text-gray-500">{job.workflow_stage?.name || 'Etapa actual'}</p>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-3">{getPriorityBadge(job.priority)}</div>
                                                    </div>
                                                ))}

                                                {invoices.length === 0 && workflowJobs.length === 0 && (
                                                    <div className="py-8 text-center text-gray-500">No hay actividad reciente</div>
                                                )}

                                                {(invoices.length > 3 || workflowJobs.length > 2) && (
                                                    <Button variant="ghost" className="w-full" onClick={() => setActiveTab('invoices')}>
                                                        Ver toda la actividad
                                                        <ArrowUpRight className="ml-2 h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Sidebar */}
                                <div className="space-y-6">
                                    {/* Identification Card */}
                                    <Card className="border-0 shadow-sm">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2 text-lg">
                                                <FileText className="h-5 w-5 text-gray-500" />
                                                Identificación
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            {contact.identification_object ? (
                                                <div className="space-y-3">
                                                    <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                        <p className="text-xs text-gray-500">Tipo de Documento</p>
                                                        <p className="font-medium">{contact.identification_object.type}</p>
                                                    </div>
                                                    <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                        <p className="text-xs text-gray-500">Número</p>
                                                        <p className="font-mono font-medium">{contact.identification_object.number}</p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="py-4 text-center text-gray-500">Sin identificación registrada</div>
                                            )}
                                        </CardContent>
                                    </Card>

                                    {/* Financial Info Card - Enhanced Credit Status */}
                                    <Card className="border-0 shadow-sm">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2 text-lg">
                                                <CreditCard className="h-5 w-5 text-gray-500" />
                                                Estado de Crédito
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {/* Credit Limit vs Amount Due */}
                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                                                    <p className="text-xs font-medium text-blue-600 dark:text-blue-400">Límite de Crédito</p>
                                                    <p className="text-lg font-bold text-blue-700 dark:text-blue-300">
                                                        {formatCurrency(contact.credit_limit || 0)}
                                                    </p>
                                                </div>
                                                <div
                                                    className={cn(
                                                        'rounded-lg p-3',
                                                        stats.pending_amount > (contact.credit_limit || 0) && (contact.credit_limit || 0) > 0
                                                            ? 'bg-red-50 dark:bg-red-900/20'
                                                            : 'bg-amber-50 dark:bg-amber-900/20',
                                                    )}
                                                >
                                                    <p
                                                        className={cn(
                                                            'text-xs font-medium',
                                                            stats.pending_amount > (contact.credit_limit || 0) && (contact.credit_limit || 0) > 0
                                                                ? 'text-red-600 dark:text-red-400'
                                                                : 'text-amber-600 dark:text-amber-400',
                                                        )}
                                                    >
                                                        Saldo pendiente
                                                    </p>
                                                    <p
                                                        className={cn(
                                                            'text-lg font-bold',
                                                            stats.pending_amount > (contact.credit_limit || 0) && (contact.credit_limit || 0) > 0
                                                                ? 'text-red-700 dark:text-red-300'
                                                                : 'text-amber-700 dark:text-amber-300',
                                                        )}
                                                    >
                                                        {formatCurrency(stats.pending_amount)}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Credit Usage Bar */}
                                            {(contact.credit_limit || 0) > 0 && (
                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between text-xs">
                                                        <span className="text-gray-500">Uso de crédito</span>
                                                        <span
                                                            className={cn(
                                                                'font-medium',
                                                                stats.pending_amount > (contact.credit_limit || 0)
                                                                    ? 'text-red-600 dark:text-red-400'
                                                                    : stats.pending_amount > (contact.credit_limit || 0) * 0.8
                                                                      ? 'text-amber-600 dark:text-amber-400'
                                                                      : 'text-green-600 dark:text-green-400',
                                                            )}
                                                        >
                                                            {Math.min(100, Math.round((stats.pending_amount / (contact.credit_limit || 1)) * 100))}%
                                                        </span>
                                                    </div>
                                                    <div className="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                        <div
                                                            className={cn(
                                                                'h-full rounded-full transition-all',
                                                                stats.pending_amount > (contact.credit_limit || 0)
                                                                    ? 'bg-red-500'
                                                                    : stats.pending_amount > (contact.credit_limit || 0) * 0.8
                                                                      ? 'bg-amber-500'
                                                                      : 'bg-green-500',
                                                            )}
                                                            style={{
                                                                width: `${Math.min(100, (stats.pending_amount / (contact.credit_limit || 1)) * 100)}%`,
                                                            }}
                                                        />
                                                    </div>
                                                    <div className="flex items-center justify-between text-xs text-gray-500">
                                                        <span>
                                                            Disponible:{' '}
                                                            {formatCurrency(Math.max(0, (contact.credit_limit || 0) - stats.pending_amount))}
                                                        </span>
                                                        {Number(stats.pending_amount) > Number(contact.credit_limit || 0) && (
                                                            <Badge className="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                                Excedido
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            )}

                                            {/* No credit limit message */}
                                            {(contact.credit_limit || 0) === 0 && (
                                                <p className="text-xs text-gray-500 italic">Sin límite de crédito definido</p>
                                            )}
                                        </CardContent>
                                    </Card>

                                    {/* Notes Card */}
                                    {contact.observations && (
                                        <Card className="border-0 shadow-sm">
                                            <CardHeader className="pb-3">
                                                <CardTitle className="flex items-center gap-2 text-lg">
                                                    <FileText className="h-5 w-5 text-gray-500" />
                                                    Observaciones
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                                    <p className="text-sm whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                                                        {contact.observations}
                                                    </p>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    {/* Quick Stats */}
                                    <Card className="border-0 shadow-sm">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2 text-lg">
                                                <Settings2 className="h-5 w-5 text-gray-500" />
                                                Estadísticas
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="space-y-3">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">Facturas</span>
                                                    <Badge variant="secondary">{stats.total_invoices}</Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">Cotizaciones</span>
                                                    <Badge variant="secondary">{stats.total_quotations}</Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">Recetas</span>
                                                    <Badge variant="secondary">{stats.total_prescriptions}</Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">Procesos</span>
                                                    <Badge variant="secondary">{stats.total_workflow_jobs}</Badge>
                                                </div>
                                                <Separator className="my-2" />
                                                <div className="space-y-1 text-xs text-gray-500">
                                                    <p>Creado: {formatDate(contact.created_at)}</p>
                                                    <p>Actualizado: {formatDate(contact.updated_at)}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        </TabsContent>

                        {/* Invoices Tab */}
                        <TabsContent value="invoices" className="space-y-4">
                            <Card className="border-0 shadow-sm">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <ReceiptText className="h-5 w-5" />
                                                Historial de Facturas
                                            </CardTitle>
                                            <CardDescription>{stats.total_invoices} facturas en total</CardDescription>
                                        </div>
                                        <Button asChild>
                                            <Link href={`/invoices/create?contact_id=${contact.id}`}>Nueva Factura</Link>
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {invoices.length > 0 ? (
                                        <div className="space-y-3">
                                            {invoices.map((invoice) => (
                                                <Link
                                                    key={invoice.id}
                                                    href={`/invoices/${invoice.id}`}
                                                    className="flex items-center justify-between rounded-lg border border-gray-100 p-4 transition-all hover:border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:border-gray-700 dark:hover:bg-gray-800/50"
                                                >
                                                    <div className="flex items-center gap-4">
                                                        <div className="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/30">
                                                            <ReceiptText className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-gray-900 dark:text-white">{invoice.document_number}</p>
                                                            <p className="text-sm text-gray-500">
                                                                {invoice.document_subtype?.name || 'Factura'} • {formatDate(invoice.issue_date)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-4">
                                                        <div className="text-right">
                                                            <p className="text-lg font-bold text-gray-900 dark:text-white">
                                                                {formatCurrency(invoice.total_amount)}
                                                            </p>
                                                            {invoice.amount_due > 0 && (
                                                                <p className="text-xs text-amber-600">
                                                                    Pendiente: {formatCurrency(invoice.amount_due)}
                                                                </p>
                                                            )}
                                                        </div>
                                                        {getInvoiceStatusBadge(invoice.status)}
                                                        <Eye className="h-4 w-4 text-gray-400" />
                                                    </div>
                                                </Link>
                                            ))}
                                            {stats.total_invoices > 10 && (
                                                <div className="pt-4 text-center">
                                                    <Button variant="outline" asChild>
                                                        <Link href={`/invoices?contact_id=${contact.id}`}>
                                                            Ver todas las facturas ({stats.total_invoices})
                                                            <ArrowUpRight className="ml-2 h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center">
                                            <ReceiptText className="mx-auto h-12 w-12 text-gray-300" />
                                            <p className="mt-4 text-gray-500">No hay facturas registradas</p>
                                            <Button className="mt-4" asChild>
                                                <Link href={`/invoices/create?contact_id=${contact.id}`}>Crear primera factura</Link>
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Quotations Tab */}
                        <TabsContent value="quotations" className="space-y-4">
                            <Card className="border-0 shadow-sm">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <ClipboardList className="h-5 w-5" />
                                                Historial de Cotizaciones
                                            </CardTitle>
                                            <CardDescription>{stats.total_quotations} cotizaciones en total</CardDescription>
                                        </div>
                                        <Button asChild>
                                            <Link href={`/quotations/create?contact_id=${contact.id}`}>Nueva cotización</Link>
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {quotations.length > 0 ? (
                                        <div className="space-y-3">
                                            {quotations.map((quotation) => (
                                                <Link
                                                    key={quotation.id}
                                                    href={`/quotations/${quotation.id}`}
                                                    className="flex items-center justify-between rounded-lg border border-gray-100 p-4 transition-all hover:border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:border-gray-700 dark:hover:bg-gray-800/50"
                                                >
                                                    <div className="flex items-center gap-4">
                                                        <div className="rounded-lg bg-amber-100 p-3 dark:bg-amber-900/30">
                                                            <ClipboardList className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-gray-900 dark:text-white">{quotation.document_number}</p>
                                                            <p className="text-sm text-gray-500">
                                                                {quotation.document_subtype?.name || 'Cotización'} •{' '}
                                                                {formatDate(quotation.issue_date)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-4">
                                                        <div className="text-right">
                                                            <p className="text-lg font-bold text-gray-900 dark:text-white">
                                                                {formatCurrency(quotation.total_amount)}
                                                            </p>
                                                        </div>
                                                        {getQuotationStatusBadge(quotation.status)}
                                                        <Eye className="h-4 w-4 text-gray-400" />
                                                    </div>
                                                </Link>
                                            ))}
                                            {stats.total_quotations > 10 && (
                                                <div className="pt-4 text-center">
                                                    <Button variant="outline" asChild>
                                                        <Link href={`/quotations?contact_id=${contact.id}`}>
                                                            Ver todas las cotizaciones ({stats.total_quotations})
                                                            <ArrowUpRight className="ml-2 h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center">
                                            <ClipboardList className="mx-auto h-12 w-12 text-gray-300" />
                                            <p className="mt-4 text-gray-500">No hay cotizaciones registradas</p>
                                            <Button className="mt-4" asChild>
                                                <Link href={`/quotations/create?contact_id=${contact.id}`}>Crear primera cotización</Link>
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Prescriptions Tab */}
                        <TabsContent value="prescriptions" className="space-y-4">
                            <Card className="border-0 shadow-sm">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <Glasses className="h-5 w-5" />
                                                Historial de Recetas
                                            </CardTitle>
                                            <CardDescription>{stats.total_prescriptions} recetas en total</CardDescription>
                                        </div>
                                        <Button asChild>
                                            <Link href={`/prescriptions/create?patient_id=${contact.id}`}>Nueva Receta</Link>
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {prescriptions.length > 0 ? (
                                        <div className="space-y-3">
                                            {prescriptions.map((prescription) => (
                                                <Link
                                                    key={prescription.id}
                                                    href={`/prescriptions/${prescription.id}`}
                                                    className="flex items-center justify-between rounded-lg border border-gray-100 p-4 transition-all hover:border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:border-gray-700 dark:hover:bg-gray-800/50"
                                                >
                                                    <div className="flex items-center gap-4">
                                                        <div className="rounded-lg bg-yellow-100 p-3 dark:bg-yellow-900/30">
                                                            <Glasses className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-gray-900 dark:text-white">Receta #{prescription.id}</p>
                                                            <p className="text-sm text-gray-500">
                                                                {prescription.optometrist?.name || 'Sin optometrista'} •{' '}
                                                                {prescription.human_readable_date || formatDate(prescription.created_at)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-4">
                                                        <Badge variant="outline">Ver detalles</Badge>
                                                        <Eye className="h-4 w-4 text-gray-400" />
                                                    </div>
                                                </Link>
                                            ))}
                                            {stats.total_prescriptions > 10 && (
                                                <div className="pt-4 text-center">
                                                    <Button variant="outline" asChild>
                                                        <Link href={`/prescriptions?patient_id=${contact.id}`}>
                                                            Ver todas las recetas ({stats.total_prescriptions})
                                                            <ArrowUpRight className="ml-2 h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center">
                                            <Glasses className="mx-auto h-12 w-12 text-gray-300" />
                                            <p className="mt-4 text-gray-500">No hay recetas registradas</p>
                                            <Button className="mt-4" asChild>
                                                <Link href={`/prescriptions/create?patient_id=${contact.id}`}>Crear primera receta</Link>
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Workflows Tab */}
                        <TabsContent value="workflows" className="space-y-4">
                            <Card className="border-0 shadow-sm">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <Workflow className="h-5 w-5" />
                                                Procesos de Trabajo
                                            </CardTitle>
                                            <CardDescription>
                                                {stats.pending_workflow_jobs} activos de {stats.total_workflow_jobs} total
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {workflowJobs.length > 0 ? (
                                        <div className="space-y-3">
                                            {workflowJobs.map((job) => (
                                                <div
                                                    key={job.id}
                                                    className="flex items-center justify-between rounded-lg border border-gray-100 p-4 transition-all hover:border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:border-gray-700 dark:hover:bg-gray-800/50"
                                                >
                                                    <div className="flex items-center gap-4">
                                                        <div
                                                            className="rounded-lg p-3"
                                                            style={{
                                                                backgroundColor: job.workflow_stage?.color
                                                                    ? `${job.workflow_stage.color}20`
                                                                    : 'rgb(243 244 246)',
                                                            }}
                                                        >
                                                            <Workflow
                                                                className="h-5 w-5"
                                                                style={{
                                                                    color: job.workflow_stage?.color || 'rgb(107 114 128)',
                                                                }}
                                                            />
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-gray-900 dark:text-white">
                                                                {job.workflow?.name || 'Proceso'}
                                                            </p>
                                                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                                                <Badge
                                                                    variant="outline"
                                                                    style={{
                                                                        borderColor: job.workflow_stage?.color,
                                                                        color: job.workflow_stage?.color,
                                                                    }}
                                                                >
                                                                    {job.workflow_stage?.name || 'Sin etapa'}
                                                                </Badge>
                                                                <span>•</span>
                                                                <span>{formatDate(job.created_at)}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-3">
                                                        {getPriorityBadge(job.priority)}
                                                        {job.due_date && (
                                                            <div className="text-right">
                                                                <p className="text-xs text-gray-500">Vence</p>
                                                                <p className="text-sm font-medium">{formatDate(job.due_date)}</p>
                                                            </div>
                                                        )}
                                                        {job.completed_at && (
                                                            <Badge className="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                                Completado
                                                            </Badge>
                                                        )}
                                                        {job.canceled_at && (
                                                            <Badge className="bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                                                Cancelado
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                            {stats.total_workflow_jobs > 10 && (
                                                <div className="pt-4 text-center">
                                                    <Button variant="outline" asChild>
                                                        <Link href={`/workflows?contact_id=${contact.id}`}>
                                                            Ver todos los procesos ({stats.total_workflow_jobs})
                                                            <ArrowUpRight className="ml-2 h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center">
                                            <Workflow className="mx-auto h-12 w-12 text-gray-300" />
                                            <p className="mt-4 text-gray-500">No hay procesos registrados</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Comments Tab */}
                        <TabsContent value="comments" className="space-y-4">
                            <Card className="border-0 shadow-sm">
                                <CardContent className="p-6">
                                    <CommentList
                                        comments={contact.comments || []}
                                        commentableType="Contact"
                                        commentableId={contact.id}
                                        currentUser={auth.user}
                                        title="Comentarios del contacto"
                                    />
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
