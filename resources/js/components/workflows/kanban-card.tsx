import { router } from '@inertiajs/react';
import { AlertCircle, Building, Calendar, FileText, ImageIcon, MoreHorizontal, Pencil, Trash2, User } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type Workflow, type WorkflowJob, type WorkflowJobPriority } from '@/types';

interface KanbanCardProps {
    job: WorkflowJob;
    workflow: Workflow;
    onDragStart: (e: React.DragEvent, job: WorkflowJob) => void;
    onDragEnd: (e: React.DragEvent) => void;
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

export function KanbanCard({ job, workflow, onDragStart, onDragEnd }: KanbanCardProps) {
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editPriority, setEditPriority] = useState<WorkflowJobPriority | ''>(job.priority || '');
    const [editDueDate, setEditDueDate] = useState(job.due_date?.split('T')[0] || '');

    const handleEditJob = () => {
        router.patch(
            `/workflows/${workflow.id}/jobs/${job.id}`,
            {
                priority: editPriority || null,
                due_date: editDueDate || null,
            },
            {
                onSuccess: () => setIsEditDialogOpen(false),
            },
        );
    };

    const handleDeleteJob = () => {
        if (confirm('¿Estás seguro de que deseas eliminar esta tarea?')) {
            router.delete(`/workflows/${workflow.id}/jobs/${job.id}`);
        }
    };

    const formatDate = (dateString: string | null | undefined) => {
        if (!dateString) return null;
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: 'short',
        });
    };

    const isOverdue = job.due_date && new Date(job.due_date) < new Date() && !job.completed_at;

    const handleCardClick = (e: React.MouseEvent) => {
        // Don't navigate if clicking on dropdown or dragging
        if ((e.target as HTMLElement).closest('[data-no-navigate]')) {
            return;
        }
        router.visit(`/workflows/${workflow.id}/jobs/${job.id}`);
    };

    return (
        <>
            <Card
                draggable
                onDragStart={(e) => onDragStart(e, job)}
                onDragEnd={onDragEnd}
                onClick={handleCardClick}
                className="mb-2 cursor-grab transition-shadow hover:shadow-md active:cursor-grabbing"
            >
                <CardHeader className="flex flex-row items-start justify-between space-y-0 p-3 pb-2">
                    <div className="flex-1 space-y-1">
                        {job.invoice && (
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <FileText className="h-3 w-3" />
                                <span>Factura #{job.invoice.document_number}</span>
                            </div>
                        )}
                        <div className="flex items-center gap-1 text-sm font-medium">
                            <Building className="h-3 w-3" />
                            <span className="text-xs">{job.workspace.name}</span>
                        </div>{' '}
                        {job.contact && (
                            <div className="flex items-center gap-1 text-sm font-medium">
                                <User className="h-3 w-3" />
                                <span>{job.contact.name}</span>
                            </div>
                        )}
                        {!job.invoice && !job.contact && <span className="text-sm text-muted-foreground">Tarea #{job.id}</span>}
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-6 w-6 p-0" data-no-navigate>
                                <MoreHorizontal className="h-3 w-3" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" data-no-navigate>
                            <DropdownMenuItem onClick={() => setIsEditDialogOpen(true)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Editar
                            </DropdownMenuItem>
                            <DropdownMenuItem className="text-destructive" onClick={handleDeleteJob}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </CardHeader>

                <CardContent className="p-3 pt-0">
                    <div className="flex flex-wrap items-center gap-2">
                        {job.priority && (
                            <Badge variant="outline" className={priorityColors[job.priority]}>
                                {priorityLabels[job.priority]}
                            </Badge>
                        )}
                        {job.due_date && (
                            <Badge variant={isOverdue ? 'destructive' : 'outline'} className="flex items-center gap-1">
                                {isOverdue && <AlertCircle className="h-3 w-3" />}
                                <Calendar className="h-3 w-3" />
                                {formatDate(job.due_date)}
                            </Badge>
                        )}
                    </div>
                    {job.media && job.media.length > 0 && (
                        <div className="mt-2 flex items-center gap-1.5">
                            <div className="flex -space-x-2">
                                {job.media.slice(0, 3).map((media, index) => (
                                    <div
                                        key={media.id}
                                        className="relative h-8 w-8 overflow-hidden rounded-md border-2 border-background bg-muted"
                                        style={{ zIndex: 3 - index }}
                                    >
                                        <img
                                            src={media.preview_url || media.original_url}
                                            alt={media.file_name}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                ))}
                            </div>
                            {job.media.length > 3 && <span className="text-xs text-muted-foreground">+{job.media.length - 3}</span>}
                            {job.media.length <= 3 && (
                                <span className="flex items-center gap-0.5 text-xs text-muted-foreground">
                                    <ImageIcon className="h-3 w-3" />
                                    {job.media.length}
                                </span>
                            )}
                        </div>
                    )}
                    {job.comments && job.comments.length > 0 && (
                        <p className="mt-2 line-clamp-2 text-xs text-muted-foreground">{job.comments[0].comment}</p>
                    )}
                </CardContent>
            </Card>

            <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Editar tarea</DialogTitle>
                        <DialogDescription>Modifica los detalles de la tarea.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit-priority">Prioridad</Label>
                            <Select value={editPriority} onValueChange={(value) => setEditPriority(value as WorkflowJobPriority | '')}>
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
                            <Label htmlFor="edit-due-date">Fecha de Vencimiento</Label>
                            <Input id="edit-due-date" type="date" value={editDueDate} onChange={(e) => setEditDueDate(e.target.value)} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsEditDialogOpen(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handleEditJob}>Guardar Cambios</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
