import { Head, Link, router, usePage } from '@inertiajs/react';
import { Activity, Building2, Calendar, Edit, FileText, Mail, MapPin, Phone, Trash2, Users } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { Address, SharedData, type BreadcrumbItem, type Contact } from '@/types';
import { CommentList } from '@/components/CommentList';

interface Props {
    contact: Contact;
}

export default function ContactShow({ contact }: Props) {
    const { auth } = usePage<SharedData>().props;
    const { can } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Contactos',
            href: '/contacts',
        },
        {
            title: contact.name,
            href: `/contacts/${contact.id}`,
        },
    ];

    const handleDelete = () => {
        if (confirm('¿Estás seguro de que deseas eliminar este contacto? Esta acción no se puede deshacer.')) {
            router.delete(`/contacts/${contact.id}`);
        }
    };

    const getContactTypeIcon = (type: string) => {
        return type === 'customer' ? <Users className="h-5 w-5" /> : <Building2 className="h-5 w-5" />;
    };

    const getContactTypeBadge = (type: string) => {
        return type === 'customer' ? (
            <Badge variant="default" className="flex items-center gap-2">
                <Users className="h-3 w-3" />
                Cliente
            </Badge>
        ) : (
            <Badge variant="secondary" className="flex items-center gap-2">
                <Building2 className="h-3 w-3" />
                Proveedor
            </Badge>
        );
    };

    const formatAddress = (address: Address | null | undefined) => {
        if (!address) return null;

        const parts = [];
        if (address.description) parts.push(address.description);
        if (address.municipality) parts.push(address.municipality);
        if (address.province) parts.push(address.province);
        if (address.country) parts.push(address.country);

        return parts.length > 0 ? parts.join(', ') : null;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${contact.name} - Contacto`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="">
                    {/* Header */}
                    <div className="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div className="mb-4 flex items-center space-x-4 sm:mb-0">
                            <div>
                                <div className="mb-2 flex items-center space-x-3">
                                    {getContactTypeIcon(contact.contact_type)}
                                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{contact.name}</h1>
                                    {getContactTypeBadge(contact.contact_type)}
                                </div>
                                <div className="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div className="flex items-center space-x-1">
                                        <Activity className="h-4 w-4" />
                                        <span>Estado:</span>
                                        <Badge variant={contact.status === 'active' ? 'default' : 'secondary'}>
                                            {contact.status === 'active' ? 'Activo' : 'Inactivo'}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center space-x-1">
                                        <Calendar className="h-4 w-4" />
                                        <span>Creado: {formatDate(contact.created_at)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex space-x-2">
                            {can('edit contacts') && (
                                <Button variant="outline" asChild>
                                    <Link href={`/contacts/${contact.id}/edit`}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                            )}
                            {can('delete contacts') && (
                                <Button variant="destructive" onClick={handleDelete}>
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Eliminar
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        {/* Main Content */}
                        <div className="space-y-6 lg:col-span-2">
                            {/* Contact Information */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Phone className="h-5 w-5" />
                                        <span>Información de Contacto</span>
                                    </CardTitle>
                                    <CardDescription>Formas de comunicación con el contacto</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        {contact.email && (
                                            <div className="flex items-center space-x-3">
                                                <Mail className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">Email</div>
                                                    <a
                                                        href={`mailto:${contact.email}`}
                                                        className="text-primary hover:text-primary dark:text-primary dark:hover:text-primary"
                                                    >
                                                        {contact.email}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {contact.phone_primary && (
                                            <div className="flex items-center space-x-3">
                                                <Phone className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">Teléfono Principal</div>
                                                    <a
                                                        href={`tel:${contact.phone_primary}`}
                                                        className="text-primary hover:text-primary dark:text-primary dark:hover:text-primary"
                                                    >
                                                        {contact.phone_primary}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {contact.phone_secondary && (
                                            <div className="flex items-center space-x-3">
                                                <Phone className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">Teléfono Secundario</div>
                                                    <a
                                                        href={`tel:${contact.phone_secondary}`}
                                                        className="text-primary hover:text-primary dark:text-primary dark:hover:text-primary"
                                                    >
                                                        {contact.phone_secondary}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {contact.mobile && (
                                            <div className="flex items-center space-x-3">
                                                <Phone className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">Móvil</div>
                                                    <a
                                                        href={`tel:${contact.mobile}`}
                                                        className="text-primary hover:text-primary dark:text-primary dark:hover:text-primary"
                                                    >
                                                        {contact.mobile}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {contact.fax && (
                                            <div className="flex items-center space-x-3">
                                                <Phone className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">Fax</div>
                                                    <div className="font-medium">{contact.fax}</div>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {!contact.email && !contact.phone_primary && !contact.phone_secondary && !contact.mobile && !contact.fax && (
                                        <div className="py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay información de contacto disponible
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                                 {/* Comments Section */}
                                                <CommentList
                                                    comments={contact.comments || []}
                                                    commentableType="Contact"
                                                    commentableId={contact.id}
                                                    currentUser={auth.user}
                                                    title="Comentarios del contacto"
                                                    className="mb-8"
                                                />

                            {/* Address Information */}
                            {contact.primary_address && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center space-x-2">
                                            <MapPin className="h-5 w-5" />
                                            <span>Dirección</span>
                                        </CardTitle>
                                        <CardDescription>Dirección principal del contacto</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {formatAddress(contact.primary_address) ? (
                                                <div className="text-gray-900 dark:text-white">{formatAddress(contact.primary_address)}</div>
                                            ) : (
                                                <div className="py-4 text-center text-gray-500 dark:text-gray-400">No hay dirección registrada</div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Notes */}
                            {contact.observations && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center space-x-2">
                                            <FileText className="h-5 w-5" />
                                            <span>Observaciones</span>
                                        </CardTitle>
                                        <CardDescription>Notas adicionales sobre el contacto</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="prose dark:prose-invert max-w-none">
                                            <p className="whitespace-pre-wrap">{contact.observations}</p>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Identification */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Identificación</CardTitle>
                                    <CardDescription>Documentos de identificación</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {contact.identification_object ? (
                                        <div className="space-y-3">
                                            <div>
                                                <div className="text-sm text-gray-600 dark:text-gray-400">Tipo</div>
                                                <div className="font-medium">{contact.identification_object.type}</div>
                                            </div>
                                            <div>
                                                <div className="text-sm text-gray-600 dark:text-gray-400">Número</div>
                                                <div className="font-medium">{contact.identification_object.number}</div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="py-4 text-center text-gray-500 dark:text-gray-400">Sin identificación registrada</div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Financial Information */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información Financiera</CardTitle>
                                    <CardDescription>Límites y configuración financiera</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        <div>
                                            <div className="text-sm text-gray-600 dark:text-gray-400">Límite de Crédito</div>
                                            <div className="text-lg font-medium">
                                                $
                                                {contact.credit_limit
                                                    ? contact.credit_limit.toLocaleString('es-ES', { minimumFractionDigits: 2 })
                                                    : '0.00'}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Activity Summary */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Resumen de Actividad</CardTitle>
                                    <CardDescription>Estadísticas del contacto</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">Documentos</span>
                                            <Badge variant="outline">{contact.documents_count || 0}</Badge>
                                        </div>

                                        {contact.contact_type === 'supplier' && (
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm text-gray-600 dark:text-gray-400">Productos Suministrados</span>
                                                <Badge variant="outline">{contact.supplied_stocks_count || 0}</Badge>
                                            </div>
                                        )}

                                        <Separator />

                                        <div className="space-y-2 text-xs text-gray-500 dark:text-gray-400">
                                            <div>Creado: {formatDate(contact.created_at)}</div>
                                            <div>Actualizado: {formatDate(contact.updated_at)}</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
