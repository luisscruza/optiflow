import { ChevronDown, ChevronRight, FileText, Heart, Eye } from 'lucide-react';
import { useState } from 'react';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Label } from '@/components/ui/label';

interface MasterTableData {
    id: number;
    name: string;
    alias: string;
    description?: string;
    items: Array<{
        id: number;
        mastertable_id: number;
        name: string;
    }>;
}

interface ClinicalHistoryData {
    motivos_consulta: number[];
    estado_salud_actual: number[];
    historia_ocular_familiar: number[];
}

interface ClinicalHistoryProps {
    masterTables: Record<string, MasterTableData>;
    data: ClinicalHistoryData;
    onDataChange: (field: keyof ClinicalHistoryData, value: number[]) => void;
    errors?: Partial<Record<keyof ClinicalHistoryData, string>>;
}

export default function ClinicalHistory({ masterTables, data, onDataChange, errors }: ClinicalHistoryProps) {
    // Collapsible states
    const [clinicalHistoryOpen, setClinicalHistoryOpen] = useState(false);
    const [motivosConsultaOpen, setMotivosConsultaOpen] = useState(false);
    const [estadoSaludOpen, setEstadoSaludOpen] = useState(false);
    const [historiaOcularOpen, setHistoriaOcularOpen] = useState(false);

    // Helper function to count total selected items across all clinical history sections
    const getSelectedItemsCount = () => {
        return data.motivos_consulta.length + data.estado_salud_actual.length + data.historia_ocular_familiar.length;
    };

    // Helper function to handle checkbox changes
    const handleCheckboxChange = (field: keyof ClinicalHistoryData, itemId: number, checked: boolean) => {
        const currentValues = data[field];
        if (checked) {
            onDataChange(field, [...currentValues, itemId]);
        } else {
            onDataChange(field, currentValues.filter(id => id !== itemId));
        }
    };

    return (
        <Collapsible open={clinicalHistoryOpen} onOpenChange={setClinicalHistoryOpen}>
            <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                <CollapsibleTrigger asChild>
                    <CardHeader className="px-6 py-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50/50 transition-colors">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <FileText className="h-5 w-5 text-blue-600" />
                                <div>
                                    <CardTitle className="text-lg font-semibold text-gray-900">
                                        Historial clínico
                                    </CardTitle>
                                    <CardDescription className="text-sm text-gray-500 mt-1">
                                        {getSelectedItemsCount() > 0 
                                            ? `${getSelectedItemsCount()} elementos seleccionados`
                                            : 'Información médica del paciente (opcional)'
                                        }
                                    </CardDescription>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-full">
                                    OPCIONAL
                                </span>
                                {clinicalHistoryOpen ? (
                                    <ChevronDown className="h-4 w-4 text-gray-500" />
                                ) : (
                                    <ChevronRight className="h-4 w-4 text-gray-500" />
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </CollapsibleTrigger>
                
                <CollapsibleContent>
                    <CardContent className="px-6 py-6">
                        <div className="space-y-6">
                            {/* Motivo de consulta */}
                            <Collapsible open={motivosConsultaOpen} onOpenChange={setMotivosConsultaOpen}>
                                <div className="border border-gray-200 rounded-lg overflow-hidden">
                                    <CollapsibleTrigger asChild>
                                        <div className="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                                    <span className="text-sm font-semibold text-blue-700">1</span>
                                                </div>
                                                <div>
                                                    <h3 className="font-medium text-gray-900">Motivo de consulta</h3>
                                                    <p className="text-sm text-gray-500">
                                                        {data.motivos_consulta.length > 0 
                                                            ? `${data.motivos_consulta.length} seleccionados`
                                                            : 'Ningún motivo seleccionado'
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                            {motivosConsultaOpen ? (
                                                <ChevronDown className="h-4 w-4 text-gray-500" />
                                            ) : (
                                                <ChevronRight className="h-4 w-4 text-gray-500" />
                                            )}
                                        </div>
                                    </CollapsibleTrigger>
                                    
                                    <CollapsibleContent>
                                        {masterTables.motivos_consulta && (
                                            <div className="p-4 space-y-3">
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                    {masterTables.motivos_consulta.items.map((item) => (
                                                        <div key={item.id} className="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-50">
                                                            <Checkbox
                                                                id={`motivos_consulta_${item.id}`}
                                                                checked={data.motivos_consulta.includes(item.id)}
                                                                onCheckedChange={(checked) => handleCheckboxChange('motivos_consulta', item.id, checked as boolean)}
                                                                className="data-[state=checked]:bg-blue-600"
                                                            />
                                                            <Label
                                                                htmlFor={`motivos_consulta_${item.id}`}
                                                                className="text-sm text-gray-700 cursor-pointer leading-5"
                                                            >
                                                                {item.name}
                                                            </Label>
                                                        </div>
                                                    ))}
                                                </div>
                                                {errors?.motivos_consulta && (
                                                    <p className="text-sm text-red-600 mt-2">{errors.motivos_consulta}</p>
                                                )}
                                            </div>
                                        )}
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            {/* Estado general de salud actual */}
                            <Collapsible open={estadoSaludOpen} onOpenChange={setEstadoSaludOpen}>
                                <div className="border border-gray-200 rounded-lg overflow-hidden">
                                    <CollapsibleTrigger asChild>
                                        <div className="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                                                    <Heart className="h-4 w-4 text-green-700" />
                                                </div>
                                                <div>
                                                    <h3 className="font-medium text-gray-900">Estado general de salud actual</h3>
                                                    <p className="text-sm text-gray-500">
                                                        {data.estado_salud_actual.length > 0 
                                                            ? `${data.estado_salud_actual.length} condiciones seleccionadas`
                                                            : 'Ninguna condición seleccionada'
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                            {estadoSaludOpen ? (
                                                <ChevronDown className="h-4 w-4 text-gray-500" />
                                            ) : (
                                                <ChevronRight className="h-4 w-4 text-gray-500" />
                                            )}
                                        </div>
                                    </CollapsibleTrigger>
                                    
                                    <CollapsibleContent>
                                        {masterTables.estado_salud_actual && (
                                            <div className="p-4 space-y-3">
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                    {masterTables.estado_salud_actual.items.map((item) => (
                                                        <div key={item.id} className="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-50">
                                                            <Checkbox
                                                                id={`estado_salud_actual_${item.id}`}
                                                                checked={data.estado_salud_actual.includes(item.id)}
                                                                onCheckedChange={(checked) => handleCheckboxChange('estado_salud_actual', item.id, checked as boolean)}
                                                                className="data-[state=checked]:bg-green-600"
                                                            />
                                                            <Label
                                                                htmlFor={`estado_salud_actual_${item.id}`}
                                                                className="text-sm text-gray-700 cursor-pointer leading-5"
                                                            >
                                                                {item.name}
                                                            </Label>
                                                        </div>
                                                    ))}
                                                </div>
                                                {errors?.estado_salud_actual && (
                                                    <p className="text-sm text-red-600 mt-2">{errors.estado_salud_actual}</p>
                                                )}
                                            </div>
                                        )}
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>

                            {/* Historia ocular familiar */}
                            <Collapsible open={historiaOcularOpen} onOpenChange={setHistoriaOcularOpen}>
                                <div className="border border-gray-200 rounded-lg overflow-hidden">
                                    <CollapsibleTrigger asChild>
                                        <div className="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100">
                                                    <Eye className="h-4 w-4 text-purple-700" />
                                                </div>
                                                <div>
                                                    <h3 className="font-medium text-gray-900">Historia ocular familiar</h3>
                                                    <p className="text-sm text-gray-500">
                                                        {data.historia_ocular_familiar.length > 0 
                                                            ? `${data.historia_ocular_familiar.length} antecedentes seleccionados`
                                                            : 'Ningún antecedente seleccionado'
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                            {historiaOcularOpen ? (
                                                <ChevronDown className="h-4 w-4 text-gray-500" />
                                            ) : (
                                                <ChevronRight className="h-4 w-4 text-gray-500" />
                                            )}
                                        </div>
                                    </CollapsibleTrigger>
                                    
                                    <CollapsibleContent>
                                        {masterTables.historia_ocular_familiar && (
                                            <div className="p-4 space-y-3">
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                    {masterTables.historia_ocular_familiar.items.map((item) => (
                                                        <div key={item.id} className="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-50">
                                                            <Checkbox
                                                                id={`historia_ocular_familiar_${item.id}`}
                                                                checked={data.historia_ocular_familiar.includes(item.id)}
                                                                onCheckedChange={(checked) => handleCheckboxChange('historia_ocular_familiar', item.id, checked as boolean)}
                                                                className="data-[state=checked]:bg-purple-600"
                                                            />
                                                            <Label
                                                                htmlFor={`historia_ocular_familiar_${item.id}`}
                                                                className="text-sm text-gray-700 cursor-pointer leading-5"
                                                            >
                                                                {item.name}
                                                            </Label>
                                                        </div>
                                                    ))}
                                                </div>
                                                {errors?.historia_ocular_familiar && (
                                                    <p className="text-sm text-red-600 mt-2">{errors.historia_ocular_familiar}</p>
                                                )}
                                            </div>
                                        )}
                                    </CollapsibleContent>
                                </div>
                            </Collapsible>
                        </div>
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>
    );
}