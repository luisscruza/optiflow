import React from "react";
import { X } from "lucide-react";
import SelectSphere from "../ui/select-sphere";
import SelectCylinder from "../ui/select-cylinder";
import SelectAxis from "../ui/select-axis";
import SelectAdd from "../ui/select-add";

interface RefraccionModalData {
    // === CICLOPLEGIA ===
    cicloplegia_medicamento?: string;
    cicloplegia_num_gotas?: string;
    cicloplegia_hora_aplicacion?: string;
    cicloplegia_hora_examen?: string;

    // === AUTOREFRACCIÓN ===
    // Ojo Derecho
    autorefraccion_od_esfera?: string;
    autorefraccion_od_cilindro?: string;
    autorefraccion_od_eje?: string;
    // Ojo Izquierdo
    autorefraccion_oi_esfera?: string;
    autorefraccion_oi_cilindro?: string;
    autorefraccion_oi_eje?: string;

    // === REFRACCIÓN ===
    // Ojo Derecho
    refraccion_od_esfera?: string;
    refraccion_od_cilindro?: string;
    refraccion_od_eje?: string;
    // Ojo Izquierdo
    refraccion_oi_esfera?: string;
    refraccion_oi_cilindro?: string;
    refraccion_oi_eje?: string;

    // === RETINOSCOPÍA ===
    // Ojo Derecho
    retinoscopia_od_esfera?: string;
    retinoscopia_od_cilindro?: string;
    retinoscopia_od_eje?: string;
    // Ojo Izquierdo
    retinoscopia_oi_esfera?: string;
    retinoscopia_oi_cilindro?: string;
    retinoscopia_oi_eje?: string;
    // Tipo
    retinoscopia_estatica?: boolean;
    retinoscopia_dinamica?: boolean;

    // === SUBJETIVO (en sección de refracción) ===
    // Ojo Derecho
    refraccion_subjetivo_od_esfera?: string;
    refraccion_subjetivo_od_cilindro?: string;
    refraccion_subjetivo_od_eje?: string;
    refraccion_subjetivo_od_adicion?: string;
    // Ojo Izquierdo
    refraccion_subjetivo_oi_esfera?: string;
    refraccion_subjetivo_oi_cilindro?: string;
    refraccion_subjetivo_oi_eje?: string;
    refraccion_subjetivo_oi_adicion?: string;

    // === OBSERVACIONES DE REFRACCIÓN ===
    refraccion_observaciones?: string;

    // === SUBJETIVO ===
    subjetivo_od_esfera?: string;
    subjetivo_od_cilindro?: string;
    subjetivo_od_eje?: string;
    subjetivo_od_add?: string;
    subjetivo_od_dp?: string; // Distancia pupilar
    subjetivo_od_av_lejos?: string; // 20/
    subjetivo_od_av_cerca?: string;

    subjetivo_oi_esfera?: string;
    subjetivo_oi_cilindro?: string;
    subjetivo_oi_eje?: string;
    subjetivo_oi_add?: string;
    subjetivo_oi_dp?: string; // Distancia pupilar
    subjetivo_oi_av_lejos?: string; // 20/
    subjetivo_oi_av_cerca?: string;
}

interface RefraccionModalProps {
    isOpen: boolean;
    onClose: () => void;
    data: RefraccionModalData;
    onChange: (field: string, value: string | boolean) => void;
    errors?: Record<string, string>;
}

export default function RefraccionModal({ 
    isOpen, 
    onClose, 
    data, 
    onChange, 
    errors 
}: RefraccionModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-lg w-full max-w-7xl max-h-[90vh] overflow-y-auto shadow-2xl">
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <h2 className="text-lg font-semibold text-gray-900">Refracción - Detalles Completos</h2>
                    <button
                        onClick={onClose}
                        className="p-1 text-gray-400 hover:text-gray-600"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Content */}
                <div className="p-6 space-y-6">
                    {/* Row 1: Cicloplegia + Autorefracción */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Cicloplegia Section */}
                        <section className="border border-gray-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                                Cicloplegia
                            </h3>
                            <div className="grid grid-cols-1 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">
                                        Medicamento
                                    </label>
                                    <input
                                        type="text"
                                        value={data.cicloplegia_medicamento || ""}
                                        onChange={(e) => onChange("cicloplegia_medicamento", e.target.value)}
                                        className="w-full h-6 px-2 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div className="grid grid-cols-3 gap-2">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">
                                            N° gotas
                                        </label>
                                        <input
                                            type="text"
                                            value={data.cicloplegia_num_gotas || ""}
                                            onChange={(e) => onChange("cicloplegia_num_gotas", e.target.value)}
                                            className="w-full h-6 px-2 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">
                                            H. aplicación
                                        </label>
                                        <input
                                            type="text"
                                            value={data.cicloplegia_hora_aplicacion || ""}
                                            onChange={(e) => onChange("cicloplegia_hora_aplicacion", e.target.value)}
                                            className="w-full h-6 px-2 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">
                                            H. examen
                                        </label>
                                        <input
                                            type="text"
                                            value={data.cicloplegia_hora_examen || ""}
                                            onChange={(e) => onChange("cicloplegia_hora_examen", e.target.value)}
                                            className="w-full h-6 px-2 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Autorefracción Section */}
                        <section className="border border-gray-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                                Autorefracción
                            </h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-2 px-2 font-medium text-gray-700"></th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Esfera</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Cilindro</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Eje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="py-2 px-2 font-medium text-gray-700">OD</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.autorefraccion_od_esfera || ""}
                                                    onValueChange={(value) => onChange("autorefraccion_od_esfera", value)}
                                                    triggerClassName="h-6 text-xs w-fit"
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.autorefraccion_od_cilindro || ""}
                                                    onValueChange={(value) => onChange("autorefraccion_od_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.autorefraccion_od_eje || ""}
                                                    onValueChange={(value) => onChange("autorefraccion_od_eje", value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="py-2 px-2 font-medium text-gray-700">OI</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.autorefraccion_oi_esfera || ""}
                                                    onValueChange={(value) => onChange("autorefraccion_oi_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.autorefraccion_oi_cilindro || ""}
                                                    onValueChange={(value) => onChange("autorefraccion_oi_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.autorefraccion_oi_eje || ""}
                                                    onValueChange={(value) => onChange("autorefraccion_oi_eje", value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    {/* Row 2: Refracción + Retinoscopía */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Refracción Section */}
                        <section className="border border-gray-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                                Refracción
                            </h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-2 px-2 font-medium text-gray-700"></th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Esfera</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Cilindro</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Eje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="py-2 px-2 font-medium text-gray-700">OD</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.refraccion_od_esfera || ""}
                                                    onValueChange={(value) => onChange("refraccion_od_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.refraccion_od_cilindro || ""}
                                                    onValueChange={(value) => onChange("refraccion_od_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.refraccion_od_eje || ""}
                                                    onValueChange={(value) => onChange("refraccion_od_eje", value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="py-2 px-2 font-medium text-gray-700">OI</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.refraccion_oi_esfera || ""}
                                                    onValueChange={(value) => onChange("refraccion_oi_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.refraccion_oi_cilindro || ""}
                                                    onValueChange={(value) => onChange("refraccion_oi_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.refraccion_oi_eje || ""}
                                                    onValueChange={(value) => onChange("refraccion_oi_eje", value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {/* Retinoscopía Section */}
                        <section className="border border-gray-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                                Retinoscopía
                            </h3>
                            
                            {/* Checkboxes */}
                            <div className="flex items-center space-x-4 mb-4">
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        id="modal_retinoscopia_estatica"
                                        checked={data.retinoscopia_estatica || false}
                                        onChange={(e) => onChange("retinoscopia_estatica", e.target.checked)}
                                        className="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="modal_retinoscopia_estatica" className="text-xs font-medium text-gray-700">
                                        Estática
                                    </label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        id="modal_retinoscopia_dinamica"
                                        checked={data.retinoscopia_dinamica || false}
                                        onChange={(e) => onChange("retinoscopia_dinamica", e.target.checked)}
                                        className="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="modal_retinoscopia_dinamica" className="text-xs font-medium text-gray-700">
                                        Dinámica
                                    </label>
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-2 px-2 font-medium text-gray-700"></th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Esfera</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Cilindro</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Eje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="py-2 px-2 font-medium text-gray-700">OD</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.retinoscopia_od_esfera || ""}
                                                    onValueChange={(value) => onChange("retinoscopia_od_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.retinoscopia_od_cilindro || ""}
                                                    onValueChange={(value) => onChange("retinoscopia_od_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.retinoscopia_od_eje || ""}
                                                    onValueChange={(value) => onChange("retinoscopia_od_eje", value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="py-2 px-2 font-medium text-gray-700">OI</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.retinoscopia_oi_esfera || ""}
                                                    onValueChange={(value) => onChange("retinoscopia_oi_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.retinoscopia_oi_cilindro || ""}
                                                    onValueChange={(value) => onChange("retinoscopia_oi_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.retinoscopia_oi_eje || ""}
                                                    onValueChange={(value) => onChange("retinoscopia_oi_eje", value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    {/* Row 3: Subjetivo (Refracción) + Subjetivo */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Subjetivo (en sección de refracción) Section */}
                        <section className="border border-gray-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                                Subjetivo (Refracción)
                            </h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-2 px-2 font-medium text-gray-700"></th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Esfera</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Cilindro</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Eje</th>
                                            <th className="text-center py-2 px-2 font-medium text-gray-700">Adición</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="py-2 px-2 font-medium text-gray-700">OD</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.refraccion_subjetivo_od_esfera || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_od_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.refraccion_subjetivo_od_cilindro || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_od_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.refraccion_subjetivo_od_eje || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_od_eje", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAdd
                                                    value={data.refraccion_subjetivo_od_adicion || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_od_adicion", value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="py-2 px-2 font-medium text-gray-700">OI</td>
                                            <td className="py-2 px-2">
                                                <SelectSphere
                                                    value={data.refraccion_subjetivo_oi_esfera || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_oi_esfera", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectCylinder
                                                    value={data.refraccion_subjetivo_oi_cilindro || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_oi_cilindro", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAxis
                                                    value={data.refraccion_subjetivo_oi_eje || ""}
                                                    onValueChange={(value) => onChange("refraccion_subjetivo_oi_eje", value)}
                                                />
                                            </td>
                                            <td className="py-2 px-2">
                                                <SelectAdd
                                                    value={data.refraccion_subjetivo_oi_adicion || ""}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {/* Subjetivo Section */}
                        <section className="border border-gray-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                                Subjetivo
                            </h3>
                            <div className="space-y-3">
                                {/* OD Row */}
                                <div>
                                    <h4 className="text-xs font-medium text-gray-700 mb-2">OD</h4>
                                    <div className="grid grid-cols-4 gap-2 mb-2">
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Esfera</label>
                                            <SelectSphere
                                                value={data.subjetivo_od_esfera || ""}
                                                onValueChange={(value) => onChange("subjetivo_od_esfera", value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Cilindro</label>
                                            <SelectCylinder
                                                value={data.subjetivo_od_cilindro || ""}
                                                onValueChange={(value) => onChange("subjetivo_od_cilindro", value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Eje</label>
                                            <SelectAxis
                                                value={data.subjetivo_od_eje || ""}
                                                onValueChange={(value) => onChange("subjetivo_od_eje", value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Add</label>
                                            <SelectAdd
                                                value={data.subjetivo_od_add || ""}
                                                onValueChange={(value) => onChange("subjetivo_od_add", value)}
                                            />
                                        </div>
                                    </div>

                                </div>

                                {/* OI Row */}
                                <div>
                                    <h4 className="text-xs font-medium text-gray-700 mb-2">OI</h4>
                                    <div className="grid grid-cols-4 gap-2 mb-2">
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Esfera</label>
                                            <SelectSphere
                                                value={data.subjetivo_oi_esfera || ""}
                                                onValueChange={(value) => onChange("subjetivo_oi_esfera", value)}
                                                triggerClassName="h-6 text-xs"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Cilindro</label>
                                            <SelectCylinder
                                                value={data.subjetivo_oi_cilindro || ""}
                                                onValueChange={(value) => onChange("subjetivo_oi_cilindro", value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Eje</label>
                                            <SelectAxis
                                                value={data.subjetivo_oi_eje || ""}
                                                onValueChange={(value) => onChange("subjetivo_oi_eje", value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-gray-600 mb-1">Add</label>
                                            <SelectAdd
                                                value={data.subjetivo_oi_add || ""}
                                                onValueChange={(value) => onChange("subjetivo_oi_add", value)}
                                            />
                                        </div>
                                    </div>
                                  
                                </div>
                            </div>
                        </section>
                    </div>

                    {/* Observaciones Section - Full Width */}
                    <section className="border border-gray-200 rounded-lg p-4">
                        <h3 className="text-sm font-semibold mb-3 text-gray-900 border-b border-gray-200 pb-2">
                            Observaciones de Refracción
                        </h3>
                        <textarea
                            value={data.refraccion_observaciones || ""}
                            onChange={(e) => onChange("refraccion_observaciones", e.target.value)}
                            className="w-full h-20 px-3 py-2 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder="Observaciones adicionales..."
                        />
                    </section>
                </div>

                {/* Footer */}
                <div className="flex items-center justify-end p-4 border-t border-gray-200 space-x-2">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:ring-2 focus:ring-blue-500"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    );
}