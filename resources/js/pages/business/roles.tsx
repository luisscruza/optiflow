import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, RefreshCw, Search, Shield, Trash2, Users } from 'lucide-react';
import { useRef, useState } from 'react';

interface Permission {
    name: string;
    label: string;
    group: string;
}

interface Role {
    name: string;
    permissions: { name: string; label: string }[];
    users_count: number;
    workspace_ids: number[];
    workspaces_count: number;
    total_workspaces: number;
    is_synced: boolean;
}

interface Workspace {
    id: number;
    name: string;
}

interface Props {
    roles: Role[];
    permissions: Record<string, Permission[]>;
    workspaces: Workspace[];
}

export default function BusinessRoles({ roles, permissions, workspaces }: Props) {
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [editingRole, setEditingRole] = useState<Role | null>(null);
    const [roleName, setRoleName] = useState('');
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [searchQuery, setSearchQuery] = useState('');
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const resetForm = () => {
        setRoleName('');
        setSelectedPermissions([]);
        setErrors({});
        setSearchQuery('');
    };

    const openCreateDialog = () => {
        resetForm();
        setShowCreateDialog(true);
    };

    const openEditDialog = (role: Role) => {
        setEditingRole(role);
        setRoleName(role.name);
        setSelectedPermissions(role.permissions.map((p) => p.name));
        setShowEditDialog(true);
    };

    const handlePermissionToggle = (permissionName: string) => {
        const scrollPosition = scrollContainerRef.current?.scrollTop || 0;
        setSelectedPermissions((prev) => (prev.includes(permissionName) ? prev.filter((p) => p !== permissionName) : [...prev, permissionName]));
        requestAnimationFrame(() => {
            if (scrollContainerRef.current) {
                scrollContainerRef.current.scrollTop = scrollPosition;
            }
        });
    };

    const handleSelectAllInGroup = (groupPermissions: Permission[]) => {
        const groupPermissionNames = groupPermissions.map((p) => p.name);
        const allSelected = groupPermissionNames.every((p) => selectedPermissions.includes(p));

        if (allSelected) {
            setSelectedPermissions((prev) => prev.filter((p) => !groupPermissionNames.includes(p)));
        } else {
            setSelectedPermissions((prev) => [...new Set([...prev, ...groupPermissionNames])]);
        }
    };

    const handleSelectAll = () => {
        const allPermissionNames = Object.values(permissions)
            .flat()
            .map((p) => p.name);
        const allSelected = allPermissionNames.every((p) => selectedPermissions.includes(p));

        if (allSelected) {
            setSelectedPermissions([]);
        } else {
            setSelectedPermissions(allPermissionNames);
        }
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            '/business/roles',
            {
                name: roleName,
                permissions: selectedPermissions,
            },
            {
                onSuccess: () => {
                    setShowCreateDialog(false);
                    resetForm();
                },
                onError: (errors) => {
                    setErrors(errors);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingRole) return;

        setProcessing(true);

        router.patch(
            `/business/roles/${encodeURIComponent(editingRole.name)}`,
            {
                name: roleName,
                permissions: selectedPermissions,
            },
            {
                onSuccess: () => {
                    setShowEditDialog(false);
                    setEditingRole(null);
                    resetForm();
                },
                onError: (errors) => {
                    setErrors(errors);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const handleDelete = (role: Role) => {
        if (!confirm(`¿Estás seguro de eliminar el rol "${role.name}" de todos los workspaces?`)) return;

        router.delete(`/business/roles/${encodeURIComponent(role.name)}`);
    };

    const handleSync = (role: Role) => {
        if (!confirm(`¿Sincronizar el rol "${role.name}" a todos los workspaces que no lo tienen?`)) return;

        router.post(`/business/roles/${encodeURIComponent(role.name)}/sync`);
    };

    const PermissionForm = ({ onSubmit, submitLabel, formId }: { onSubmit: (e: React.FormEvent) => void; submitLabel: string; formId: string }) => (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="space-y-2">
                <Label htmlFor={`${formId}-name`}>Nombre del rol</Label>
                <Input
                    id={`${formId}-name`}
                    value={roleName}
                    onChange={(e) => setRoleName(e.target.value)}
                    placeholder="Ej: Vendedor, Optometrista, etc."
                />
                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
            </div>

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <Label>Permisos</Label>
                    <Button type="button" variant="outline" size="sm" onClick={handleSelectAll} className="h-8 text-xs">
                        {Object.values(permissions)
                            .flat()
                            .every((p) => selectedPermissions.includes(p.name))
                            ? 'Deseleccionar todos'
                            : 'Seleccionar todos'}
                    </Button>
                </div>
                {errors.permissions && <p className="text-sm text-red-600">{errors.permissions}</p>}

                <div className="relative">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        type="text"
                        placeholder="Buscar permisos..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9"
                    />
                </div>

                <div ref={scrollContainerRef} className="max-h-[400px] space-y-6 overflow-y-auto pr-2">
                    {Object.entries(permissions).map(([groupName, groupPermissions]) => {
                        const filteredPermissions = groupPermissions.filter((p) =>
                            searchQuery
                                ? p.label.toLowerCase().includes(searchQuery.toLowerCase()) ||
                                  p.name.toLowerCase().includes(searchQuery.toLowerCase())
                                : true,
                        );
                        if (filteredPermissions.length === 0) return null;
                        return (
                            <div key={groupName} className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100">{groupName}</h4>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleSelectAllInGroup(filteredPermissions)}
                                        className="h-7 text-xs"
                                    >
                                        {filteredPermissions.every((p) => selectedPermissions.includes(p.name))
                                            ? 'Deseleccionar todos'
                                            : 'Seleccionar todos'}
                                    </Button>
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    {filteredPermissions.map((permission) => (
                                        <div
                                            key={permission.name}
                                            className="flex items-center space-x-2 rounded-md p-2 hover:bg-gray-50 dark:hover:bg-gray-800"
                                        >
                                            <Checkbox
                                                id={`${formId}-${permission.name}`}
                                                checked={selectedPermissions.includes(permission.name)}
                                                onCheckedChange={() => handlePermissionToggle(permission.name)}
                                            />
                                            <label htmlFor={`${formId}-${permission.name}`} className="flex-1 cursor-pointer text-sm">
                                                {permission.label}
                                            </label>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            <div className="flex gap-2 border-t pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                        setShowCreateDialog(false);
                        setShowEditDialog(false);
                        resetForm();
                    }}
                    className="flex-1"
                >
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !roleName || selectedPermissions.length === 0} className="flex-1">
                    {processing ? 'Guardando...' : submitLabel}
                </Button>
            </div>
        </form>
    );

    return (
        <AppLayout>
            <Head title="Roles y permisos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Roles y permisos</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gestiona los roles y permisos.</p>
                    </div>

                    <Dialog
                        open={showCreateDialog}
                        onOpenChange={(open) => {
                            if (open) {
                                resetForm();
                            }
                            setShowCreateDialog(open);
                        }}
                    >
                        <DialogTrigger asChild>
                            <Button className="gap-2">
                                <Plus className="h-4 w-4" />
                                Crear rol
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] max-w-2xl overflow-hidden">
                            <DialogHeader>
                                <DialogTitle>Crear nuevo rol</DialogTitle>
                                <DialogDescription>
                                    Define un nombre y los permisos para el nuevo rol. Se creará en todos los workspaces automáticamente.
                                </DialogDescription>
                            </DialogHeader>
                            <PermissionForm onSubmit={handleCreate} submitLabel="Crear rol" formId="create" />
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Roles List */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Shield className="h-5 w-5" />
                            Roles disponibles
                        </CardTitle>
                        <CardDescription>
                            {roles.length} {roles.length === 1 ? 'rol definido' : 'roles definidos'} en el negocio
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {roles.length === 0 ? (
                            <div className="py-12 text-center">
                                <Shield className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 className="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">No hay roles definidos</h3>
                                <p className="mb-4 text-gray-500 dark:text-gray-400">
                                    Crea tu primer rol para empezar a asignar permisos a los usuarios.
                                </p>
                                <Button onClick={openCreateDialog} className="gap-2">
                                    <Plus className="h-4 w-4" />
                                    Crear primer rol
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {roles.map((role) => (
                                    <div
                                        key={role.name}
                                        className="flex items-center justify-between rounded-lg border p-4 hover:bg-gray-50 dark:hover:bg-gray-900"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                                <Shield className="h-5 w-5 text-primary" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium">{role.name}</p>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipContent>
                                                                {role.is_synced
                                                                    ? 'Este rol existe en todos los workspaces'
                                                                    : `Este rol solo existe en ${role.workspaces_count} de ${role.total_workspaces} workspaces`}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </div>
                                                <div className="mt-1 flex items-center gap-4">
                                                    <span className="flex items-center gap-1 text-sm text-gray-500">
                                                        <Users className="h-3 w-3" />
                                                        {role.users_count} {role.users_count === 1 ? 'usuario' : 'usuarios'} (total)
                                                    </span>
                                                    <span className="text-sm text-gray-500">
                                                        {role.permissions.length} {role.permissions.length === 1 ? 'permiso' : 'permisos'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <div className="flex max-w-md flex-wrap gap-1">
                                                {role.permissions.slice(0, 3).map((permission) => (
                                                    <Badge key={permission.name} variant="secondary" className="text-xs">
                                                        {permission.label}
                                                    </Badge>
                                                ))}
                                                {role.permissions.length > 3 && (
                                                    <Badge variant="outline" className="text-xs">
                                                        +{role.permissions.length - 3} más
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="ml-4 flex gap-2">
                                                {!role.is_synced && (
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="outline" size="sm" onClick={() => handleSync(role)}>
                                                                    <RefreshCw className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Sincronizar a todos los workspaces</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                )}
                                                <Button variant="outline" size="sm" onClick={() => openEditDialog(role)}>
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(role)}
                                                    className="text-red-600 hover:text-red-700"
                                                    disabled={role.users_count > 0}
                                                    title={role.users_count > 0 ? 'No se puede eliminar un rol asignado a usuarios' : ''}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Edit Dialog */}
                <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
                    <DialogContent className="max-h-[90vh] max-w-2xl overflow-hidden">
                        <DialogHeader>
                            <DialogTitle>Editar rol</DialogTitle>
                            <DialogDescription>
                                Modifica el nombre y los permisos del rol. Los cambios se aplicarán a todos los workspaces.
                            </DialogDescription>
                        </DialogHeader>
                        <PermissionForm onSubmit={handleUpdate} submitLabel="Guardar cambios" formId="edit" />
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
