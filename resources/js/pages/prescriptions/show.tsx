import { Head, Link } from '@inertiajs/react';
import { Calendar, Edit, FileText, Printer, User } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import prescriptions from '@/routes/prescriptions';
import { Prescription, type BreadcrumbItem } from '@/types';

interface Props {
    prescription: Prescription;
}

export default function PrescriptionShow({ prescription }: Props) {
    const { can } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Recetas',
            href: prescriptions.index().url,
        },
        {
            title: `Receta #${prescription.id}`,
            href: prescriptions.show(prescription).url,
        },
    ];

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Receta #${prescription.id}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-3">
                                <FileText className="h-8 w-8 text-primary" />
                                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Receta #{prescription.id}</h1>
                            </div>
                            <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <div className="flex items-center gap-1">
                                    <Calendar className="h-4 w-4" />
                                    <span>Creada: {formatDate(prescription.created_at)}</span>
                                </div>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            {can('edit prescriptions') && (
                                <Link href={prescriptions.edit(prescription).url}>
                                    <Button variant="outline">
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Button>
                                </Link>
                            )}
                            <a target="blank" href={prescriptions.pdf(prescription).url}>
                                <Button>
                                    <Printer className="mr-2 h-4 w-4" />
                                    Descargar PDF
                                </Button>
                            </a>
                        </div>
                    </div>

                    <div className="mb-6 grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Paciente
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {prescription.patient ? (
                                    <div className="space-y-2">
                                        <p className="text-lg font-semibold">{prescription.patient.name}</p>
                                        {prescription.patient.identification_number && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Identificación: {prescription.patient.identification_number}
                                            </p>
                                        )}
                                        {prescription.patient.email && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Email: {prescription.patient.email}</p>
                                        )}
                                        {prescription.patient.phone_primary && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Teléfono: {prescription.patient.phone_primary}</p>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">No hay información del paciente</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Optometrista
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {prescription.optometrist ? (
                                    <div className="space-y-2">
                                        <p className="text-lg font-semibold">{prescription.optometrist.name}</p>
                                        {prescription.optometrist.email && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Email: {prescription.optometrist.email}</p>
                                        )}
                                        {prescription.optometrist.phone_primary && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Teléfono: {prescription.optometrist.phone_primary}
                                            </p>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">No hay información del optometrista</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de la Receta</CardTitle>
                            <CardDescription>Información completa de la prescripción</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {((prescription.motivos && prescription.motivos.length > 0) ||
                                    (prescription.estadoActual && prescription.estadoActual.length > 0) ||
                                    (prescription.historiaOcularFamiliar && prescription.historiaOcularFamiliar.length > 0) ||
                                    prescription.motivos_consulta_otros ||
                                    prescription.estado_salud_actual_otros ||
                                    prescription.historia_ocular_familiar_otros) && (
                                    <>
                                        <div>
                                            <h3 className="mb-3 text-lg font-semibold">Historia Clínica</h3>
                                            <div className="space-y-3">
                                                {((prescription.motivos && prescription.motivos.length > 0) ||
                                                    prescription.motivos_consulta_otros) && (
                                                    <div>
                                                        <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            Motivos de Consulta
                                                        </p>
                                                        {prescription.motivos && prescription.motivos.length > 0 && (
                                                            <div className="flex flex-wrap gap-2">
                                                                {prescription.motivos.map((motivo) => (
                                                                    <Badge key={motivo.id} variant="secondary">
                                                                        {motivo.name}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        )}
                                                        {prescription.motivos_consulta_otros && (
                                                            <div className="mt-2 rounded-md bg-blue-50 p-3">
                                                                <p className="text-sm text-gray-700">
                                                                    <span className="font-medium">Otros: </span>
                                                                    {prescription.motivos_consulta_otros}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                                {((prescription.estadoActual && prescription.estadoActual.length > 0) ||
                                                    prescription.estado_salud_actual_otros) && (
                                                    <div>
                                                        <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            Estado de Salud Actual
                                                        </p>
                                                        {prescription.estadoActual && prescription.estadoActual.length > 0 && (
                                                            <div className="flex flex-wrap gap-2">
                                                                {prescription.estadoActual.map((estado) => (
                                                                    <Badge key={estado.id} variant="secondary">
                                                                        {estado.name}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        )}
                                                        {prescription.estado_salud_actual_otros && (
                                                            <div className="mt-2 rounded-md bg-green-50 p-3">
                                                                <p className="text-sm text-gray-700">
                                                                    <span className="font-medium">Otros: </span>
                                                                    {prescription.estado_salud_actual_otros}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                                {((prescription.historiaOcularFamiliar && prescription.historiaOcularFamiliar.length > 0) ||
                                                    prescription.historia_ocular_familiar_otros) && (
                                                    <div>
                                                        <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            Historia Ocular Familiar
                                                        </p>
                                                        {prescription.historiaOcularFamiliar && prescription.historiaOcularFamiliar.length > 0 && (
                                                            <div className="flex flex-wrap gap-2">
                                                                {prescription.historiaOcularFamiliar.map((historia) => (
                                                                    <Badge key={historia.id} variant="secondary">
                                                                        {historia.name}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        )}
                                                        {prescription.historia_ocular_familiar_otros && (
                                                            <div className="mt-2 rounded-md bg-purple-50 p-3">
                                                                <p className="text-sm text-gray-700">
                                                                    <span className="font-medium">Otros: </span>
                                                                    {prescription.historia_ocular_familiar_otros}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        <Separator />
                                    </>
                                )}

                                {(prescription.refraccion_od_esfera ||
                                    prescription.refraccion_oi_esfera ||
                                    prescription.refraccion_od_cilindro ||
                                    prescription.refraccion_oi_cilindro) && (
                                    <>
                                        <div>
                                            <h3 className="mb-3 text-lg font-semibold">Refracción</h3>
                                            <div className="grid gap-4 md:grid-cols-2">
                                                <div>
                                                    <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Ojo Derecho (OD)</p>
                                                    <div className="space-y-1 text-sm">
                                                        {prescription.refraccion_od_esfera && <p>Esfera: {prescription.refraccion_od_esfera}</p>}
                                                        {prescription.refraccion_od_cilindro && (
                                                            <p>Cilindro: {prescription.refraccion_od_cilindro}</p>
                                                        )}
                                                        {prescription.refraccion_od_eje && <p>Eje: {prescription.refraccion_od_eje}°</p>}
                                                    </div>
                                                </div>
                                                <div>
                                                    <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Ojo Izquierdo (OI)</p>
                                                    <div className="space-y-1 text-sm">
                                                        {prescription.refraccion_oi_esfera && <p>Esfera: {prescription.refraccion_oi_esfera}</p>}
                                                        {prescription.refraccion_oi_cilindro && (
                                                            <p>Cilindro: {prescription.refraccion_oi_cilindro}</p>
                                                        )}
                                                        {prescription.refraccion_oi_eje && <p>Eje: {prescription.refraccion_oi_eje}°</p>}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <Separator />
                                    </>
                                )}

                                {prescription.refraccion_observaciones && (
                                    <div>
                                        <h3 className="mb-3 text-lg font-semibold">Observaciones</h3>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">{prescription.refraccion_observaciones}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
