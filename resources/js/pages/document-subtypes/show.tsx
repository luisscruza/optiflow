import { Head, Link, router } from '@inertiajs/react';
import { Building2, Calendar, Edit, FileText, Hash, Settings, Star, TrendingUp } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Workspace } from '@/types';

interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    start_number: number;
    end_number: number | null;
    next_number: number;
    valid_until_date: string | null;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    // Computed attributes
    electronica: string;
    preferida: string;
    is_near_expiration: boolean;
    is_running_low: boolean;
    document_count: number;
}

interface Props {
    subtype: DocumentSubtype;
    availableWorkspaces: Workspace[];
    workspacePreferences: Record<number, boolean>;
}

export default function ShowDocumentSubtype({ subtype, availableWorkspaces, workspacePreferences }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Numeraciones de comprobantes',
            href: '/document-subtypes',
        },
        {
            title: subtype.name,
            href: `/document-subtypes/${subtype.id}`,
        },
    ];

    const handleSetDefault = () => {
        router.patch(
            `/document-subtypes/${subtype.id}/set-default`,
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    router.reload({ only: ['subtype'] });
                },
            },
        );
    };

    const handleSetWorkspacePreferred = (workspace: Workspace) => {
        router.patch(
            `/document-subtypes/${subtype.id}/workspace/${workspace.slug}/set-preferred`,
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    router.reload({ only: ['workspacePreferences'] });
                },
            },
        );
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-DO', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const getStatusColor = () => {
        if (subtype.is_near_expiration || subtype.is_running_low) {
            return 'text-orange-600';
        }
        return 'text-green-600';
    };

    const getStatusText = () => {
        if (subtype.is_near_expiration && subtype.is_running_low) {
            return 'Próxima a vencer y números bajos';
        }
        if (subtype.is_near_expiration) {
            return 'Próxima a vencer';
        }
        if (subtype.is_running_low) {
            return 'Números disponibles bajos';
        }
        return 'Activa';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Numeración: ${subtype.name}`} />

            <div className="max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-semibold text-gray-900">{subtype.name}</h1>
                                {subtype.is_default && <Star className="h-5 w-5 fill-current text-yellow-500" />}
                            </div>
                            <p className="text-sm text-gray-600">Detalles de la numeración de comprobantes</p>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline">
                                <Settings className="mr-2 h-4 w-4" />
                                Acciones
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={`/document-subtypes/${subtype.id}/edit`}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar numeración
                                </Link>
                            </DropdownMenuItem>
                            {!subtype.is_default && (
                                <DropdownMenuItem onClick={handleSetDefault}>
                                    <Star className="mr-2 h-4 w-4" />
                                    Establecer como preferida
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                {/* Status Alert */}
                {(subtype.is_near_expiration || subtype.is_running_low) && (
                    <Card className="border-orange-200 bg-orange-50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-orange-600" />
                                <div>
                                    <h3 className="font-medium text-orange-900">Atención requerida</h3>
                                    <p className="text-sm text-orange-700">{getStatusText()}. Considera crear una nueva numeración.</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Main Information */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5" />
                                    Información general
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Nombre</label>
                                        <p className="text-lg font-medium">{subtype.name}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Prefijo</label>
                                        <div className="mt-1 flex items-center gap-2">
                                            <Hash className="h-4 w-4 text-gray-400" />
                                            <Badge variant="outline" className="font-mono text-lg">
                                                {subtype.prefix}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Estado</label>
                                        <p className={`text-lg font-medium ${getStatusColor()}`}>{getStatusText()}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Sequence Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Hash className="h-5 w-5" />
                                    Información de secuencia
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Número inicial</label>
                                        <p className="text-lg font-medium">{subtype.start_number}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Siguiente número</label>
                                        <p className="text-lg font-medium">{subtype.next_number}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Número final</label>
                                        <p className="text-lg font-medium">{subtype.end_number ? subtype.end_number : 'Infinito'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Validity */}
                        {subtype.valid_until_date && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Calendar className="h-5 w-5" />
                                        Vigencia
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Fecha de vencimiento</label>
                                        <p className="text-lg font-medium">{new Date(subtype.valid_until_date).toLocaleDateString()}</p>
                                        {subtype.is_near_expiration && <p className="mt-1 text-sm text-orange-600">Esta numeración vence pronto</p>}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Preferences Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Star className="h-5 w-5" />
                                    Preferencias
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Global Default */}
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium">Predeterminada global</p>
                                        <p className="text-xs text-gray-500">Se usará si no hay preferencia por sucursal</p>
                                    </div>
                                    {subtype.is_default ? (
                                        <Badge variant="default" className="bg-yellow-100 text-yellow-800">
                                            <Star className="mr-1 h-3 w-3 fill-current" />
                                            Activa
                                        </Badge>
                                    ) : (
                                        <Button variant="outline" size="sm" onClick={handleSetDefault}>
                                            Establecer
                                        </Button>
                                    )}
                                </div>

                                {/* Workspace Preferences */}
                                {availableWorkspaces.length > 0 && (
                                    <div className="border-t pt-4">
                                        <p className="mb-3 text-sm font-medium">Preferida por sucursal</p>
                                        <div className="space-y-2">
                                            {availableWorkspaces.map((workspace) => (
                                                <div key={workspace.id} className="flex items-center justify-between rounded-lg border p-2">
                                                    <div className="flex items-center gap-2">
                                                        <Building2 className="h-4 w-4 text-gray-400" />
                                                        <span className="text-sm">{workspace.name}</span>
                                                    </div>
                                                    {workspacePreferences[workspace.id] ? (
                                                        <Badge variant="default" className="bg-blue-100 text-blue-800">
                                                            <Star className="mr-1 h-3 w-3 fill-current" />
                                                            Preferida
                                                        </Badge>
                                                    ) : (
                                                        <Button variant="ghost" size="sm" onClick={() => handleSetWorkspacePreferred(workspace)}>
                                                            Establecer
                                                        </Button>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Información del sistema</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Creada</label>
                                    <p className="text-sm">{formatDate(subtype.created_at)}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Última actualización</label>
                                    <p className="text-sm">{formatDate(subtype.updated_at)}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
