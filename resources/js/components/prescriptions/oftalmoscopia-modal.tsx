import { useState } from 'react';
import { Eye, Plus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface OftalmoscopiaData {
    // Examen Pupilar - OD
    pupilar_od_fotomotor_directo?: string;
    pupilar_od_consensual?: string;
    pupilar_od_acomodativo?: string;

    // Examen Pupilar - OI
    pupilar_oi_fotomotor_directo?: string;
    pupilar_oi_consensual?: string;
    pupilar_oi_acomodativo?: string;

    // Oftalmoscopía - OD
    oftalmoscopia_od_color?: string;
    oftalmoscopia_od_papila?: string;
    oftalmoscopia_od_excavacion?: string;
    oftalmoscopia_od_relacion_av?: string;
    oftalmoscopia_od_macula?: string;
    oftalmoscopia_od_brillo_foveal?: string;
    oftalmoscopia_od_fijacion?: string;

    // Oftalmoscopía - OI
    oftalmoscopia_oi_color?: string;
    oftalmoscopia_oi_papila?: string;
    oftalmoscopia_oi_excavacion?: string;
    oftalmoscopia_oi_relacion_av?: string;
    oftalmoscopia_oi_macula?: string;
    oftalmoscopia_oi_brillo_foveal?: string;
    oftalmoscopia_oi_fijacion?: string;

    // Observaciones
    oftalmoscopia_observaciones?: string;
}

interface OftalmoscopiaModalProps {
    data: OftalmoscopiaData;
    onChange: (field: string, value: string) => void;
    errors?: Record<string, string>;
}

const pupilarOptions = ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'];

const pupilarFields = [
    { key: 'fotomotor_directo', label: 'Fotomotor directo' },
    { key: 'consensual', label: 'Consensual' },
    { key: 'acomodativo', label: 'Acomodativo' },
];

const oftalmoscopiaFields = [
    { key: 'color', label: 'Color' },
    { key: 'papila', label: 'Papila' },
    { key: 'excavacion', label: 'Excavación' },
    { key: 'relacion_av', label: 'R Arteria/Vena' },
    { key: 'macula', label: 'Mácula' },
    { key: 'brillo_foveal', label: 'Brillo Foveal' },
    { key: 'fijacion', label: 'Fijación' },
];

export default function OftalmoscopiaModal({ data, onChange, errors }: OftalmoscopiaModalProps) {
    const [isOpen, setIsOpen] = useState(false);

    // Generate summary text for pupilar and oftalmoscopia exams
    const generateSummary = () => {
        const pupilarOdValues: string[] = [];
        const pupilarOiValues: string[] = [];
        const oftalmoscopiaOdValues: string[] = [];
        const oftalmoscopiaOiValues: string[] = [];

        // Pupilar exam summary
        pupilarFields.forEach(field => {
            const odValue = data[`pupilar_od_${field.key}` as keyof OftalmoscopiaData];
            const oiValue = data[`pupilar_oi_${field.key}` as keyof OftalmoscopiaData];

            if (odValue?.trim()) {
                pupilarOdValues.push(`${field.label}: ${odValue}`);
            }
            if (oiValue?.trim()) {
                pupilarOiValues.push(`${field.label}: ${oiValue}`);
            }
        });

        // Oftalmoscopia summary
        oftalmoscopiaFields.forEach(field => {
            const odValue = data[`oftalmoscopia_od_${field.key}` as keyof OftalmoscopiaData];
            const oiValue = data[`oftalmoscopia_oi_${field.key}` as keyof OftalmoscopiaData];

            if (odValue?.trim()) {
                oftalmoscopiaOdValues.push(`${field.label}: ${odValue}`);
            }
            if (oiValue?.trim()) {
                oftalmoscopiaOiValues.push(`${field.label}: ${oiValue}`);
            }
        });

        return { pupilarOdValues, pupilarOiValues, oftalmoscopiaOdValues, oftalmoscopiaOiValues };
    };

    const { pupilarOdValues, pupilarOiValues, oftalmoscopiaOdValues, oftalmoscopiaOiValues } = generateSummary();
    const hasData = pupilarOdValues.length > 0 || pupilarOiValues.length > 0 || 
                   oftalmoscopiaOdValues.length > 0 || oftalmoscopiaOiValues.length > 0 || 
                   data.oftalmoscopia_observaciones?.trim();

    return (
        <div className="w-full">
            <div className="border border-gray-200 rounded-lg p-3">
                <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">Oftalmoscopía</h3>

                {/* Content */}
                <div className="flex-1 overflow-y-auto min-h-[120px]">
                    {hasData ? (
                        <div className="space-y-2 min-h-[120px] flex flex-col justify-start">
                            {/* Pupilar Exam Summary */}
                            {(pupilarOdValues.length > 0 || pupilarOiValues.length > 0) && (
                                <>
                                    <div className="space-y-1">
                                        <p className="text-xs font-semibold text-purple-700">Examen Pupilar:</p>
                                    </div>
                                    
                                    {pupilarOdValues.length > 0 && (
                                        <div className="space-y-1">
                                            <p className="text-xs font-semibold text-gray-900">OD:</p>
                                            <div className="pl-2 border-l-2 border-purple-100">
                                                <p className="text-xs text-gray-700 leading-tight line-clamp-1">
                                                    {pupilarOdValues.join(', ')}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {pupilarOiValues.length > 0 && (
                                        <div className="space-y-1">
                                            <p className="text-xs font-semibold text-gray-900">OI:</p>
                                            <div className="pl-2 border-l-2 border-purple-100">
                                                <p className="text-xs text-gray-700 leading-tight line-clamp-1">
                                                    {pupilarOiValues.join(', ')}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}

                            {/* Oftalmoscopia Summary */}
                            {(oftalmoscopiaOdValues.length > 0 || oftalmoscopiaOiValues.length > 0) && (
                                <>
                                    <div className="space-y-1">
                                        <p className="text-xs font-semibold text-indigo-700">Oftalmoscopía:</p>
                                    </div>

                                    {oftalmoscopiaOdValues.length > 0 && (
                                        <div className="space-y-1">
                                            <p className="text-xs font-semibold text-gray-900">OD:</p>
                                            <div className="pl-2 border-l-2 border-blue-100">
                                                <p className="text-xs text-gray-700 leading-tight line-clamp-2">
                                                    {oftalmoscopiaOdValues.join(', ')}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {oftalmoscopiaOiValues.length > 0 && (
                                        <div className="space-y-1">
                                            <p className="text-xs font-semibold text-gray-900">OI:</p>
                                            <div className="pl-2 border-l-2 border-green-100">
                                                <p className="text-xs text-gray-700 leading-tight line-clamp-2">
                                                    {oftalmoscopiaOiValues.join(', ')}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}

                            {/* Observaciones */}
                            {data.oftalmoscopia_observaciones?.trim() && (
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold text-gray-900">Observaciones:</p>
                                    <div className="pl-2 border-l-2 border-amber-100">
                                        <p className="text-xs text-gray-700 leading-tight bg-gray-50 p-1 rounded line-clamp-2">
                                            {data.oftalmoscopia_observaciones}
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
                                No se han completado campos de oftalmoscopía
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

                            <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
                                <DialogHeader>
                                    <DialogTitle className="flex items-center gap-2">
                                        <Eye className="h-5 w-5 text-blue-600" />
                                        Examen Pupilar / Oftalmoscopía
                                    </DialogTitle>
                                </DialogHeader>

                                <div className="space-y-6">
                                    {/* Examen Pupilar */}
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                            Examen Pupilar
                                        </h3>
                                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            {/* Ojo Derecho */}
                                            <div className="space-y-4">
                                                <h4 className="text-md font-medium text-gray-800 border-b border-gray-300 pb-1">
                                                    Ojo Derecho
                                                </h4>
                                                <div className="space-y-3">
                                                    {pupilarFields.map((field) => (
                                                        <div key={field.key} className="space-y-2">
                                                            <Label className="text-gray-700 font-medium text-sm">
                                                                {field.label}
                                                            </Label>
                                                            <Select
                                                                value={data[`pupilar_od_${field.key}` as keyof OftalmoscopiaData] || ''}
                                                                onValueChange={(value) => onChange(`pupilar_od_${field.key}`, value)}
                                                            >
                                                                <SelectTrigger className="border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                                    <SelectValue placeholder="Seleccionar" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {pupilarOptions.map((option) => (
                                                                        <SelectItem key={option} value={option}>
                                                                            {option}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                            {errors?.[`pupilar_od_${field.key}`] && (
                                                                <p className="text-xs text-red-600">
                                                                    {errors[`pupilar_od_${field.key}`]}
                                                                </p>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Ojo Izquierdo */}
                                            <div className="space-y-4">
                                                <h4 className="text-md font-medium text-gray-800 border-b border-gray-300 pb-1">
                                                    Ojo Izquierdo
                                                </h4>
                                                <div className="space-y-3">
                                                    {pupilarFields.map((field) => (
                                                        <div key={field.key} className="space-y-2">
                                                            <Label className="text-gray-700 font-medium text-sm">
                                                                {field.label}
                                                            </Label>
                                                            <Select
                                                                value={data[`pupilar_oi_${field.key}` as keyof OftalmoscopiaData] || ''}
                                                                onValueChange={(value) => onChange(`pupilar_oi_${field.key}`, value)}
                                                            >
                                                                <SelectTrigger className="border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                                    <SelectValue placeholder="Seleccionar" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {pupilarOptions.map((option) => (
                                                                        <SelectItem key={option} value={option}>
                                                                            {option}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                            {errors?.[`pupilar_oi_${field.key}`] && (
                                                                <p className="text-xs text-red-600">
                                                                    {errors[`pupilar_oi_${field.key}`]}
                                                                </p>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Oftalmoscopía */}
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                            Oftalmoscopía
                                        </h3>
                                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            {/* Ojo Derecho */}
                                            <div className="space-y-4">
                                                <h4 className="text-md font-medium text-gray-800 border-b border-gray-300 pb-1">
                                                    Ojo Derecho
                                                </h4>
                                                <div className="space-y-3">
                                                    {oftalmoscopiaFields.map((field) => (
                                                        <div key={field.key} className="space-y-2">
                                                            <Label className="text-gray-700 font-medium text-sm">
                                                                {field.label}
                                                            </Label>
                                                            <Input
                                                                value={data[`oftalmoscopia_od_${field.key}` as keyof OftalmoscopiaData] || ''}
                                                                onChange={(e) => onChange(`oftalmoscopia_od_${field.key}`, e.target.value)}
                                                                className="border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                                                                placeholder={`Ingrese ${field.label.toLowerCase()}`}
                                                            />
                                                            {errors?.[`oftalmoscopia_od_${field.key}`] && (
                                                                <p className="text-xs text-red-600">
                                                                    {errors[`oftalmoscopia_od_${field.key}`]}
                                                                </p>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Ojo Izquierdo */}
                                            <div className="space-y-4">
                                                <h4 className="text-md font-medium text-gray-800 border-b border-gray-300 pb-1">
                                                    Ojo Izquierdo
                                                </h4>
                                                <div className="space-y-3">
                                                    {oftalmoscopiaFields.map((field) => (
                                                        <div key={field.key} className="space-y-2">
                                                            <Label className="text-gray-700 font-medium text-sm">
                                                                {field.label}
                                                            </Label>
                                                            <Input
                                                                value={data[`oftalmoscopia_oi_${field.key}` as keyof OftalmoscopiaData] || ''}
                                                                onChange={(e) => onChange(`oftalmoscopia_oi_${field.key}`, e.target.value)}
                                                                className="border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                                                                placeholder={`Ingrese ${field.label.toLowerCase()}`}
                                                            />
                                                            {errors?.[`oftalmoscopia_oi_${field.key}`] && (
                                                                <p className="text-xs text-red-600">
                                                                    {errors[`oftalmoscopia_oi_${field.key}`]}
                                                                </p>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
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
                                                value={data.oftalmoscopia_observaciones || ''}
                                                onChange={(e) => onChange('oftalmoscopia_observaciones', e.target.value)}
                                                placeholder="Observaciones adicionales sobre la oftalmoscopía..."
                                                className="w-full border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 min-h-[100px] text-sm"
                                                rows={4}
                                            />
                                            {errors?.oftalmoscopia_observaciones && (
                                                <p className="text-xs text-red-600">
                                                    {errors.oftalmoscopia_observaciones}
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