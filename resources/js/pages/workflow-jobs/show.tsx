import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Clock,
    Edit,
    FileText,
    History,
    ImageIcon,
    LayoutGrid,
    Loader2,
    Receipt,
    Settings2,
    User,
    X,
} from 'lucide-react';
import { useState } from 'react';

import { CommentList } from '@/components/CommentList';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ImageUpload } from '@/components/ui/image-upload';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type Media,
    type SharedData,
    type Workflow,
    type WorkflowEvent,
    type WorkflowField,
    type WorkflowJob,
    type WorkflowJobPriority,
    type WorkflowStage,
} from '@/types';
import { useCurrency } from '@/utils/currency';

interface Props {
    workflow: Workflow;
    job: WorkflowJob;
    events?: WorkflowEvent[];
}

const priorityColors: Record<WorkflowJobPriority, string> = {
    low: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    urgent: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const priorityLabels: Record<WorkflowJobPriority, string> = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    urgent: 'Urgente',
};

export default function WorkflowJobShow({ workflow, job, events }: Props) {
    const { format: formatCurrency } = useCurrency();
    const { auth } = usePage<SharedData>().props;
    const [contactOpen, setContactOpen] = useState(false);
    const [invoiceOpen, setInvoiceOpen] = useState(false);
    const [prescriptionOpen, setPrescriptionOpen] = useState(false);
    const [notesOpen, setNotesOpen] = useState(false);
    const [customFieldsOpen, setCustomFieldsOpen] = useState(true);
    const [historyOpen, setHistoryOpen] = useState(true);
    const [datesOpen, setDatesOpen] = useState(true);
    const [imagesOpen, setImagesOpen] = useState(true);
    const [dueDateInput, setDueDateInput] = useState<string>('');

    // Metadata editing state
    const [editableMetadata, setEditableMetadata] = useState<Record<string, string | number | boolean | null>>(job.metadata || {});
    const [isSavingMetadata, setIsSavingMetadata] = useState(false);

    // Images editing state
    const [newImages, setNewImages] = useState<File[]>([]);
    const [imagesToRemove, setImagesToRemove] = useState<number[]>([]);
    const [isSavingImages, setIsSavingImages] = useState(false);

    // Get existing media excluding those marked for removal
    const existingImages = (job.media || []).filter((m: Media) => !imagesToRemove.includes(m.id));

    const handleRemoveExistingImage = (mediaId: number) => {
        setImagesToRemove((prev) => [...prev, mediaId]);
    };

    const handleSaveImages = () => {
        if (newImages.length === 0 && imagesToRemove.length === 0) return;

        setIsSavingImages(true);

        const formData: Record<string, unknown> = {};

        if (newImages.length > 0) {
            formData.images = newImages;
        }

        if (imagesToRemove.length > 0) {
            formData.remove_images = imagesToRemove;
        }

        // Use POST with _method spoofing for file uploads with PATCH
        router.post(
            `/workflows/${workflow.id}/jobs/${job.id}`,
            {
                _method: 'patch',
                ...formData,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setNewImages([]);
                    setImagesToRemove([]);
                },
                onFinish: () => {
                    setIsSavingImages(false);
                },
            },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Procesos',
            href: '/workflows',
        },
        {
            title: workflow.name,
            href: `/workflows/${workflow.id}`,
        },
        {
            title: `Tarea`,
            href: '#',
        },
    ];

    const formatDate = (dateString: string | null | undefined): string => {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('es-DO', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatDateTime = (dateString: string | null | undefined): string => {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('es-DO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const isOverdue = job.due_date && new Date(job.due_date) < new Date() && !job.completed_at;

    const getPriorityLabel = (priority: string | null | undefined): string => {
        if (!priority) return 'Sin prioridad';
        return priorityLabels[priority as WorkflowJobPriority] || priority;
    };

    const getStatusBadge = () => {
        if (job.completed_at) {
            return (
                <Badge variant="default" className="bg-green-600">
                    <CheckCircle2 className="mr-1 h-3 w-3" />
                    Completada
                </Badge>
            );
        }
        if (job.canceled_at) {
            return <Badge variant="destructive">Cancelada</Badge>;
        }
        if (isOverdue) {
            return (
                <Badge variant="destructive">
                    <AlertCircle className="mr-1 h-3 w-3" />
                    Vencida
                </Badge>
            );
        }
        if (job.started_at) {
            return (
                <Badge variant="outline" className="border-blue-500 text-blue-600">
                    <Clock className="mr-1 h-3 w-3" />
                    En progreso
                </Badge>
            );
        }
        return <Badge variant="secondary">Pendiente</Badge>;
    };

    const handleMoveToStage = (stage: WorkflowStage) => {
        if (stage.id === job.workflow_stage_id) {
            return;
        }

        router.patch(
            `/workflows/${workflow.id}/jobs/${job.id}/move`,
            {
                workflow_stage_id: stage.id,
            },
            {
                preserveScroll: true,
            },
        );
    };

    const handleChangePriority = (priority: WorkflowJobPriority | null) => {
        router.patch(
            `/workflows/${workflow.id}/jobs/${job.id}`,
            {
                priority: priority,
            },
            {
                preserveScroll: true,
            },
        );
    };

    const handleChangeDueDate = (date: string | null) => {
        router.patch(
            `/workflows/${workflow.id}/jobs/${job.id}`,
            {
                due_date: date,
            },
            {
                preserveScroll: true,
            },
        );
    };

    const formatDateForInput = (dateString: string | null | undefined): string => {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    };

    const stages = workflow.stages || [];
    const currentStageIndex = stages.findIndex((s) => s.id === job.workflow_stage_id);
    const priorities: (WorkflowJobPriority | null)[] = [null, 'low', 'medium', 'high', 'urgent'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tarea - ${workflow.name}`} />

            <div className="min-h-screen bg-gray-50/30">
                <div className="flex h-[calc(100vh-8rem)] flex-col px-4 py-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardContent className="px-6 py-6">
                                <div className="flex items-start justify-between">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-3">
                                            <Link href={`/workflows/${workflow.id}`}>
                                                <Button variant="ghost" size="sm">
                                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                                    Volver
                                                </Button>
                                            </Link>
                                            <LayoutGrid className="h-6 w-6 text-primary" />
                                            <h1 className="text-2xl font-bold text-gray-900">{workflow.name}</h1>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            {getStatusBadge()}
                                            {job.priority && (
                                                <Badge variant="outline" className={priorityColors[job.priority]}>
                                                    {priorityLabels[job.priority]}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm text-gray-500">Creada</p>
                                        <p className="font-medium text-gray-900">{formatDate(job.created_at)}</p>
                                        {job.due_date && (
                                            <>
                                                <p className="mt-2 text-sm text-gray-500">Fecha límite</p>
                                                <p className={`font-medium ${isOverdue ? 'text-red-600' : 'text-gray-900'}`}>
                                                    {formatDate(job.due_date)}
                                                </p>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Stage Progress */}
                                {stages.length > 0 && (
                                    <div className="mt-6 border-t pt-6">
                                        <Label className="mb-3 block text-sm font-medium text-gray-700">Etapa del proceso</Label>
                                        <div className="flex items-center gap-2 overflow-x-auto pb-2">
                                            {stages.map((stage, index) => {
                                                const isCurrentStage = stage.id === job.workflow_stage_id;
                                                const isPastStage = index < currentStageIndex;
                                                const isFutureStage = index > currentStageIndex;

                                                return (
                                                    <div key={stage.id} className="flex items-center px-2 py-2.5">
                                                        <button
                                                            onClick={() => handleMoveToStage(stage)}
                                                            className={`flex items-center gap-2 rounded-lg border-2 px-4 py-2 text-sm font-medium transition-all hover:shadow-md ${
                                                                isCurrentStage
                                                                    ? 'ring-2 ring-offset-2'
                                                                    : isPastStage
                                                                      ? 'opacity-60 hover:opacity-100'
                                                                      : 'opacity-60 hover:opacity-100'
                                                            }`}
                                                            style={
                                                                {
                                                                    backgroundColor: isCurrentStage ? stage.color + '20' : 'transparent',
                                                                    borderColor: stage.color,
                                                                    color: stage.color,
                                                                    '--tw-ring-color': isCurrentStage ? 'gray' : undefined,
                                                                } as React.CSSProperties
                                                            }
                                                        >
                                                            {isPastStage && <CheckCircle2 className="h-4 w-4" />}
                                                            {stage.name}
                                                        </button>
                                                        {index < stages.length - 1 && (
                                                            <ChevronRight className="mx-1 h-5 w-5 flex-shrink-0 text-gray-300" />
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* Priority Selector */}
                                <div className="mt-6 border-t pt-6">
                                    <Label className="mb-3 block text-sm font-medium text-gray-700">Prioridad</Label>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {priorities.map((priority) => {
                                            const isSelected = job.priority === priority;
                                            const label = priority ? priorityLabels[priority] : 'Sin prioridad';
                                            const colorClass = priority ? priorityColors[priority] : 'bg-gray-50 text-gray-500';

                                            return (
                                                <button
                                                    key={priority || 'none'}
                                                    onClick={() => handleChangePriority(priority)}
                                                    className={`rounded-lg border-2 px-4 py-2 text-sm font-medium transition-all hover:shadow-md ${colorClass} ${
                                                        isSelected ? 'ring-2 ring-gray-400 ring-offset-2' : 'opacity-60 hover:opacity-100'
                                                    }`}
                                                >
                                                    {label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        {/* Main Content - Left Side */}
                        <div className="space-y-6 lg:col-span-2">
                            {/* Contact Information */}
                            {job.contact && (
                                <Collapsible open={contactOpen} onOpenChange={setContactOpen}>
                                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                        <CollapsibleTrigger asChild>
                                            <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                                <div className="flex items-center justify-between">
                                                    <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                                            <User className="h-4 w-4" />
                                                        </div>
                                                        Información del Contacto
                                                    </CardTitle>
                                                    <ChevronDown
                                                        className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${contactOpen ? 'rotate-180' : ''}`}
                                                    />
                                                </div>
                                            </CardHeader>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <CardContent className="px-6 py-6">
                                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div>
                                                        <Label className="text-sm font-medium text-gray-700">Nombre</Label>
                                                        <p className="mt-1 text-sm text-gray-900">{job.contact.name}</p>
                                                    </div>
                                                    {job.contact.email && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Email</Label>
                                                            <p className="mt-1 text-sm text-gray-900">{job.contact.email}</p>
                                                        </div>
                                                    )}
                                                    {job.contact.phone_primary && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Teléfono</Label>
                                                            <p className="mt-1 text-sm text-gray-900">{job.contact.phone_primary}</p>
                                                        </div>
                                                    )}
                                                    {job.contact.identification_number && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Identificación</Label>
                                                            <p className="mt-1 text-sm text-gray-900">{job.contact.identification_number}</p>
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="mt-4">
                                                    <Link href={`/contacts/${job.contact.id}`}>
                                                        <Button variant="outline" size="sm">
                                                            Ver perfil completo
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </CardContent>
                                        </CollapsibleContent>
                                    </Card>
                                </Collapsible>
                            )}

                            {/* Invoice Information */}
                            {job.invoice && (
                                <Collapsible open={invoiceOpen} onOpenChange={setInvoiceOpen}>
                                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                        <CollapsibleTrigger asChild>
                                            <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex flex-col gap-1.5">
                                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                                                <Receipt className="h-4 w-4" />
                                                            </div>
                                                            Factura asociada
                                                        </CardTitle>
                                                        <CardDescription>Factura #{job.invoice.document_number}</CardDescription>
                                                    </div>
                                                    <ChevronDown
                                                        className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${invoiceOpen ? 'rotate-180' : ''}`}
                                                    />
                                                </div>
                                            </CardHeader>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <CardContent className="px-6 py-6">
                                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div>
                                                        <Label className="text-sm font-medium text-gray-700">Número de documento</Label>
                                                        <p className="mt-1 text-sm text-gray-900">{job.invoice.document_number}</p>
                                                    </div>
                                                    {job.invoice.contact && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Cliente</Label>
                                                            <p className="mt-1 text-sm text-gray-900">{job.invoice.contact.name}</p>
                                                        </div>
                                                    )}
                                                    <div>
                                                        <Label className="text-sm font-medium text-gray-700">Fecha de emisión</Label>
                                                        <p className="mt-1 text-sm text-gray-900">{formatDate(job.invoice.issue_date)}</p>
                                                    </div>
                                                    <div>
                                                        <Label className="text-sm font-medium text-gray-700">Total</Label>
                                                        <p className="mt-1 text-lg font-semibold text-gray-900">
                                                            {formatCurrency(job.invoice.total_amount)}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <Label className="text-sm font-medium text-gray-700">Estado</Label>
                                                        <div className="mt-1">
                                                            <Badge
                                                                variant={job.invoice.status_config?.variant || 'secondary'}
                                                                className={job.invoice.status_config?.className}
                                                            >
                                                                {job.invoice.status_config?.label || job.invoice.status}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                    {job.invoice.amount_due > 0 && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Monto pendiente</Label>
                                                            <p className="mt-1 text-sm font-medium text-red-600">
                                                                {formatCurrency(job.invoice.amount_due)}
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Invoice Items */}
                                                {job.invoice.items && job.invoice.items.length > 0 && (
                                                    <>
                                                        <Separator className="my-4" />
                                                        <div>
                                                            <Label className="mb-3 block text-sm font-medium text-gray-700">Artículos</Label>
                                                            <div className="rounded-lg border">
                                                                <table className="w-full">
                                                                    <thead className="bg-gray-50">
                                                                        <tr>
                                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">
                                                                                Producto
                                                                            </th>
                                                                            <th className="px-4 py-2 text-center text-xs font-medium text-gray-500">
                                                                                Cant.
                                                                            </th>
                                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">
                                                                                Precio
                                                                            </th>
                                                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">
                                                                                Total
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody className="divide-y">
                                                                        {job.invoice.items.map((item) => (
                                                                            <tr key={item.id}>
                                                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                                                    {item.product?.name || item.description}
                                                                                </td>
                                                                                <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                    {item.quantity}
                                                                                </td>
                                                                                <td className="px-4 py-2 text-right text-sm text-gray-900">
                                                                                    {formatCurrency(item.unit_price)}
                                                                                </td>
                                                                                <td className="px-4 py-2 text-right text-sm font-medium text-gray-900">
                                                                                    {formatCurrency(item.total)}
                                                                                </td>
                                                                            </tr>
                                                                        ))}
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </>
                                                )}

                                                <div className="mt-4">
                                                    <Link href={`/invoices/${job.invoice.id}`}>
                                                        <Button variant="outline" size="sm">
                                                            Ver factura completa
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </CardContent>
                                        </CollapsibleContent>
                                    </Card>
                                </Collapsible>
                            )}

                            {/* Prescription Information */}
                            {job.prescription && (
                                <Collapsible open={prescriptionOpen} onOpenChange={setPrescriptionOpen}>
                                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                        <CollapsibleTrigger asChild>
                                            <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex flex-col gap-1.5">
                                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                                                <FileText className="h-4 w-4" />
                                                            </div>
                                                            Receta asociada
                                                        </CardTitle>
                                                        <CardDescription>Receta #{job.prescription.id}</CardDescription>
                                                    </div>
                                                    <ChevronDown
                                                        className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${prescriptionOpen ? 'rotate-180' : ''}`}
                                                    />
                                                </div>
                                            </CardHeader>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <CardContent className="px-6 py-6">
                                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    {job.prescription.patient && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Paciente</Label>
                                                            <p className="mt-1 text-sm text-gray-900">{job.prescription.patient.name}</p>
                                                        </div>
                                                    )}
                                                    {job.prescription.optometrist && (
                                                        <div>
                                                            <Label className="text-sm font-medium text-gray-700">Optometrista</Label>
                                                            <p className="mt-1 text-sm text-gray-900">{job.prescription.optometrist.name}</p>
                                                        </div>
                                                    )}
                                                    <div>
                                                        <Label className="text-sm font-medium text-gray-700">Fecha de creación</Label>
                                                        <p className="mt-1 text-sm text-gray-900">{formatDate(job.prescription.created_at)}</p>
                                                    </div>
                                                </div>

                                                {/* Basic prescription data preview */}
                                                {(job.prescription.refraccion_od_esfera ||
                                                    job.prescription.refraccion_od_cilindro ||
                                                    job.prescription.refraccion_oi_esfera ||
                                                    job.prescription.refraccion_oi_cilindro) && (
                                                    <>
                                                        <Separator className="my-4" />
                                                        <div>
                                                            <Label className="mb-3 block text-sm font-medium text-gray-700">Refracción</Label>
                                                            <div className="rounded-lg border">
                                                                <table className="w-full">
                                                                    <thead className="bg-gray-50">
                                                                        <tr>
                                                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">
                                                                                Ojo
                                                                            </th>
                                                                            <th className="px-4 py-2 text-center text-xs font-medium text-gray-500">
                                                                                Esfera
                                                                            </th>
                                                                            <th className="px-4 py-2 text-center text-xs font-medium text-gray-500">
                                                                                Cilindro
                                                                            </th>
                                                                            <th className="px-4 py-2 text-center text-xs font-medium text-gray-500">
                                                                                Eje
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody className="divide-y">
                                                                        <tr>
                                                                            <td className="px-4 py-2 text-sm font-medium text-gray-900">OD</td>
                                                                            <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                {job.prescription.refraccion_od_esfera || '-'}
                                                                            </td>
                                                                            <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                {job.prescription.refraccion_od_cilindro || '-'}
                                                                            </td>
                                                                            <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                {job.prescription.refraccion_od_eje || '-'}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td className="px-4 py-2 text-sm font-medium text-gray-900">OI</td>
                                                                            <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                {job.prescription.refraccion_oi_esfera || '-'}
                                                                            </td>
                                                                            <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                {job.prescription.refraccion_oi_cilindro || '-'}
                                                                            </td>
                                                                            <td className="px-4 py-2 text-center text-sm text-gray-900">
                                                                                {job.prescription.refraccion_oi_eje || '-'}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </>
                                                )}

                                                <div className="mt-4">
                                                    <Link href={`/prescriptions/${job.prescription.id}`}>
                                                        <Button variant="outline" size="sm">
                                                            Ver receta completa
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </CardContent>
                                        </CollapsibleContent>
                                    </Card>
                                </Collapsible>
                            )}

                            {/* Notes */}
                            {job.notes && (
                                <Collapsible open={notesOpen} onOpenChange={setNotesOpen}>
                                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                        <CollapsibleTrigger asChild>
                                            <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                                <div className="flex items-center justify-between">
                                                    <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600">
                                                            <Edit className="h-4 w-4" />
                                                        </div>
                                                        Notas
                                                    </CardTitle>
                                                    <ChevronDown
                                                        className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${notesOpen ? 'rotate-180' : ''}`}
                                                    />
                                                </div>
                                            </CardHeader>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <CardContent className="px-6 py-6">
                                                <div className="rounded-lg bg-gray-50 p-4">
                                                    <p className="text-sm whitespace-pre-wrap text-gray-700">{job.notes}</p>
                                                </div>
                                            </CardContent>
                                        </CollapsibleContent>
                                    </Card>
                                </Collapsible>
                            )}

                            {/* Images */}
                            <Collapsible open={imagesOpen} onOpenChange={setImagesOpen}>
                                <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                    <CollapsibleTrigger asChild>
                                        <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                            <div className="flex items-center justify-between">
                                                <div className="flex flex-col gap-1.5">
                                                    <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-pink-100 text-pink-600">
                                                            <ImageIcon className="h-4 w-4" />
                                                        </div>
                                                        Imágenes
                                                    </CardTitle>
                                                    {(job.media?.length || 0) > 0 && (
                                                        <CardDescription>
                                                            {job.media?.length} {job.media?.length === 1 ? 'imagen' : 'imágenes'}
                                                        </CardDescription>
                                                    )}
                                                </div>
                                                <ChevronDown
                                                    className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${imagesOpen ? 'rotate-180' : ''}`}
                                                />
                                            </div>
                                        </CardHeader>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <CardContent className="px-6 py-6">
                                            <ImageUpload
                                                value={newImages}
                                                onChange={setNewImages}
                                                existingMedia={existingImages}
                                                onRemoveExisting={handleRemoveExistingImage}
                                                maxFiles={10}
                                                disabled={isSavingImages}
                                                dropzoneCollapsible={true}
                                                dropzoneDefaultOpen={false}
                                            />

                                            {(newImages.length > 0 || imagesToRemove.length > 0) && (
                                                <div className="mt-4 flex items-center justify-between rounded-lg bg-amber-50 p-3">
                                                    <p className="text-sm text-amber-800">
                                                        {newImages.length > 0 && `${newImages.length} imagen(es) por agregar`}
                                                        {newImages.length > 0 && imagesToRemove.length > 0 && ' • '}
                                                        {imagesToRemove.length > 0 && `${imagesToRemove.length} imagen(es) por eliminar`}
                                                    </p>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                setNewImages([]);
                                                                setImagesToRemove([]);
                                                            }}
                                                            disabled={isSavingImages}
                                                        >
                                                            Cancelar
                                                        </Button>
                                                        <Button size="sm" onClick={handleSaveImages} disabled={isSavingImages}>
                                                            {isSavingImages ? (
                                                                <>
                                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                                    Guardando...
                                                                </>
                                                            ) : (
                                                                'Guardar cambios'
                                                            )}
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}
                                        </CardContent>
                                    </CollapsibleContent>
                                </Card>
                            </Collapsible>

                            {/* Custom Fields / Metadata */}
                            {workflow.fields && workflow.fields.length > 0 && (
                                <Collapsible open={customFieldsOpen} onOpenChange={setCustomFieldsOpen}>
                                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                        <CollapsibleTrigger asChild>
                                            <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                                <div className="flex items-center justify-between">
                                                    <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                                            <Settings2 className="h-4 w-4" />
                                                        </div>
                                                        Datos del trabajo
                                                    </CardTitle>
                                                    <ChevronDown
                                                        className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${customFieldsOpen ? 'rotate-180' : ''}`}
                                                    />
                                                </div>
                                            </CardHeader>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <CardContent className="px-6 py-6">
                                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    {workflow.fields.map((field: WorkflowField) => {
                                                        const currentValue = editableMetadata[field.key] ?? '';

                                                        const handleFieldChange = (value: string | number | null) => {
                                                            setEditableMetadata((prev) => ({
                                                                ...prev,
                                                                [field.key]: value,
                                                            }));
                                                        };

                                                        return (
                                                            <div key={field.id} className="space-y-2">
                                                                <Label className="text-sm font-medium text-gray-700">
                                                                    {field.name}
                                                                    {field.is_required && <span className="ml-1 text-red-500">*</span>}
                                                                </Label>

                                                                {field.type === 'text' && (
                                                                    <Input
                                                                        value={String(currentValue)}
                                                                        onChange={(e) => handleFieldChange(e.target.value)}
                                                                        placeholder={field.placeholder || ''}
                                                                    />
                                                                )}

                                                                {field.type === 'textarea' && (
                                                                    <Textarea
                                                                        value={String(currentValue)}
                                                                        onChange={(e) => handleFieldChange(e.target.value)}
                                                                        placeholder={field.placeholder || ''}
                                                                        rows={3}
                                                                    />
                                                                )}

                                                                {field.type === 'number' && (
                                                                    <Input
                                                                        type="number"
                                                                        value={String(currentValue)}
                                                                        onChange={(e) =>
                                                                            handleFieldChange(e.target.value ? Number(e.target.value) : null)
                                                                        }
                                                                        placeholder={field.placeholder || ''}
                                                                    />
                                                                )}

                                                                {field.type === 'date' && (
                                                                    <Input
                                                                        type="date"
                                                                        value={String(currentValue)}
                                                                        onChange={(e) => handleFieldChange(e.target.value)}
                                                                    />
                                                                )}

                                                                {field.type === 'select' && field.mastertable?.items && (
                                                                    <Select value={String(currentValue)} onValueChange={(v) => handleFieldChange(v)}>
                                                                        <SelectTrigger>
                                                                            <SelectValue placeholder={field.placeholder || 'Seleccionar...'} />
                                                                        </SelectTrigger>
                                                                        <SelectContent>
                                                                            {field.mastertable.items.map((item) => (
                                                                                <SelectItem key={item.id} value={String(item.id)}>
                                                                                    {item.name}
                                                                                </SelectItem>
                                                                            ))}
                                                                        </SelectContent>
                                                                    </Select>
                                                                )}
                                                            </div>
                                                        );
                                                    })}
                                                </div>

                                                <div className="mt-4 flex justify-end">
                                                    <Button
                                                        size="sm"
                                                        disabled={isSavingMetadata}
                                                        onClick={() => {
                                                            setIsSavingMetadata(true);
                                                            router.patch(
                                                                `/workflows/${workflow.id}/jobs/${job.id}`,
                                                                { metadata: editableMetadata },
                                                                {
                                                                    preserveScroll: true,
                                                                    onSuccess: () => setIsSavingMetadata(false),
                                                                    onError: () => setIsSavingMetadata(false),
                                                                },
                                                            );
                                                        }}
                                                    >
                                                        {isSavingMetadata ? 'Guardando...' : 'Guardar campos'}
                                                    </Button>
                                                </div>
                                            </CardContent>
                                        </CollapsibleContent>
                                    </Card>
                                </Collapsible>
                            )}

                            {/* Comments */}
                            <CommentList
                                comments={job.comments || []}
                                commentableType="WorkflowJob"
                                commentableId={job.id}
                                currentUser={auth.user}
                                title="Comentarios"
                            />
                        </div>

                        {/* Sidebar - Right Side */}
                        <div className="space-y-6">
                            {/* Timeline / Events */}
                            <Collapsible open={historyOpen} onOpenChange={setHistoryOpen}>
                                <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                    <CollapsibleTrigger asChild>
                                        <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                                        <History className="h-4 w-4" />
                                                    </div>
                                                    Historial de Cambios
                                                </CardTitle>
                                                <ChevronDown
                                                    className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${historyOpen ? 'rotate-180' : ''}`}
                                                />
                                            </div>
                                        </CardHeader>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <CardContent className="px-6 py-6">
                                            <Deferred
                                                data="events"
                                                fallback={
                                                    <div className="flex items-center justify-center py-8">
                                                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                                    </div>
                                                }
                                            >
                                                {events && events.length > 0 ? (
                                                    <div className="space-y-4">
                                                        {events.map((event, index) => (
                                                            <div key={event.id} className="relative">
                                                                {index !== events.length - 1 && (
                                                                    <div className="absolute top-8 left-3 h-full w-0.5 bg-gray-200" />
                                                                )}
                                                                <div className="flex gap-3">
                                                                    <div className="relative z-10 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100">
                                                                        {event.event_type === 'stage_changed' ? (
                                                                            <LayoutGrid className="h-3 w-3 text-indigo-600" />
                                                                        ) : event.event_type === 'priority_updated' ? (
                                                                            <AlertCircle className="h-3 w-3 text-indigo-600" />
                                                                        ) : (
                                                                            <Edit className="h-3 w-3 text-indigo-600" />
                                                                        )}
                                                                    </div>
                                                                    <div className="min-w-0 flex-1">
                                                                        <div className="flex items-center gap-2">
                                                                            <p className="text-sm font-medium text-gray-900">
                                                                                {event.event_type_label}
                                                                            </p>
                                                                            {event.user?.name && (
                                                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                                                    {event.user.name}
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                        {event.event_type === 'stage_changed' && (
                                                                            <p className="text-xs text-gray-600">
                                                                                {event.from_stage?.name || 'Inicio'} →{' '}
                                                                                {event.to_stage?.name || 'Final'}
                                                                            </p>
                                                                        )}
                                                                        {event.event_type === 'priority_updated' && event.metadata && (
                                                                            <p className="text-xs text-gray-600">
                                                                                {getPriorityLabel(event.metadata.from_priority)} →{' '}
                                                                                {getPriorityLabel(event.metadata.to_priority)}
                                                                            </p>
                                                                        )}
                                                                        <p className="mt-1 text-xs text-gray-500">
                                                                            Realizado por {event.user?.name && <span>{event.user.name}• </span>}
                                                                            {formatDateTime(event.created_at)}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <div className="py-4 text-center text-gray-500">
                                                        <History className="mx-auto mb-2 h-8 w-8 text-gray-300" />
                                                        <p className="text-sm">Sin historial de cambios</p>
                                                    </div>
                                                )}
                                            </Deferred>
                                        </CardContent>
                                    </CollapsibleContent>
                                </Card>
                            </Collapsible>

                            {/* Quick Info */}
                            <Collapsible open={datesOpen} onOpenChange={setDatesOpen}>
                                <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                                    <CollapsibleTrigger asChild>
                                        <CardHeader className="cursor-pointer bg-gray-50/50 px-6 py-5 transition-colors hover:bg-gray-100/50">
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                        <Calendar className="h-4 w-4" />
                                                    </div>
                                                    Fechas
                                                </CardTitle>
                                                <ChevronDown
                                                    className={`h-5 w-5 text-gray-500 transition-transform duration-200 ${datesOpen ? 'rotate-180' : ''}`}
                                                />
                                            </div>
                                        </CardHeader>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <CardContent className="px-6 py-6">
                                            <div className="space-y-3">
                                                <div>
                                                    <Label className="text-xs font-medium text-gray-500">Creada</Label>
                                                    <p className="text-sm text-gray-900">{formatDateTime(job.created_at)}</p>
                                                </div>
                                                {job.started_at && (
                                                    <div>
                                                        <Label className="text-xs font-medium text-gray-500">Iniciada</Label>
                                                        <p className="text-sm text-gray-900">{formatDateTime(job.started_at)}</p>
                                                    </div>
                                                )}
                                                <div>
                                                    <Label className="text-xs font-medium text-gray-500">Fecha límite</Label>
                                                    <div className="mt-1 flex items-center gap-2">
                                                        <div className="relative flex-1">
                                                            <CalendarDays className="absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                                            <Input
                                                                type="date"
                                                                value={dueDateInput || formatDateForInput(job.due_date)}
                                                                onChange={(e) => setDueDateInput(e.target.value)}
                                                                className={`h-9 pl-9 text-sm ${isOverdue && !dueDateInput ? 'border-red-300 text-red-600' : ''}`}
                                                            />
                                                        </div>
                                                        {(dueDateInput || !job.due_date) && (
                                                            <Button
                                                                variant="default"
                                                                size="sm"
                                                                className="h-9"
                                                                disabled={!dueDateInput}
                                                                onClick={() => {
                                                                    handleChangeDueDate(dueDateInput);
                                                                    setDueDateInput('');
                                                                }}
                                                            >
                                                                Establecer
                                                            </Button>
                                                        )}
                                                        {job.due_date && !dueDateInput && (
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-9 w-9 text-gray-400 hover:text-red-500"
                                                                onClick={() => handleChangeDueDate(null)}
                                                            >
                                                                <X className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                    {/* Quick date presets */}
                                                    <div className="mt-2 flex flex-wrap gap-1">
                                                        {[
                                                            { label: '+1d', days: 1 },
                                                            { label: '+2d', days: 2 },
                                                            { label: '+3d', days: 3 },
                                                            { label: '+7d', days: 7 },
                                                            { label: '+15d', days: 15 },
                                                            { label: '+30d', days: 30 },
                                                        ].map((preset) => {
                                                            const date = new Date();
                                                            date.setDate(date.getDate() + preset.days);
                                                            const dateStr = date.toISOString().split('T')[0];
                                                            return (
                                                                <Button
                                                                    key={preset.days}
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="h-7 px-2 text-xs"
                                                                    onClick={() => handleChangeDueDate(dateStr)}
                                                                >
                                                                    {preset.label}
                                                                </Button>
                                                            );
                                                        })}
                                                    </div>
                                                    {isOverdue && !dueDateInput && (
                                                        <p className="mt-1 text-xs text-red-500">Esta tarea está vencida</p>
                                                    )}
                                                </div>
                                                {job.completed_at && (
                                                    <div>
                                                        <Label className="text-xs font-medium text-gray-500">Completada</Label>
                                                        <p className="text-sm text-green-600">{formatDateTime(job.completed_at)}</p>
                                                    </div>
                                                )}
                                                {job.canceled_at && (
                                                    <div>
                                                        <Label className="text-xs font-medium text-gray-500">Cancelada</Label>
                                                        <p className="text-sm text-red-600">{formatDateTime(job.canceled_at)}</p>
                                                    </div>
                                                )}
                                            </div>
                                        </CardContent>
                                    </CollapsibleContent>
                                </Card>
                            </Collapsible>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <Button variant="outline" size="lg" asChild>
                            <Link href={`/workflows/${workflow.id}`} className="flex items-center justify-center gap-2">
                                <ArrowLeft className="h-4 w-4" />
                                Volver al proceso
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
