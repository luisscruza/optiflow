import { Head, Link } from '@inertiajs/react';
import { Download, Edit } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';

import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import prescriptions from '@/routes/prescriptions';
import { Prescription, type BreadcrumbItem } from '@/types';

interface CompanyData {
    company_name?: string;
    logo?: string;
    [key: string]: string | undefined;
}

interface Props {
    prescription: Prescription;
    company: CompanyData;
}

export default function PrescriptionShow({ prescription, company }: Props) {
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
            hour: 'numeric',
            minute: 'numeric',
            hour12: true,
        });
    };

    const formatNextControlDate = (dateString: string) => {
        const date = new Date(dateString);
        date.setMonth(date.getMonth() + 12);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const padNumber = (num: number, length: number) => {
        return String(num).padStart(length, '0');
    };

    const dpOd = prescription.subjetivo_od_dp ?? null;
    const dpOi = prescription.subjetivo_oi_dp ?? null;
    const showCombinedDP = dpOd && dpOi && dpOd === dpOi;
    const showSeparateDP = (dpOd && dpOi && dpOd !== dpOi) || (dpOd && !dpOi) || (!dpOd && dpOi);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Receta #${prescription.id}`} />

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Action buttons */}
                <div className="mb-6 flex justify-end gap-2 print:hidden">
                    {can('edit prescriptions') && (
                        <Link href={prescriptions.edit(prescription).url}>
                            <Button variant="outline" size="sm">
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </Button>
                        </Link>
                    )}
                    <a target="_blank" href={prescriptions.pdf(prescription).url} rel="noreferrer">
                        <Button size="sm">
                            <Download className="mr-2 h-4 w-4" />
                            Descargar Rx final
                        </Button>
                    </a>
                </div>

                {/* Prescription document */}
                <div className="rounded-lg border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    {/* Header */}
                    <div className="flex items-start justify-between border-b-[3px] border-blue-700 pb-5">
                        <div className="flex-1">
                            <h1 className="mb-2 text-xl font-bold tracking-wide text-gray-900 dark:text-white">{company.company_name}</h1>
                            {prescription.workspace?.address && (
                                <p className="text-sm text-gray-700 dark:text-gray-300">{prescription.workspace.address}</p>
                            )}
                            {prescription.workspace?.phone && (
                                <p className="text-sm text-gray-700 dark:text-gray-300">{prescription.workspace.phone}</p>
                            )}
                        </div>

                        {company.logo && (
                            <div className="flex items-center justify-center px-4">
                                <img src={`/storage/${company.logo}`} alt="Logo" className="max-h-[90px] max-w-[160px] object-contain" />
                            </div>
                        )}

                        <div className="text-right">
                            {prescription.patient && (
                                <p className="text-sm text-gray-700 dark:text-gray-300">HISTORIA N° {padNumber(prescription.patient.id, 5)}</p>
                            )}
                            <p className="text-sm text-gray-700 dark:text-gray-300">RECETA N° {padNumber(prescription.id, 5)}</p>
                            <Separator className="my-2" />
                            <div className="space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                                <p>
                                    <span className="font-semibold">Vigencia:</span> 2 meses
                                </p>
                                <p>
                                    <span className="font-semibold">Sucursal:</span> {prescription.workspace?.name}
                                </p>
                                {prescription.optometrist && (
                                    <p>
                                        <span className="font-semibold">Evaluado por:</span> {prescription.optometrist.name}
                                    </p>
                                )}
                                <p>
                                    <span className="font-semibold">Fecha:</span> {formatDate(prescription.created_at)}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Title */}
                    <h2 className="my-6 text-center text-lg font-bold tracking-widest text-gray-900 uppercase dark:text-white">
                        Prescripción de Lentes
                    </h2>

                    {/* Patient info */}
                    {prescription.patient && (
                        <div className="mb-5 rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                            <p className="text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                                <span className="font-semibold">Paciente:</span> {prescription.patient.name.toUpperCase()}
                                {prescription.patient.identification_number && (
                                    <>
                                        {'      '}
                                        <span className="font-semibold">Identificación:</span> {prescription.patient.identification_number}
                                    </>
                                )}
                                {prescription.patient.phone_primary && (
                                    <>
                                        {'      '}
                                        <span className="font-semibold">Teléfono:</span> {prescription.patient.phone_primary}
                                    </>
                                )}
                                {prescription.patient.age != null && (
                                    <>
                                        {'      '}
                                        <span className="font-semibold">Edad:</span> {prescription.patient.age} año(s)
                                    </>
                                )}
                            </p>
                        </div>
                    )}

                    {/* Prescription table */}
                    <div className="my-5 overflow-x-auto">
                        <table className="w-full border-collapse shadow-sm">
                            <thead>
                                <tr>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        Rx Final
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        Esfera
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        Cilindro
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        Eje
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        Adición
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        Alt Bif
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        AV Lejos
                                    </th>
                                    <th className="border border-gray-900 px-2 py-2.5 text-center text-xs font-semibold tracking-wide text-gray-900 uppercase dark:border-gray-500 dark:text-gray-100">
                                        AV Cerca
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-gray-50 dark:bg-gray-800">
                                    <td className="border border-gray-900 py-2.5 pl-4 text-left text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        OD (Derecho)
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_od_esfera ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_od_cilindro ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_od_eje ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_od_add ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        -
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_od_av_lejos ? `20/${prescription.subjetivo_od_av_lejos}` : '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_od_av_cerca ? `20/${prescription.subjetivo_od_av_cerca}` : '-'}
                                    </td>
                                </tr>
                                <tr>
                                    <td className="border border-gray-900 py-2.5 pl-4 text-left text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        OI (Izquierdo)
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_oi_esfera ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_oi_cilindro ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_oi_eje ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm font-bold text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_oi_add ?? '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        -
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_oi_av_lejos ? `20/${prescription.subjetivo_oi_av_lejos}` : '-'}
                                    </td>
                                    <td className="border border-gray-900 px-2 py-2.5 text-center text-sm text-gray-900 dark:border-gray-500 dark:text-gray-100">
                                        {prescription.subjetivo_oi_av_cerca ? `20/${prescription.subjetivo_oi_av_cerca}` : '-'}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {/* Distancia Naso Pupilar */}
                    {(showCombinedDP || showSeparateDP) && (
                        <div className="my-4 flex gap-8">
                            {showCombinedDP ? (
                                <span className="rounded border border-gray-300 bg-white px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                                    <span className="font-semibold">Distancia Naso Pupilar:</span> {dpOd}mm
                                </span>
                            ) : (
                                <>
                                    {dpOd && (
                                        <span className="rounded border border-gray-300 bg-white px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                                            <span className="font-semibold">DNP OD:</span> {dpOd}mm
                                        </span>
                                    )}
                                    {dpOi && (
                                        <span className="rounded border border-gray-300 bg-white px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                                            <span className="font-semibold">DNP OI:</span> {dpOi}mm
                                        </span>
                                    )}
                                </>
                            )}
                        </div>
                    )}

                    {/* Footer information */}
                    <div className="mt-5 space-y-2 rounded border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p className="text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                            <span className="font-semibold">Próximo Control Visual:</span> {formatNextControlDate(prescription.created_at)}
                        </p>
                        {prescription.motivos && prescription.motivos.length > 0 && (
                            <p className="text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                                <span className="font-semibold">Diagnósticos:</span>{' '}
                                {prescription.motivos.map((motivo) => motivo.name.charAt(0).toUpperCase() + motivo.name.slice(1)).join(', ')}
                            </p>
                        )}
                        {prescription.lentesRecomendados && prescription.lentesRecomendados.length > 0 && (
                            <p className="text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                                <span className="font-semibold">Tipo de Lentes Recomendados:</span>{' '}
                                {prescription.lentesRecomendados.map((lente) => lente.name.charAt(0).toUpperCase() + lente.name.slice(1)).join(', ')}
                            </p>
                        )}
                        {prescription.refraccion_observaciones && (
                            <p className="text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                                <span className="font-semibold">Observaciones Adicionales:</span> {prescription.refraccion_observaciones}
                            </p>
                        )}
                    </div>

                    {/* Signature section */}
                    <div className="mt-16 text-center">
                        <div className="mx-auto w-72 border-t-2 border-blue-700 pt-2 text-sm text-gray-700 dark:text-gray-300">FIRMA PROFESIONAL</div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
