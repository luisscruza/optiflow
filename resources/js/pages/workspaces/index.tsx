import { Building2, Crown, Edit, MoreHorizontal, Plus, Users } from 'lucide-react';
import { useState } from 'react';

import { update as switchWorkspace } from '@/actions/App/Http/Controllers/WorkspaceContextController';
import { index, store, update } from '@/actions/App/Http/Controllers/WorkspaceController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Sucursales',
        href: index().url,
    },
];

interface Workspace {
    id: number;
    slug: string;
    name: string;
    code?: string;
    description: string | null;
    address?: string | null;
    phone?: string | null;
    is_owner: boolean;
    members_count: number;
    created_at: string;
}

interface WorkspaceFormData {
    [key: string]: string;
    name: string;
    code: string;
    description: string;
    address: string;
    phone: string;
}

interface Props {
    workspaces: Workspace[];
    current_workspace: Workspace | null;
}

export default function WorkspacesIndex({ workspaces, current_workspace }: Props) {
    const [deletingWorkspace, setDeletingWorkspace] = useState<string | null>(null);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [editingWorkspace, setEditingWorkspace] = useState<Workspace | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [formData, setFormData] = useState<WorkspaceFormData>({
        name: '',
        code: '',
        description: '',
        address: '',
        phone: '',
    });
    const [errors, setErrors] = useState<Partial<WorkspaceFormData>>({});

    const handleSwitchWorkspace = (slug: string) => {
        router.patch(switchWorkspace(slug.toString()).url);
    };

    const resetForm = () => {
        setFormData({
            name: '',
            code: '',
            description: '',
            address: '',
            phone: '',
        });
        setErrors({});
    };

    const handleCreateWorkspace = () => {
        resetForm();
        setIsCreateModalOpen(true);
    };

    const handleEditWorkspace = (workspace: Workspace) => {
        setFormData({
            name: workspace.name,
            code: workspace.code || '',
            description: workspace.description || '',
            address: workspace.address || '',
            phone: workspace.phone || '',
        });
        setEditingWorkspace(workspace);
        setErrors({});
        setIsEditModalOpen(true);
    };

    const handleSubmit = (isEdit: boolean = false) => {
        setIsSubmitting(true);
        setErrors({});

        const url = isEdit && editingWorkspace ? update(editingWorkspace.slug).url : store().url;

        const method = isEdit ? 'patch' : 'post';

        router[method](url, formData, {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                setIsEditModalOpen(false);
                setEditingWorkspace(null);
                resetForm();
            },
            onError: (errors: any) => {
                setErrors(errors);
            },
            onFinish: () => {
                setIsSubmitting(false);
            },
        });
    };

    const handleInputChange = (field: keyof WorkspaceFormData, value: string) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value,
        }));
        // Clear error for this field when user starts typing
        if (errors[field]) {
            setErrors((prev) => ({
                ...prev,
                [field]: undefined,
            }));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sucursales" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Sucursales</h1>
                        <p className="mt-2 text-gray-600 dark:text-gray-400">Gestiona tus espacios de trabajo y cambia entre diferentes contextos.</p>
                    </div>
                    <Button onClick={handleCreateWorkspace}>
                        <Plus className="mr-2 h-4 w-4" />
                        Crear nueva sucursal
                    </Button>
                </div>

                {current_workspace && (
                    <div className="mb-8">
                        <Card className="border-blue-200 bg-background dark:border-blue-800 dark:bg-blue-950">
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Crown className="h-5 w-5 text-primary dark:text-primary" />
                                    <CardTitle className="text-primary dark:text-primary">Espacio de trabajo actual</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="font-semibold text-primary dark:text-primary">{current_workspace.name}</h3>
                                        {current_workspace.description && (
                                            <p className="mt-1 text-sm text-primary dark:text-primary">{current_workspace.description}</p>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {current_workspace.is_owner && (
                                            <Badge variant="secondary" className="bg-blue-100 text-primary dark:bg-blue-900 dark:text-primary">
                                                Propietario
                                            </Badge>
                                        )}
                                        <div className="flex items-center text-sm text-primary dark:text-primary">
                                            <Users className="mr-1 h-4 w-4" />
                                            {current_workspace.members_count} miembro{current_workspace.members_count !== 1 ? 's' : ''}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {workspaces.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Building2 className="mb-4 h-12 w-12 text-gray-400" />
                            <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">No hay sucursales aún</h3>
                            <p className="mb-6 max-w-md text-center text-gray-600 dark:text-gray-400">Comienza creando tu primera sucursal.</p>
                            <Button onClick={handleCreateWorkspace}>
                                <Plus className="mr-2 h-4 w-4" />
                                Crear tu primer espacio de trabajo
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Miembros</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead className="w-[100px]">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {workspaces.map((workspace) => (
                                    <TableRow
                                        key={workspace.id}
                                        className={current_workspace?.id === workspace.id ? 'bg-background dark:bg-blue-950' : ''}
                                    >
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Building2 className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                                <span className="font-medium">{workspace.name}</span>
                                                {current_workspace?.id === workspace.id && (
                                                    <div className="flex items-center gap-1">
                                                        <Crown className="h-4 w-4 text-primary dark:text-primary" />
                                                        <Badge variant="outline" className="text-xs">
                                                            Actual
                                                        </Badge>
                                                    </div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">{workspace.code || '-'}</span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">{workspace.description || '-'}</span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1 text-sm">
                                                <Users className="h-4 w-4" />
                                                {workspace.members_count}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {new Date(workspace.created_at).toLocaleDateString()}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleEditWorkspace(workspace)}
                                                    className="h-8 w-8 p-0"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {current_workspace?.id !== workspace.id && (
                                                            <DropdownMenuItem onClick={() => handleSwitchWorkspace(workspace.slug)}>
                                                                Cambiar a este espacio de trabajo
                                                            </DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>

            {/* Create Workspace Modal */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nueva sucursal</DialogTitle>
                        <DialogDescription>Crea una nueva sucursal para distribuir tus ingresos y gastos.</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="create-name">Nombre *</Label>
                            <Input
                                id="create-name"
                                value={formData.name}
                                onChange={(e) => handleInputChange('name', e.target.value)}
                                placeholder="Ej: Sucursal Centro"
                            />
                            {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="create-code">Código</Label>
                            <Input
                                id="create-code"
                                value={formData.code}
                                onChange={(e) => handleInputChange('code', e.target.value)}
                                placeholder="Ej: SUC001"
                            />
                            {errors.code && <p className="text-sm text-red-600 dark:text-red-400">{errors.code}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="create-description">Descripción</Label>
                            <Textarea
                                id="create-description"
                                value={formData.description}
                                onChange={(e) => handleInputChange('description', e.target.value)}
                                placeholder="Descripción opcional de la sucursal"
                                rows={3}
                            />
                            {errors.description && <p className="text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="create-address">Dirección</Label>
                            <Input
                                id="create-address"
                                value={formData.address}
                                onChange={(e) => handleInputChange('address', e.target.value)}
                                placeholder="Ej: Calle Principal #123, Col. Centro"
                            />
                            {errors.address && <p className="text-sm text-red-600 dark:text-red-400">{errors.address}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="create-phone">Teléfono</Label>
                            <Input
                                id="create-phone"
                                value={formData.phone}
                                onChange={(e) => handleInputChange('phone', e.target.value)}
                                placeholder="Ej: +52 55 1234 5678"
                            />
                            {errors.phone && <p className="text-sm text-red-600 dark:text-red-400">{errors.phone}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsCreateModalOpen(false)} disabled={isSubmitting}>
                            Cancelar
                        </Button>
                        <Button onClick={() => handleSubmit(false)} disabled={isSubmitting}>
                            {isSubmitting ? 'Creando...' : 'Crear sucursal'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Edit Workspace Modal */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Editar sucursal</DialogTitle>
                        <DialogDescription>Modifica la información de la sucursal.</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Nombre *</Label>
                            <Input
                                id="edit-name"
                                value={formData.name}
                                onChange={(e) => handleInputChange('name', e.target.value)}
                                placeholder="Ej: Sucursal Centro"
                            />
                            {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-code">Código</Label>
                            <Input
                                id="edit-code"
                                value={formData.code}
                                onChange={(e) => handleInputChange('code', e.target.value)}
                                placeholder="Ej: SUC001"
                            />
                            {errors.code && <p className="text-sm text-red-600 dark:text-red-400">{errors.code}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-description">Descripción</Label>
                            <Textarea
                                id="edit-description"
                                value={formData.description}
                                onChange={(e) => handleInputChange('description', e.target.value)}
                                placeholder="Descripción opcional de la sucursal"
                                rows={3}
                            />
                            {errors.description && <p className="text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-address">Dirección</Label>
                            <Input
                                id="edit-address"
                                value={formData.address}
                                onChange={(e) => handleInputChange('address', e.target.value)}
                                placeholder="Ej: Calle Principal #123, Col. Centro"
                            />
                            {errors.address && <p className="text-sm text-red-600 dark:text-red-400">{errors.address}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-phone">Teléfono</Label>
                            <Input
                                id="edit-phone"
                                value={formData.phone}
                                onChange={(e) => handleInputChange('phone', e.target.value)}
                                placeholder="Ej: +52 55 1234 5678"
                            />
                            {errors.phone && <p className="text-sm text-red-600 dark:text-red-400">{errors.phone}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsEditModalOpen(false)} disabled={isSubmitting}>
                            Cancelar
                        </Button>
                        <Button onClick={() => handleSubmit(true)} disabled={isSubmitting}>
                            {isSubmitting ? 'Guardando...' : 'Guardar cambios'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
