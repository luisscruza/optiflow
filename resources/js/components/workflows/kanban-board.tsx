import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type Contact, type Invoice, type Workflow, type WorkflowJob, type WorkflowJobPriority } from '@/types';

import { KanbanColumn } from './kanban-column';

interface KanbanBoardProps {
    workflow: Workflow;
    invoices?: Invoice[];
    contacts?: Contact[];
}

export function KanbanBoard({ workflow, invoices = [], contacts = [] }: KanbanBoardProps) {
    const [draggedJob, setDraggedJob] = useState<WorkflowJob | null>(null);
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [selectedStageId, setSelectedStageId] = useState<string | null>(null);
    const [isAddStageDialogOpen, setIsAddStageDialogOpen] = useState(false);

    // New job form state
    const [newJobInvoiceId, setNewJobInvoiceId] = useState<string>('');
    const [newJobContactId, setNewJobContactId] = useState<string>('');
    const [newJobPriority, setNewJobPriority] = useState<WorkflowJobPriority | ''>('');
    const [newJobDueDate, setNewJobDueDate] = useState('');
    const [newJobNotes, setNewJobNotes] = useState('');

    // New stage form state
    const [newStageName, setNewStageName] = useState('');
    const [newStageDescription, setNewStageDescription] = useState('');
    const [newStageColor, setNewStageColor] = useState('#3B82F6');

    const handleDragStart = (e: React.DragEvent, job: WorkflowJob) => {
        setDraggedJob(job);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(job.id));
        // Add a custom drag image effect
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
        if (!selectedStageId) return;

        router.post(
            `/workflows/${workflow.id}/jobs`,
            {
                workflow_stage_id: selectedStageId,
                invoice_id: newJobInvoiceId || null,
                contact_id: newJobContactId || null,
                priority: newJobPriority || null,
                due_date: newJobDueDate || null,
                notes: newJobNotes || null,
            },
            {
                onSuccess: () => {
                    setIsCreateDialogOpen(false);
                    resetNewJobForm();
                },
            },
        );
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
        setNewJobInvoiceId('');
        setNewJobContactId('');
        setNewJobPriority('');
        setNewJobDueDate('');
        setNewJobNotes('');
        setSelectedStageId(null);
    };

    const resetNewStageForm = () => {
        setNewStageName('');
        setNewStageDescription('');
        setNewStageColor('#3B82F6');
    };

    const totalStages = workflow.stages?.length || 0;

    return (
        <>
            <div className="flex h-full gap-4 overflow-x-auto pb-4">
                {workflow.stages?.map((stage) => (
                    <KanbanColumn
                        key={stage.id}
                        workflow={workflow}
                        stage={stage}
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
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nueva tarea</DialogTitle>
                        <DialogDescription>Agrega una nueva tarea al flujo de trabajo.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="job-invoice">Factura (opcional)</Label>
                            <Select value={newJobInvoiceId} onValueChange={setNewJobInvoiceId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar factura" />
                                </SelectTrigger>
                                <SelectContent>
                                    {invoices.map((invoice) => (
                                        <SelectItem key={invoice.id} value={invoice.id.toString()}>
                                            #{invoice.document_number} - {invoice.contact?.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="job-contact">Contacto (opcional)</Label>
                            <Select value={newJobContactId} onValueChange={setNewJobContactId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar contacto" />
                                </SelectTrigger>
                                <SelectContent>
                                    {contacts.map((contact) => (
                                        <SelectItem key={contact.id} value={contact.id.toString()}>
                                            {contact.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

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
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsCreateDialogOpen(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handleSubmitNewJob}>Crear tarea</Button>
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
