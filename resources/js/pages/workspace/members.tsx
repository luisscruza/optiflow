import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Form } from '@inertiajs/react';
import { UserPlus, Settings, Trash2, Clock } from 'lucide-react';
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { InviteUserForm } from '@/components/workspace/invite-user-form';
import { MemberRoleSelect } from '@/components/workspace/member-role-select';

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    joined_at: string;
}

interface PendingInvitation {
    id: number;
    email: string;
    role: string;
    role_label: string;
    invited_by: string;
    created_at: string;
    expires_at: string;
}

interface Workspace {
    id: number;
    name: string;
    is_owner: boolean;
}

interface Props {
    workspace: Workspace;
    members: Member[];
    pending_invitations: PendingInvitation[];
    roles: Record<string, string>;
    available_workspaces: Array<{id: number, name: string}>;
}

export default function WorkspaceMembers({ workspace, members, pending_invitations, roles, available_workspaces }: Props) {
    const [showInviteDialog, setShowInviteDialog] = useState(false);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getRoleBadgeVariant = (role: string) => {
        switch (role) {
            case 'admin':
                return 'destructive';
            case 'sales':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    return (
        <AppLayout>
            <Head title={`Miembros - ${workspace.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Miembros de la sucursal</h1>
                        <p className="text-gray-600">
                            Gestiona los miembros y sus permisos en {workspace.name}
                        </p>
                    </div>

                    {workspace.is_owner && (
                        <Dialog open={showInviteDialog} onOpenChange={setShowInviteDialog}>
                            <DialogTrigger asChild>
                                <Button className="gap-2">
                                    <UserPlus className="h-4 w-4" />
                                    Agregar usuario
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                                <DialogHeader>
                                    <DialogTitle>Gestionar Usuario</DialogTitle>
                                    <DialogDescription>
                                        Buscar un usuario existente o crear uno nuevo y asignarlo a uno o múltiples workspaces.
                                    </DialogDescription>
                                </DialogHeader>
                                <InviteUserForm 
                                    roles={roles} 
                                    availableWorkspaces={available_workspaces}
                                    onSuccess={() => setShowInviteDialog(false)} 
                                />
                            </DialogContent>
                        </Dialog>
                    )}
                </div>

                {/* Current Members */}
                <Card>
                    <CardHeader>
                        <CardTitle>Miembros actuales</CardTitle>
                        <CardDescription>
                            {members.length} {members.length === 1 ? 'miembro' : 'miembros'} en este workspace
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {members.map((member) => (
                                <div key={member.id} className="flex items-center justify-between p-4 border rounded-lg">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span className="text-blue-600 font-medium">
                                                {member.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="font-medium">{member.name}</p>
                                            <p className="text-sm text-gray-600">{member.email}</p>
                                            <p className="text-xs text-gray-500">
                                                Se unió el {formatDate(member.joined_at)}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Badge variant={getRoleBadgeVariant(member.role)}>
                                            {member.role_label}
                                        </Badge>
                                        {workspace.is_owner && (
                                            <div className="flex gap-2">
                                                <MemberRoleSelect 
                                                    member={member} 
                                                    roles={roles} 
                                                />
                                                <Form
                                                    action={`/workspace/members/${member.id}`}
                                                    method="delete"
                                                    onSuccess={() => {}}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="outline"
                                                            size="sm"
                                                            disabled={processing}
                                                            className="text-red-600 hover:text-red-700"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Pending Invitations */}
                {pending_invitations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5" />
                                Invitaciones Pendientes
                            </CardTitle>
                            <CardDescription>
                                {pending_invitations.length} {pending_invitations.length === 1 ? 'invitación pendiente' : 'invitaciones pendientes'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {pending_invitations.map((invitation) => (
                                    <div key={invitation.id} className="flex items-center justify-between p-4 border rounded-lg bg-yellow-50">
                                        <div className="flex items-center gap-4">
                                            <div className="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                                <Clock className="h-5 w-5 text-yellow-600" />
                                            </div>
                                            <div>
                                                <p className="font-medium">{invitation.email}</p>
                                                <p className="text-sm text-gray-600">
                                                    Invitado por {invitation.invited_by}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    Enviado el {formatDate(invitation.created_at)} • 
                                                    Expira el {formatDate(invitation.expires_at)}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge variant="outline" className="text-yellow-600">
                                            {invitation.role_label}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}