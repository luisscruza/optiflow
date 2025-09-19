import { Head, Link } from '@inertiajs/react';
import { Building2, Calendar, Crown, Settings, Users } from 'lucide-react';

import { edit, index } from '@/actions/App/Http/Controllers/WorkspaceController';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, User, Workspace } from '@/types';

interface Props {
    workspace: Workspace & {
        owner: User;
        users: User[];
    };
}

export default function ShowWorkspace({ workspace }: Props) {
    const isOwner = workspace.owner_id === workspace.owner?.id;
    const users = workspace.users || [];

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Sucursales',
            href: index().url,
        },
        {
            title: workspace.name,
            href: '#',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={workspace.name} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{workspace.name}</h1>
                                {isOwner && (
                                    <Badge variant="secondary">
                                        <Crown className="mr-1 h-3 w-3" />
                                        Propietario
                                    </Badge>
                                )}
                            </div>
                            {workspace.description && <p className="text-gray-600 dark:text-gray-400">{workspace.description}</p>}
                        </div>

                        {isOwner && (
                            <Button asChild>
                                <Link href={edit(workspace.slug).url}>
                                    <Settings className="mr-2 h-4 w-4" />
                                    Configuración
                                </Link>
                            </Button>
                        )}
                    </div>

                    <HeadingSmall title="Resumen" description="Información clave sobre este espacio de trabajo" />

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div className="flex items-center gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                                <Users className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Miembros</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">{users.length}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                                <Calendar className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Creado</p>
                                <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                    {new Date(workspace.created_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                                <Building2 className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Última Actualización</p>
                                <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                    {new Date(workspace.updated_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                    </div>

                    <HeadingSmall title="Miembros" description="Personas que tienen acceso a este espacio de trabajo" />

                    {users.length === 0 ? (
                        <div className="rounded-lg border border-gray-200 py-8 text-center dark:border-gray-700">
                            <Users className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                            <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Sin miembros aún</h3>
                            <p className="text-gray-600 dark:text-gray-400">Invita a personas para colaborar en este espacio de trabajo.</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {users.map((member: User) => (
                                <div
                                    key={member.id}
                                    className="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                                            <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                {member.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="font-semibold text-gray-900 dark:text-white">{member.name}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">{member.email}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {member.id === workspace.owner_id && (
                                            <Badge variant="secondary">
                                                <Crown className="mr-1 h-3 w-3" />
                                                Propietario
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {isOwner && (
                        <>
                            <HeadingSmall title="Acciones Rápidas" description="Acciones comunes que puedes realizar en este espacio de trabajo" />

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <Button variant="outline" asChild className="justify-start">
                                    <Link href={edit(workspace.slug).url}>
                                        <Settings className="mr-2 h-4 w-4" />
                                        Configuración
                                    </Link>
                                </Button>

                                <Button variant="outline" asChild className="justify-start">
                                    <Link href={index().url}>
                                        <Building2 className="mr-2 h-4 w-4" />
                                        Ver todas las sucursales
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
