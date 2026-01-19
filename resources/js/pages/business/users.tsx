import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Building2, Mail, Plus, Shield, Trash2, UserPlus, Users } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Usuarios del negocio',
        href: '/business/users',
    },
];

interface Role {
    id: number;
    name: string;
}

interface WorkspaceMembership {
    id: number;
    name: string;
    is_owner: boolean;
    pivot_role: string;
    joined_at: string;
    roles: Role[];
}

interface User {
    id: number;
    name: string;
    email: string;
    business_role: string;
    created_at: string;
    workspaces_count: number;
    workspaces: WorkspaceMembership[];
}

interface Workspace {
    id: number;
    name: string;
}

interface RolesByWorkspace {
    [workspaceId: string]: Role[];
}

interface Props {
    users: User[];
    workspaces: Workspace[];
    rolesByWorkspace: RolesByWorkspace;
}

export default function BusinessUsers({ users, workspaces, rolesByWorkspace }: Props) {
    const { can } = usePermissions();
    const { auth, impersonating } = usePage<SharedData>().props;
    const [selectedUser, setSelectedUser] = useState<User | null>(null);
    const [showUserDetailsDialog, setShowUserDetailsDialog] = useState(false);
    const [showInviteDialog, setShowInviteDialog] = useState(false);
    const [showEditRolesDialog, setShowEditRolesDialog] = useState(false);
    const [showAddToWorkspaceDialog, setShowAddToWorkspaceDialog] = useState(false);
    const [selectedWorkspaceForRoles, setSelectedWorkspaceForRoles] = useState<WorkspaceMembership | null>(null);

    // Invite form state
    const [inviteName, setInviteName] = useState('');
    const [inviteEmail, setInviteEmail] = useState('');
    const [inviteWorkspaces, setInviteWorkspaces] = useState<{ workspace_id: number; role_id: number | null }[]>([]);

    // Edit roles state
    const [selectedRoleIds, setSelectedRoleIds] = useState<number[]>([]);

    // Add to workspace state
    const [addToWorkspaceId, setAddToWorkspaceId] = useState<number | null>(null);
    const [addToWorkspaceRoleId, setAddToWorkspaceRoleId] = useState<number | null>(null);

    const openUserDetails = (user: User) => {
        setSelectedUser(user);
        setShowUserDetailsDialog(true);
    };

    const impersonate = (user: User) => {
        router.post('/impersonate/' + user.id); //
    };

    const openInviteDialog = () => {
        setInviteName('');
        setInviteEmail('');
        setInviteWorkspaces([]);
        setShowInviteDialog(true);
    };

    const handleInvite = () => {
        router.post(
            '/business/users/invite',
            {
                name: inviteName,
                email: inviteEmail,
                workspaces: inviteWorkspaces,
            },
            {
                onSuccess: () => {
                    setShowInviteDialog(false);
                    setInviteName('');
                    setInviteEmail('');
                    setInviteWorkspaces([]);
                },
            },
        );
    };

    const openEditRolesDialog = (workspace: WorkspaceMembership) => {
        setSelectedWorkspaceForRoles(workspace);
        setSelectedRoleIds(workspace.roles.map((r) => r.id));
        setShowEditRolesDialog(true);
    };

    const handleUpdateRoles = () => {
        if (!selectedUser || !selectedWorkspaceForRoles) return;

        router.patch(
            `/business/users/${selectedUser.id}/workspaces/${selectedWorkspaceForRoles.id}/roles`,
            {
                role_ids: selectedRoleIds,
            },
            {
                preserveState: false,
                onSuccess: () => {
                    // Update selectedUser with new roles
                    const updatedWorkspaces = selectedUser.workspaces.map((ws) => {
                        if (ws.id === selectedWorkspaceForRoles.id) {
                            const newRoles = selectedRoleIds
                                .map((id) => rolesByWorkspace[selectedWorkspaceForRoles.id]?.find((r) => r.id === id))
                                .filter((r): r is Role => r !== undefined);
                            return { ...ws, roles: newRoles };
                        }
                        return ws;
                    });
                    setSelectedUser({ ...selectedUser, workspaces: updatedWorkspaces });
                    setShowEditRolesDialog(false);
                    setSelectedWorkspaceForRoles(null);
                    setSelectedRoleIds([]);
                },
            },
        );
    };

    const openAddToWorkspaceDialog = () => {
        setAddToWorkspaceId(null);
        setAddToWorkspaceRoleId(null);
        setShowAddToWorkspaceDialog(true);
    };

    const handleAddToWorkspace = () => {
        if (!selectedUser || !addToWorkspaceId) return;

        router.post(
            `/business/users/${selectedUser.id}/workspaces`,
            {
                workspace_id: addToWorkspaceId,
                role_id: addToWorkspaceRoleId,
            },
            {
                onSuccess: () => {
                    setShowAddToWorkspaceDialog(false);
                    setAddToWorkspaceId(null);
                    setAddToWorkspaceRoleId(null);
                },
            },
        );
    };

    const handleRemoveFromWorkspace = (workspaceId: number) => {
        if (!selectedUser) return;

        if (confirm('¿Estás seguro de que deseas remover este usuario del workspace?')) {
            router.delete(`/business/users/${selectedUser.id}/workspaces/${workspaceId}`, {
                preserveScroll: true,
            });
        }
    };

    const toggleInviteWorkspace = (workspaceId: number) => {
        const exists = inviteWorkspaces.find((w) => w.workspace_id === workspaceId);
        if (exists) {
            setInviteWorkspaces(inviteWorkspaces.filter((w) => w.workspace_id !== workspaceId));
        } else {
            setInviteWorkspaces([...inviteWorkspaces, { workspace_id: workspaceId, role_id: null }]);
        }
    };

    const updateInviteWorkspaceRole = (workspaceId: number, roleId: number | null) => {
        setInviteWorkspaces(inviteWorkspaces.map((w) => (w.workspace_id === workspaceId ? { ...w, role_id: roleId } : w)));
    };

    const toggleRoleSelection = (roleId: number) => {
        if (selectedRoleIds.includes(roleId)) {
            setSelectedRoleIds(selectedRoleIds.filter((id) => id !== roleId));
        } else {
            setSelectedRoleIds([...selectedRoleIds, roleId]);
        }
    };

    const availableWorkspacesForUser = selectedUser ? workspaces.filter((w) => !selectedUser.workspaces.some((uw) => uw.id === w.id)) : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios del negocio" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Usuarios del negocio</h1>
                        <p className="text-gray-600">Gestiona todos los usuarios y sus accesos a las sucursales</p>
                    </div>

                    <Button className="gap-2" onClick={openInviteDialog}>
                        <UserPlus className="h-4 w-4" />
                        Invitar usuario
                    </Button>
                </div>

                <Card className="mt-8">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Todos los usuarios
                        </CardTitle>
                        <CardDescription>
                            {users.length} {users.length === 1 ? 'usuario registrado' : 'usuarios registrados'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {users.length === 0 ? (
                            <div className="py-12 text-center">
                                <Users className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 className="mb-2 text-lg font-medium text-gray-900">No hay usuarios</h3>
                                <p className="mb-4 text-gray-500">Invita a tu primer usuario para empezar.</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Rol de negocio</TableHead>
                                        <TableHead className="text-center">Workspaces</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="font-medium">
                                                {user.name}
                                                {auth.user.id === user.id && <span className="font-italic font-bold"> (Yo)</span>}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2 text-sm text-gray-600">
                                                    <Mail className="h-3 w-3" />
                                                    {user.email}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{user.business_role}</Badge>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant="secondary">{user.workspaces_count}</Badge>
                                            </TableCell>
                                            <TableCell className="flex gap-2">
                                                <Button variant="outline" size="sm" onClick={() => openUserDetails(user)}>
                                                    Ver detalles
                                                </Button>
                                                {can('impersonate') && user.id !== auth.user.id && (
                                                    <Button variant="outline" size="sm" onClick={() => impersonate(user)}>
                                                        Suplantar
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Invite User Dialog */}
                <Dialog open={showInviteDialog} onOpenChange={setShowInviteDialog}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Invitar usuario al negocio</DialogTitle>
                            <DialogDescription>Invita un nuevo usuario y asígnalo a una o más sucursales</DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="name">Nombre</Label>
                                <Input id="name" value={inviteName} onChange={(e) => setInviteName(e.target.value)} placeholder="Nombre completo" />
                            </div>

                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={inviteEmail}
                                    onChange={(e) => setInviteEmail(e.target.value)}
                                    placeholder="usuario@ejemplo.com"
                                />
                            </div>

                            <div>
                                <Label>Sucursales y roles</Label>
                                <div className="mt-2 max-h-[300px] space-y-2 overflow-y-auto rounded-lg border p-3">
                                    {workspaces.map((workspace) => {
                                        const isSelected = inviteWorkspaces.some((w) => w.workspace_id === workspace.id);
                                        const selectedWorkspace = inviteWorkspaces.find((w) => w.workspace_id === workspace.id);

                                        return (
                                            <div key={workspace.id} className="flex items-center gap-3">
                                                <Checkbox
                                                    id={`workspace-${workspace.id}`}
                                                    checked={isSelected}
                                                    onCheckedChange={() => toggleInviteWorkspace(workspace.id)}
                                                />
                                                <label htmlFor={`workspace-${workspace.id}`} className="flex-1 cursor-pointer text-sm">
                                                    {workspace.name}
                                                </label>
                                                {isSelected && rolesByWorkspace[workspace.id] && (
                                                    <Select
                                                        value={selectedWorkspace?.role_id?.toString() || ''}
                                                        onValueChange={(value) =>
                                                            updateInviteWorkspaceRole(workspace.id, value ? parseInt(value) : null)
                                                        }
                                                    >
                                                        <SelectTrigger className="w-[180px]">
                                                            <SelectValue placeholder="Seleccionar rol" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {rolesByWorkspace[workspace.id].map((role) => (
                                                                <SelectItem key={role.id} value={role.id.toString()}>
                                                                    {role.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowInviteDialog(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={handleInvite} disabled={!inviteName || !inviteEmail || inviteWorkspaces.length === 0}>
                                Enviar invitación
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* User Details Dialog */}
                <Dialog open={showUserDetailsDialog} onOpenChange={setShowUserDetailsDialog}>
                    <DialogContent className="max-h-[90vh] max-w-4xl overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Detalles del usuario</DialogTitle>
                            <DialogDescription>Gestiona los workspaces y roles de {selectedUser?.name}</DialogDescription>
                        </DialogHeader>

                        {selectedUser && (
                            <div className="space-y-6">
                                {/* User Info */}
                                <div className="grid gap-4 rounded-lg border p-4">
                                    <div>
                                        <span className="text-sm font-medium text-gray-500">Nombre</span>
                                        <p className="text-base font-medium">{selectedUser.name}</p>
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-gray-500">Email</span>
                                        <p className="flex items-center gap-2 text-base">
                                            <Mail className="h-4 w-4 text-gray-400" />
                                            {selectedUser.email}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-gray-500">Rol de negocio</span>
                                        <p className="text-base">
                                            <Badge variant="outline">{selectedUser.business_role}</Badge>
                                        </p>
                                    </div>
                                </div>

                                {/* Workspace Memberships */}
                                <div>
                                    <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                        <Building2 className="h-5 w-5" />
                                        Sucursales ({selectedUser.workspaces.length})
                                    </h3>

                                    {selectedUser.workspaces.length === 0 ? (
                                        <div className="rounded-lg border border-dashed p-8 text-center">
                                            <Building2 className="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                            <p className="text-sm text-gray-500">Este usuario no pertenece a ninguna sucursal</p>
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            {selectedUser.workspaces.map((workspace) => (
                                                <div key={workspace.id} className="flex items-center justify-between rounded-lg border p-4">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <p className="font-medium">{workspace.name}</p>
                                                            {selectedUser.business_role === 'owner' && (
                                                                <Badge variant="default" className="text-xs">
                                                                    Owner
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="mt-2 flex flex-wrap gap-2">
                                                            {workspace.roles.length > 0 ? (
                                                                workspace.roles.map((role) => (
                                                                    <Badge key={role.id} variant="secondary" className="flex items-center gap-1">
                                                                        <Shield className="h-3 w-3" />
                                                                        {role.name}
                                                                    </Badge>
                                                                ))
                                                            ) : (
                                                                <span className="text-sm text-gray-500">Sin roles asignados</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <Button variant="outline" size="sm" onClick={() => openEditRolesDialog(workspace)}>
                                                            Editar roles
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="text-red-600 hover:text-red-700"
                                                            onClick={() => handleRemoveFromWorkspace(workspace.id)}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                {/* Add to Workspace */}
                                {availableWorkspacesForUser.length > 0 && (
                                    <div className="border-t pt-4">
                                        <Button variant="outline" className="w-full gap-2" onClick={openAddToWorkspaceDialog}>
                                            <Plus className="h-4 w-4" />
                                            Agregar a sucursal
                                        </Button>
                                    </div>
                                )}
                            </div>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Edit Roles Dialog */}
                <Dialog open={showEditRolesDialog} onOpenChange={setShowEditRolesDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Editar roles</DialogTitle>
                            <DialogDescription>
                                Selecciona los roles para {selectedUser?.name} en {selectedWorkspaceForRoles?.name}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-3">
                            {selectedWorkspaceForRoles &&
                                rolesByWorkspace[selectedWorkspaceForRoles.id]?.map((role) => (
                                    <div key={role.id} className="flex items-center gap-3">
                                        <Checkbox
                                            id={`role-${role.id}`}
                                            checked={selectedRoleIds.includes(role.id)}
                                            onCheckedChange={() => toggleRoleSelection(role.id)}
                                        />
                                        <label htmlFor={`role-${role.id}`} className="flex-1 cursor-pointer text-sm">
                                            {role.name}
                                        </label>
                                    </div>
                                ))}
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowEditRolesDialog(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={handleUpdateRoles}>Guardar cambios</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Add to Workspace Dialog */}
                <Dialog open={showAddToWorkspaceDialog} onOpenChange={setShowAddToWorkspaceDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Agregar a sucursal</DialogTitle>
                            <DialogDescription>Selecciona una sucursal y un rol opcional para {selectedUser?.name}</DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div>
                                <Label>Sucursal</Label>
                                <Select value={addToWorkspaceId?.toString() || ''} onValueChange={(value) => setAddToWorkspaceId(parseInt(value))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar sucursal" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableWorkspacesForUser.map((workspace) => (
                                            <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                {workspace.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {addToWorkspaceId && rolesByWorkspace[addToWorkspaceId] && (
                                <div>
                                    <Label>Rol (opcional)</Label>
                                    <Select
                                        value={addToWorkspaceRoleId?.toString() || ''}
                                        onValueChange={(value) => setAddToWorkspaceRoleId(value ? parseInt(value) : null)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar rol" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {rolesByWorkspace[addToWorkspaceId].map((role) => (
                                                <SelectItem key={role.id} value={role.id.toString()}>
                                                    {role.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowAddToWorkspaceDialog(false)}>
                                Cancelar
                            </Button>
                            <Button onClick={handleAddToWorkspace} disabled={!addToWorkspaceId}>
                                Agregar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
