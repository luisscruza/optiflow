import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, CheckCircle2, Circle, Flag, GripVertical, MoreHorizontal, Pencil, Play, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { type Workflow, type WorkflowJob, type WorkflowStage } from '@/types';

import { KanbanCard } from './kanban-card';

interface KanbanColumnProps {
    workflow: Workflow;
    stage: WorkflowStage;
    totalStages: number;
    onDragOver: (e: React.DragEvent) => void;
    onDrop: (e: React.DragEvent, stageId: string) => void;
    onDragStart: (e: React.DragEvent, job: WorkflowJob) => void;
    onDragEnd: (e: React.DragEvent) => void;
    onCreateJob: (stageId: string) => void;
}

export function KanbanColumn({ workflow, stage, totalStages, onDragOver, onDrop, onDragStart, onDragEnd, onCreateJob }: KanbanColumnProps) {
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editName, setEditName] = useState(stage.name);
    const [editDescription, setEditDescription] = useState(stage.description || '');
    const [editColor, setEditColor] = useState(stage.color);
    const [editPosition, setEditPosition] = useState(stage.position);
    const [editIsActive, setEditIsActive] = useState(stage.is_active);
    const [editIsInitial, setEditIsInitial] = useState(stage.is_initial);
    const [editIsFinal, setEditIsFinal] = useState(stage.is_final);

    const resetForm = () => {
        setEditName(stage.name);
        setEditDescription(stage.description || '');
        setEditColor(stage.color);
        setEditPosition(stage.position);
        setEditIsActive(stage.is_active);
        setEditIsInitial(stage.is_initial);
        setEditIsFinal(stage.is_final);
    };

    const handleOpenDialog = () => {
        resetForm();
        setIsEditDialogOpen(true);
    };

    const handleEditStage = () => {
        router.patch(
            `/workflows/${workflow.id}/stages/${stage.id}`,
            {
                name: editName,
                description: editDescription,
                color: editColor,
                position: editPosition,
                is_active: editIsActive,
                is_initial: editIsInitial,
                is_final: editIsFinal,
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

    // Preset colors for quick selection
    const presetColors = [
        '#EF4444', // Red
        '#F97316', // Orange
        '#EAB308', // Yellow
        '#22C55E', // Green
        '#14B8A6', // Teal
        '#3B82F6', // Blue
        '#8B5CF6', // Purple
        '#EC4899', // Pink
        '#6B7280', // Gray
    ];

    return (
        <>
            <Card
                className={`flex h-full w-80 flex-shrink-0 flex-col ${!stage.is_active ? 'opacity-60' : ''}`}
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
                        {stage.is_initial && (
                            <Badge variant="outline" className="ml-1 border-green-500 text-green-600">
                                <Play className="mr-1 h-3 w-3" />
                                Inicio
                            </Badge>
                        )}
                        {stage.is_final && (
                            <Badge variant="outline" className="ml-1 border-blue-500 text-blue-600">
                                <Flag className="mr-1 h-3 w-3" />
                                Final
                            </Badge>
                        )}
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
                                Agregar tarea
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={handleOpenDialog}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Editar etapa
                            </DropdownMenuItem>
                            <DropdownMenuItem className="text-destructive" onClick={handleDeleteStage}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar etapa
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </CardHeader>

                <CardContent className="flex-1 space-y-2 overflow-y-auto">
                    {stage.jobs?.map((job) => (
                        <KanbanCard key={job.id} job={job} workflow={workflow} onDragStart={onDragStart} onDragEnd={onDragEnd} />
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
                        Agregar tarea
                    </Button>
                </div>
            </Card>

            <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                <DialogContent className="max-h-[85vh] max-w-lg overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <div className="h-3 w-3 rounded-full" style={{ backgroundColor: editColor }} />
                            Editar etapa
                        </DialogTitle>
                        <DialogDescription>Configura los detalles y comportamiento de la etapa.</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-6">
                        {/* Basic Info Section */}
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="edit-name">Nombre de la etapa</Label>
                                <Input id="edit-name" value={editName} onChange={(e) => setEditName(e.target.value)} placeholder="Ej: En proceso" />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-description">Descripción (opcional)</Label>
                                <Textarea
                                    id="edit-description"
                                    value={editDescription}
                                    onChange={(e) => setEditDescription(e.target.value)}
                                    placeholder="Describe el propósito de esta etapa..."
                                    rows={2}
                                />
                            </div>
                        </div>

                        <Separator />

                        {/* Color Section */}
                        <div className="space-y-3">
                            <Label>Color de la etapa</Label>
                            <div className="flex flex-wrap gap-2">
                                {presetColors.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        onClick={() => setEditColor(color)}
                                        className={`h-8 w-8 rounded-full border-2 transition-transform hover:scale-110 ${
                                            editColor === color ? 'border-foreground ring-2 ring-foreground ring-offset-2' : 'border-transparent'
                                        }`}
                                        style={{ backgroundColor: color }}
                                    />
                                ))}
                                <div className="flex items-center gap-2">
                                    <Input
                                        type="color"
                                        value={editColor}
                                        onChange={(e) => setEditColor(e.target.value)}
                                        className="h-8 w-8 cursor-pointer rounded-full border-0 p-0"
                                    />
                                    <Input
                                        value={editColor}
                                        onChange={(e) => setEditColor(e.target.value)}
                                        placeholder="#000000"
                                        className="w-24 font-mono text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <Separator />

                        {/* Position Section */}
                        <div className="space-y-3">
                            <Label>Posición en el tablero</Label>
                            <div className="flex items-center gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditPosition(Math.max(1, editPosition - 1))}
                                    disabled={editPosition <= 1}
                                >
                                    <ArrowUp className="mr-1 h-4 w-4" />
                                    Mover izquierda
                                </Button>
                                <div className="flex items-center gap-2 rounded-md border bg-muted px-3 py-2">
                                    <span className="text-sm text-muted-foreground">Posición:</span>
                                    <Input
                                        type="number"
                                        value={editPosition}
                                        onChange={(e) => setEditPosition(Math.max(1, Math.min(totalStages, parseInt(e.target.value) || 1)))}
                                        min={1}
                                        max={totalStages}
                                        className="h-7 w-16 text-center"
                                    />
                                    <span className="text-sm text-muted-foreground">de {totalStages}</span>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditPosition(Math.min(totalStages, editPosition + 1))}
                                    disabled={editPosition >= totalStages}
                                >
                                    Mover derecha
                                    <ArrowDown className="ml-1 h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <Separator />

                        {/* Stage Type Section */}
                        <div className="space-y-3">
                            <Label>Tipo de etapa</Label>
                            <div className="grid grid-cols-1 gap-3">
                                {/* Active Toggle */}
                                <div
                                    onClick={() => setEditIsActive(!editIsActive)}
                                    className={`flex cursor-pointer items-center justify-between rounded-lg border p-4 transition-colors ${
                                        editIsActive ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : 'border-muted bg-muted/50'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        {editIsActive ? (
                                            <CheckCircle2 className="h-5 w-5 text-green-600" />
                                        ) : (
                                            <Circle className="h-5 w-5 text-muted-foreground" />
                                        )}
                                        <div>
                                            <p className="font-medium">Etapa activa</p>
                                            <p className="text-sm text-muted-foreground">
                                                {editIsActive ? 'La etapa está visible y disponible' : 'La etapa está oculta'}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        className={`relative h-6 w-11 rounded-full transition-colors ${editIsActive ? 'bg-green-500' : 'bg-muted-foreground/30'}`}
                                    >
                                        <div
                                            className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform ${editIsActive ? 'translate-x-5' : 'translate-x-0.5'}`}
                                        />
                                    </div>
                                </div>

                                {/* Initial Stage Toggle */}
                                <div
                                    onClick={() => {
                                        setEditIsInitial(!editIsInitial);
                                        if (!editIsInitial) setEditIsFinal(false);
                                    }}
                                    className={`flex cursor-pointer items-center justify-between rounded-lg border p-4 transition-colors ${
                                        editIsInitial ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : 'border-muted'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <Play className={`h-5 w-5 ${editIsInitial ? 'text-green-600' : 'text-muted-foreground'}`} />
                                        <div>
                                            <p className="font-medium">Etapa inicial</p>
                                            <p className="text-sm text-muted-foreground">Las tareas nuevas comienzan aquí</p>
                                        </div>
                                    </div>
                                    <div
                                        className={`relative h-6 w-11 rounded-full transition-colors ${editIsInitial ? 'bg-green-500' : 'bg-muted-foreground/30'}`}
                                    >
                                        <div
                                            className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform ${editIsInitial ? 'translate-x-5' : 'translate-x-0.5'}`}
                                        />
                                    </div>
                                </div>

                                {/* Final Stage Toggle */}
                                <div
                                    onClick={() => {
                                        setEditIsFinal(!editIsFinal);
                                        if (!editIsFinal) setEditIsInitial(false);
                                    }}
                                    className={`flex cursor-pointer items-center justify-between rounded-lg border p-4 transition-colors ${
                                        editIsFinal ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20' : 'border-muted'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <Flag className={`h-5 w-5 ${editIsFinal ? 'text-blue-600' : 'text-muted-foreground'}`} />
                                        <div>
                                            <p className="font-medium">Etapa final</p>
                                            <p className="text-sm text-muted-foreground">Las tareas se marcan como completadas</p>
                                        </div>
                                    </div>
                                    <div
                                        className={`relative h-6 w-11 rounded-full transition-colors ${editIsFinal ? 'bg-blue-500' : 'bg-muted-foreground/30'}`}
                                    >
                                        <div
                                            className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform ${editIsFinal ? 'translate-x-5' : 'translate-x-0.5'}`}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="mt-6">
                        <Button variant="outline" onClick={() => setIsEditDialogOpen(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handleEditStage}>Guardar cambios</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
