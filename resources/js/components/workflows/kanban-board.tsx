import { router } from '@inertiajs/react';
import { Loader2, Plus } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ImageUpload } from '@/components/ui/image-upload';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    type Contact,
    type CursorPaginatedData,
    type Invoice,
    type Prescription,
    type Workflow,
    type WorkflowJob,
    type WorkflowJobPriority,
} from '@/types';

import { DynamicFields } from './dynamic-fields';
import { KanbanColumn } from './kanban-column';

interface KanbanBoardProps {
    workflow: Workflow;
    stageJobs: Record<string, CursorPaginatedData<WorkflowJob>>;
    contacts?: Contact[];
    invoices?: Invoice[];
    prescriptions?: Prescription[];
}

export function KanbanBoard({ workflow, stageJobs, contacts = [], invoices = [], prescriptions = [] }: KanbanBoardProps) {
    const [draggedJob, setDraggedJob] = useState<WorkflowJob | null>(null);
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [selectedStageId, setSelectedStageId] = useState<string | null>(null);
    const [isAddStageDialogOpen, setIsAddStageDialogOpen] = useState(false);
    const [isLoadingContactData, setIsLoadingContactData] = useState(false);

    // New job form state
    const [newJobContactId, setNewJobContactId] = useState<string>('');
    const [newJobInvoiceId, setNewJobInvoiceId] = useState<string>('');
    const [newJobPrescriptionId, setNewJobPrescriptionId] = useState<string>('');
    const [newJobPriority, setNewJobPriority] = useState<WorkflowJobPriority | ''>('');
    const [newJobDueDate, setNewJobDueDate] = useState('');
    const [newJobNotes, setNewJobNotes] = useState('');
    const [newJobMetadata, setNewJobMetadata] = useState<Record<string, string | number | null>>({});
    const [newJobImages, setNewJobImages] = useState<File[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // New stage form state
    const [newStageName, setNewStageName] = useState('');
    const [newStageDescription, setNewStageDescription] = useState('');
    const [newStageColor, setNewStageColor] = useState('#3B82F6');

    const handleContactChange = (contactId: string) => {
        setNewJobContactId(contactId);
        setNewJobInvoiceId('');
        setNewJobPrescriptionId('');

        if (contactId) {
            setIsLoadingContactData(true);
            router.reload({
                only: ['invoices', 'prescriptions'],
                data: { contact_id: contactId },
                onFinish: () => setIsLoadingContactData(false),
            });
        }
    };

    const handleDragStart = (e: React.DragEvent, job: WorkflowJob) => {
        setDraggedJob(job);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(job.id));
        if (e.currentTarget instanceof HTMLElement) {
            e.currentTarget.style.opacity = '0.5';
        }
    };

    const handleDragEnd = (e: React.DragEvent) => {
        if (e.currentTarget instanceof HTMLElement) {
            e.currentTarget.style.opacity = '1';
        }
        setDraggedJob(null);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = (e: React.DragEvent, targetStageId: string) => {
        e.preventDefault();

        if (draggedJob && draggedJob.workflow_stage_id !== targetStageId) {
            router.patch(`/workflows/${workflow.id}/jobs/${draggedJob.id}/move`, {
                workflow_stage_id: targetStageId,
            });
        }

        setDraggedJob(null);
    };

    const handleCreateJob = (stageId: string) => {
        setSelectedStageId(stageId);
        setIsCreateDialogOpen(true);
    };

    const handleSubmitNewJob = () => {
        if (!selectedStageId || !newJobContactId) return;

        // Validate required invoice
        if (workflow.invoice_requirement === 'required' && !newJobInvoiceId) {
            // TODO: Show validation error
            return;
        }

        // Validate required prescription
        if (workflow.prescription_requirement === 'required' && !newJobPrescriptionId) {
            // TODO: Show validation error
            return;
        }

        // Validate required metadata fields
        const requiredFields = workflow.fields?.filter((f) => f.is_required) ?? [];
        const missingRequired = requiredFields.some((f) => !newJobMetadata[f.key]);
        if (missingRequired) {
            // TODO: Show validation error
            return;
        }

        setIsSubmitting(true);

        // Build form data for file uploads
        const formData: Record<string, unknown> = {
            workflow_stage_id: selectedStageId,
            contact_id: newJobContactId,
            invoice_id: newJobInvoiceId || null,
            prescription_id: newJobPrescriptionId || null,
            priority: newJobPriority || null,
            due_date: newJobDueDate || null,
            notes: newJobNotes || null,
            metadata: Object.keys(newJobMetadata).length > 0 ? newJobMetadata : null,
        };

        // Add images to form data - Inertia handles FormData conversion automatically
        if (newJobImages.length > 0) {
            formData.images = newJobImages;
        }

        router.post(`/workflows/${workflow.id}/jobs`, formData, {
            forceFormData: newJobImages.length > 0,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                resetNewJobForm();
            },
            onFinish: () => {
                setIsSubmitting(false);
            },
        });
    };

    const handleSubmitNewStage = () => {
        router.post(
            `/workflows/${workflow.id}/stages`,
            {
                name: newStageName,
                description: newStageDescription,
                color: newStageColor,
                position: (workflow.stages?.length || 0) + 1,
            },
            {
                onSuccess: () => {
                    setIsAddStageDialogOpen(false);
                    resetNewStageForm();
                },
            },
        );
    };

    const resetNewJobForm = () => {
        setNewJobContactId('');
        setNewJobInvoiceId('');
        setNewJobPrescriptionId('');
        setNewJobPriority('');
        setNewJobDueDate('');
        setNewJobNotes('');
        setNewJobMetadata({});
        setNewJobImages([]);
        setSelectedStageId(null);
    };

    const resetNewStageForm = () => {
        setNewStageName('');
        setNewStageDescription('');
        setNewStageColor('#3B82F6');
    };

    const totalStages = workflow.stages?.length || 0;

    // Helper to get prop name for a stage's jobs
    const getStagePropName = (stageId: string) => `stage_${stageId.replace(/-/g, '_')}_jobs`;

    return (
        <>
            <div className="flex h-full gap-4 overflow-x-auto pb-4">
                {workflow.stages?.map((stage) => (
                    <KanbanColumn
                        key={stage.id}
                        workflow={workflow}
                        stage={stage}
                        jobs={stageJobs[stage.id]?.data ?? []}
                        stageJobsPropName={getStagePropName(stage.id)}
                        totalStages={totalStages}
                        onDragOver={handleDragOver}
                        onDrop={handleDrop}
                        onDragStart={handleDragStart}
                        onDragEnd={handleDragEnd}
                        onCreateJob={handleCreateJob}
                    />
                ))}

                {/* Add Stage Button */}
                <div className="flex w-80 flex-shrink-0 items-start">
                    <Button variant="outline" className="w-full justify-start" onClick={() => setIsAddStageDialogOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar etapa
                    </Button>
                </div>
            </div>

            {/* Create Job Dialog */}
            <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Nueva tarea</DialogTitle>
                        <DialogDescription>Agrega una nueva tarea al flujo de trabajo.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 h-[75vh] overflow-y-auto pr-2">
                        {/* Contact Selection - Required and First */}
                        <div className="space-y-2">
                            <Label htmlFor="job-contact">
                                Cliente <span className="text-destructive">*</span>
                            </Label>
                            <Select value={newJobContactId} onValueChange={handleContactChange}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar cliente" />
                                </SelectTrigger>
                                <SelectContent>
                                    {contacts.map((contact) => (
                                        <SelectItem key={contact.id} value={contact.id.toString()}>
                                            {contact.name}
                                            {contact.identification_number && (
                                                <span className="ml-2 text-muted-foreground">({contact.identification_number})</span>
                                            )}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Invoice Selection - Filtered by Contact */}
                        {workflow.invoice_requirement && (
                            <div className="space-y-2">
                                <Label htmlFor="job-invoice">
                                    Factura{' '}
                                    {workflow.invoice_requirement === 'required' ? (
                                        <span className="text-destructive">*</span>
                                    ) : (
                                        <span className="text-muted-foreground">(opcional)</span>
                                    )}
                                </Label>
                                <Select
                                    value={newJobInvoiceId}
                                    onValueChange={setNewJobInvoiceId}
                                    disabled={!newJobContactId || isLoadingContactData}
                                >
                                    <SelectTrigger>
                                        {isLoadingContactData ? (
                                            <div className="flex items-center gap-2">
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                <span>Cargando...</span>
                                            </div>
                                        ) : (
                                            <SelectValue placeholder={newJobContactId ? 'Seleccionar factura' : 'Primero seleccione un cliente'} />
                                        )}
                                    </SelectTrigger>
                                    <SelectContent>
                                        {invoices.length === 0 ? (
                                            <div className="px-2 py-4 text-center text-sm text-muted-foreground">
                                                No hay facturas para este cliente
                                            </div>
                                        ) : (
                                            invoices.map((invoice) => (
                                                <SelectItem key={invoice.id} value={invoice.id.toString()}>
                                                    #{invoice.document_number} - ${Number(invoice.total_amount).toLocaleString('es-DO')}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {/* Prescription Selection - Filtered by Contact */}
                        {workflow.prescription_requirement && (
                            <div className="space-y-2">
                                <Label htmlFor="job-prescription">
                                    Receta{' '}
                                    {workflow.prescription_requirement === 'required' ? (
                                        <span className="text-destructive">*</span>
                                    ) : (
                                        <span className="text-muted-foreground">(opcional)</span>
                                    )}
                                </Label>
                                <Select
                                    value={newJobPrescriptionId}
                                    onValueChange={setNewJobPrescriptionId}
                                    disabled={!newJobContactId || isLoadingContactData}
                                >
                                    <SelectTrigger>
                                        {isLoadingContactData ? (
                                            <div className="flex items-center gap-2">
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                <span>Cargando...</span>
                                            </div>
                                        ) : (
                                            <SelectValue placeholder={newJobContactId ? 'Seleccionar receta' : 'Primero seleccione un cliente'} />
                                        )}
                                    </SelectTrigger>
                                    <SelectContent>
                                        {prescriptions.length === 0 ? (
                                            <div className="px-2 py-4 text-center text-sm text-muted-foreground">
                                                No hay recetas para este cliente
                                            </div>
                                        ) : (
                                            prescriptions.map((prescription) => (
                                                <SelectItem key={prescription.id} value={prescription.id.toString()}>
                                                    Receta #{prescription.id} - {prescription.human_readable_date}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="job-priority">Prioridad</Label>
                            <Select value={newJobPriority} onValueChange={(v) => setNewJobPriority(v as WorkflowJobPriority | '')}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar prioridad" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Baja</SelectItem>
                                    <SelectItem value="medium">Media</SelectItem>
                                    <SelectItem value="high">Alta</SelectItem>
                                    <SelectItem value="urgent">Urgente</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="job-due-date">Fecha de vencimiento</Label>
                            <Input id="job-due-date" type="date" value={newJobDueDate} onChange={(e) => setNewJobDueDate(e.target.value)} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="job-notes">Notas</Label>
                            <Textarea
                                id="job-notes"
                                value={newJobNotes}
                                onChange={(e) => setNewJobNotes(e.target.value)}
                                placeholder="Agregar notas o comentarios..."
                            />
                        </div>

                        {workflow.fields && workflow.fields.length > 0 && (
                            <DynamicFields
                                fields={workflow.fields}
                                values={newJobMetadata}
                                onChange={(key, value) => setNewJobMetadata((prev) => ({ ...prev, [key]: value }))}
                            />
                        )}

                        {/* Image Upload */}
                        <div className="space-y-2">
                            <Label>Imágenes</Label>
                            <ImageUpload value={newJobImages} onChange={setNewJobImages} maxFiles={10} disabled={isSubmitting} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsCreateDialogOpen(false)} disabled={isSubmitting}>
                            Cancelar
                        </Button>
                        <Button onClick={handleSubmitNewJob} disabled={!newJobContactId || isSubmitting}>
                            {isSubmitting ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Creando...
                                </>
                            ) : (
                                'Crear tarea'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Add Stage Dialog */}
            <Dialog open={isAddStageDialogOpen} onOpenChange={setIsAddStageDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nueva etapa</DialogTitle>
                        <DialogDescription>Agrega una nueva etapa al flujo de trabajo.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="stage-name">Nombre</Label>
                            <Input
                                id="stage-name"
                                value={newStageName}
                                onChange={(e) => setNewStageName(e.target.value)}
                                placeholder="Ej: En proceso"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="stage-description">Descripción</Label>
                            <Textarea
                                id="stage-description"
                                value={newStageDescription}
                                onChange={(e) => setNewStageDescription(e.target.value)}
                                placeholder="Descripción de la etapa..."
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="stage-color">Color</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="stage-color"
                                    type="color"
                                    value={newStageColor}
                                    onChange={(e) => setNewStageColor(e.target.value)}
                                    className="h-10 w-20 cursor-pointer"
                                />
                                <Input
                                    value={newStageColor}
                                    onChange={(e) => setNewStageColor(e.target.value)}
                                    placeholder="#3B82F6"
                                    className="flex-1"
                                />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsAddStageDialogOpen(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handleSubmitNewStage} disabled={!newStageName}>
                            Crear etapa
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
