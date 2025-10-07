import { useState } from 'react';
import { Eye, Plus, X } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface BiomicroscopiaData {
    // OD (Ojo Derecho)
    biomicroscopia_od_cejas?: string;
    biomicroscopia_od_pestanas?: string;
    biomicroscopia_od_parpados?: string;
    biomicroscopia_od_conjuntiva?: string;
    biomicroscopia_od_esclerotica?: string;
    biomicroscopia_od_cornea?: string;
    biomicroscopia_od_iris?: string;
    biomicroscopia_od_pupila?: string;
    biomicroscopia_od_cristalino?: string;

    // OI (Ojo Izquierdo)
    biomicroscopia_oi_cejas?: string;
    biomicroscopia_oi_pestanas?: string;
    biomicroscopia_oi_parpados?: string;
    biomicroscopia_oi_conjuntiva?: string;
    biomicroscopia_oi_esclerotica?: string;
    biomicroscopia_oi_cornea?: string;
    biomicroscopia_oi_iris?: string;
    biomicroscopia_oi_pupila?: string;
    biomicroscopia_oi_cristalino?: string;

    // Observaciones
    biomicroscopia_observaciones?: string;
}

interface BiomicroscopiaModalProps {
    data: BiomicroscopiaData;
    onChange: (field: string, value: string) => void;
    errors?: Record<string, string>;
}

const biomicroscopiaFields = [
    { key: 'cejas', label: 'Cejas' },
    { key: 'pestanas', label: 'Pestañas' },
    { key: 'parpados', label: 'Párpados' },
    { key: 'conjuntiva', label: 'Conjuntiva' },
    { key: 'esclerotica', label: 'Esclerótica' },
    { key: 'cornea', label: 'Córnea' },
    { key: 'iris', label: 'Iris' },
    { key: 'pupila', label: 'Pupila' },
    { key: 'cristalino', label: 'Cristalino' },
];

export default function BiomicroscopiaModal({ data, onChange, errors }: BiomicroscopiaModalProps) {
    const [isOpen, setIsOpen] = useState(false);

    // Generate summary text for OD and OI
    const generateSummary = () => {
        const odValues: string[] = [];
        const oiValues: string[] = [];

        biomicroscopiaFields.forEach(field => {
            const odValue = data[`biomicroscopia_od_${field.key}` as keyof BiomicroscopiaData];
            const oiValue = data[`biomicroscopia_oi_${field.key}` as keyof BiomicroscopiaData];

            if (odValue?.trim()) {
                odValues.push(`${field.label}: ${odValue}`);
            }
            if (oiValue?.trim()) {
                oiValues.push(`${field.label}: ${oiValue}`);
            }
        });

        return { odValues, oiValues };
    };

    const { odValues, oiValues } = generateSummary();
    const hasData = odValues.length > 0 || oiValues.length > 0 || data.biomicroscopia_observaciones?.trim();

    return (
        <div className="w-full">
            <div className="border border-gray-200 rounded-lg p-3">
                <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">Examen externo</h3>

                {/* Content */}
                <div className="flex-1 overflow-y-auto min-h-[120px]">
                    {hasData ? (
                        <div className="space-y-2 min-h-[120px] flex flex-col justify-start">
                            {/* OD Summary */}
                            {odValues.length > 0 && (
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold text-gray-900">OD:</p>
                                    <div className="pl-2 border-l-2 border-blue-100">
                                        <p className="text-xs text-gray-700 leading-tight line-clamp-2">
                                            {odValues.join(', ')}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* OI Summary */}
                            {oiValues.length > 0 && (
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold text-gray-900">OI:</p>
                                    <div className="pl-2 border-l-2 border-green-100">
                                        <p className="text-xs text-gray-700 leading-tight line-clamp-2">
                                            {oiValues.join(', ')}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Observaciones */}
                            {data.biomicroscopia_observaciones?.trim() && (
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold text-gray-900">Observaciones:</p>
                                    <div className="pl-2 border-l-2 border-amber-100">
                                        <p className="text-xs text-gray-700 leading-tight bg-gray-50 p-1 rounded line-clamp-2">
                                            {data.biomicroscopia_observaciones}
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center min-h-[120px] text-center">
                            <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                                <Eye className="h-4 w-4 text-gray-400" />
                            </div>
                            <p className="text-xs text-gray-500">
                                No se han completado campos del examen externo
                            </p>
                        </div>
                    )}
                    </div>

                {/* Footer */}
                <div className="px-3 py-2 border-t border-gray-100 bg-gray-50 flex justify-center">
                    <Dialog open={isOpen} onOpenChange={setIsOpen}>
                        <DialogTrigger asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="h-6 w-6 p-0 border-gray-300 hover:bg-blue-50 hover:border-blue-400 hover:shadow-sm transition-all duration-200"
                            >
                                <Plus className="h-3 w-3 text-blue-600" />
                            </Button>
                        </DialogTrigger>

                            <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                                <DialogHeader>
                                    <DialogTitle className="flex items-center gap-2">
                                        <Eye className="h-5 w-5 text-blue-600" />
                                        Examen Externo / Biomicroscopía
                                    </DialogTitle>
                                </DialogHeader>

                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        {/* Ojo Derecho */}
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                                Ojo Derecho
                                            </h3>
                                            <div className="space-y-4">
                                                {biomicroscopiaFields.map((field) => (
                                                    <div key={`od_${field.key}`} className="space-y-2">
                                                        <Label className="text-sm font-medium text-gray-700">
                                                            {field.label}
                                                        </Label>
                                                        <Input
                                                            value={data[`biomicroscopia_od_${field.key}` as keyof BiomicroscopiaData] || ''}
                                                            onChange={(e) => onChange(`biomicroscopia_od_${field.key}`, e.target.value)}
                                                            placeholder={`${field.label} OD`}
                                                            className="text-sm border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                        />
                                                        {errors?.[`biomicroscopia_od_${field.key}`] && (
                                                            <p className="text-xs text-red-600">
                                                                {errors[`biomicroscopia_od_${field.key}`]}
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Ojo Izquierdo */}
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                                Ojo Izquierdo
                                            </h3>
                                            <div className="space-y-4">
                                                {biomicroscopiaFields.map((field) => (
                                                    <div key={`oi_${field.key}`} className="space-y-2">
                                                        <Label className="text-sm font-medium text-gray-700">
                                                            {field.label}
                                                        </Label>
                                                        <Input
                                                            value={data[`biomicroscopia_oi_${field.key}` as keyof BiomicroscopiaData] || ''}
                                                            onChange={(e) => onChange(`biomicroscopia_oi_${field.key}`, e.target.value)}
                                                            placeholder={`${field.label} OI`}
                                                            className="text-sm border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                        />
                                                        {errors?.[`biomicroscopia_oi_${field.key}`] && (
                                                            <p className="text-xs text-red-600">
                                                                {errors[`biomicroscopia_oi_${field.key}`]}
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Observaciones */}
                                    <div className="space-y-3">
                                        <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                            Observaciones
                                        </h3>
                                        <div className="space-y-2">
                                            <Textarea
                                                value={data.biomicroscopia_observaciones || ''}
                                                onChange={(e) => onChange('biomicroscopia_observaciones', e.target.value)}
                                                placeholder="Observaciones adicionales sobre la biomicroscopía..."
                                                className="w-full border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 min-h-[100px] text-sm"
                                                rows={4}
                                            />
                                            {errors?.biomicroscopia_observaciones && (
                                                <p className="text-xs text-red-600">
                                                    {errors.biomicroscopia_observaciones}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="flex justify-end gap-2 pt-4 border-t border-gray-200">
                                        <Button
                                            variant="outline"
                                            onClick={() => setIsOpen(false)}
                                            className="border-gray-300"
                                        >
                                            Cerrar
                                        </Button>
                                        <Button
                                            onClick={() => setIsOpen(false)}
                                            className="bg-blue-600 hover:bg-blue-700"
                                        >
                                            Guardar cambios
                                        </Button>
                                    </div>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>
    );
}