import { router } from '@inertiajs/react';
import { GripVertical, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type Workflow, type WorkflowJob, type WorkflowStage } from '@/types';

import { KanbanCard } from './kanban-card';

interface KanbanColumnProps {
    workflow: Workflow;
    stage: WorkflowStage;
    onDragOver: (e: React.DragEvent) => void;
    onDrop: (e: React.DragEvent, stageId: number) => void;
    onDragStart: (e: React.DragEvent, job: WorkflowJob) => void;
    onCreateJob: (stageId: number) => void;
}

export function KanbanColumn({ workflow, stage, onDragOver, onDrop, onDragStart, onCreateJob }: KanbanColumnProps) {
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editName, setEditName] = useState(stage.name);
    const [editDescription, setEditDescription] = useState(stage.description || '');
    const [editColor, setEditColor] = useState(stage.color);

    const handleEditStage = () => {
        router.patch(
            `/workflows/${workflow.id}/stages/${stage.id}`,
            {
                name: editName,
                description: editDescription,
                color: editColor,
            },
            {
                onSuccess: () => setIsEditDialogOpen(false),
            },
        );
    };

    const handleDeleteStage = () => {
        if (confirm('¿Estás seguro de que deseas eliminar esta etapa? Las tareas deben ser movidas o eliminadas primero.')) {
            router.delete(`/workflows/${workflow.id}/stages/${stage.id}`);
        }
    };

    const jobCount = stage.jobs?.length || 0;

    return (
        <>
            <Card
                className="flex h-full w-80 flex-shrink-0 flex-col"
                style={{ borderTopColor: stage.color, borderTopWidth: '3px' }}
                onDragOver={onDragOver}
                onDrop={(e) => onDrop(e, stage.id)}
            >
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <div className="flex items-center gap-2">
                        <GripVertical className="h-4 w-4 cursor-grab text-muted-foreground" />
                        <CardTitle className="text-sm font-medium">{stage.name}</CardTitle>
                        <Badge variant="secondary" className="ml-1">
                            {jobCount}
                        </Badge>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => onCreateJob(stage.id)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar Tarea
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsEditDialogOpen(true)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Editar Etapa
                            </DropdownMenuItem>
                            <DropdownMenuItem className="text-destructive" onClick={handleDeleteStage} disabled={stage.is_initial || stage.is_final}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar Etapa
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </CardHeader>

                <CardContent className="flex-1 space-y-2 overflow-y-auto">
                    {stage.jobs?.map((job) => (
                        <KanbanCard key={job.id} job={job} workflow={workflow} onDragStart={onDragStart} />
                    ))}

                    {jobCount === 0 && (
                        <div className="flex h-24 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25">
                            <p className="text-sm text-muted-foreground">Sin tareas</p>
                        </div>
                    )}
                </CardContent>

                <div className="border-t p-2">
                    <Button variant="ghost" size="sm" className="w-full justify-start" onClick={() => onCreateJob(stage.id)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Agregar Tarea
                    </Button>
                </div>
            </Card>

            <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Editar Etapa</DialogTitle>
                        <DialogDescription>Modifica los detalles de la etapa.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit-name">Nombre</Label>
                            <Input id="edit-name" value={editName} onChange={(e) => setEditName(e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-description">Descripción</Label>
                            <Textarea id="edit-description" value={editDescription} onChange={(e) => setEditDescription(e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-color">Color</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="edit-color"
                                    type="color"
                                    value={editColor}
                                    onChange={(e) => setEditColor(e.target.value)}
                                    className="h-10 w-20 cursor-pointer"
                                />
                                <Input value={editColor} onChange={(e) => setEditColor(e.target.value)} placeholder="#FFFFFF" className="flex-1" />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsEditDialogOpen(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handleEditStage}>Guardar Cambios</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
