import { X } from 'lucide-react';
import SelectAdd from '../ui/select-add';
import SelectAxis from '../ui/select-axis';
import SelectCylinder from '../ui/select-cylinder';
import SelectSphere from '../ui/select-sphere';

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
    alt_bif_od?: string;
    subjetivo_od_av_lejos?: string; // 20/
    subjetivo_od_av_cerca?: string;

    subjetivo_oi_esfera?: string;
    subjetivo_oi_cilindro?: string;
    subjetivo_oi_eje?: string;
    subjetivo_oi_add?: string;
    subjetivo_oi_dp?: string; // Distancia pupilar
    alt_bif_oi?: string;
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

export default function RefraccionModal({ isOpen, onClose, data, onChange, errors }: RefraccionModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
            <div className="max-h-[90vh] w-full max-w-7xl overflow-y-auto rounded-lg bg-white shadow-2xl">
                {/* Header */}
                <div className="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white p-4">
                    <h2 className="text-lg font-semibold text-gray-900">Refracción - Detalles Completos</h2>
                    <button onClick={onClose} className="p-1 text-gray-400 hover:text-gray-600">
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Content */}
                <div className="space-y-6 p-6">
                    {/* Row 1: Cicloplegia + Autorefracción */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {/* Cicloplegia Section */}
                        <section className="rounded-lg border border-gray-200 p-4">
                            <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Cicloplegia</h3>
                            <div className="grid grid-cols-1 gap-3">
                                <div>
                                    <label className="mb-1 block text-xs font-medium text-gray-700">Medicamento</label>
                                    <input
                                        type="text"
                                        value={data.cicloplegia_medicamento || ''}
                                        onChange={(e) => onChange('cicloplegia_medicamento', e.target.value)}
                                        className="h-6 w-full rounded border border-gray-300 px-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div className="grid grid-cols-3 gap-2">
                                    <div>
                                        <label className="mb-1 block text-xs font-medium text-gray-700">N° gotas</label>
                                        <input
                                            type="text"
                                            value={data.cicloplegia_num_gotas || ''}
                                            onChange={(e) => onChange('cicloplegia_num_gotas', e.target.value)}
                                            className="h-6 w-full rounded border border-gray-300 px-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-xs font-medium text-gray-700">H. aplicación</label>
                                        <input
                                            type="text"
                                            value={data.cicloplegia_hora_aplicacion || ''}
                                            onChange={(e) => onChange('cicloplegia_hora_aplicacion', e.target.value)}
                                            className="h-6 w-full rounded border border-gray-300 px-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-xs font-medium text-gray-700">H. examen</label>
                                        <input
                                            type="text"
                                            value={data.cicloplegia_hora_examen || ''}
                                            onChange={(e) => onChange('cicloplegia_hora_examen', e.target.value)}
                                            className="h-6 w-full rounded border border-gray-300 px-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Autorefracción Section */}
                        <section className="rounded-lg border border-gray-200 p-4">
                            <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Autorefracción</h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="px-2 py-2 text-left font-medium text-gray-700"></th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Esfera</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Cilindro</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Eje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="px-2 py-2 font-medium text-gray-700">OD</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.autorefraccion_od_esfera || ''}
                                                    onValueChange={(value) => onChange('autorefraccion_od_esfera', value)}
                                                    triggerClassName="h-6 text-xs w-fit"
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.autorefraccion_od_cilindro || ''}
                                                    onValueChange={(value) => onChange('autorefraccion_od_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.autorefraccion_od_eje || ''}
                                                    onValueChange={(value) => onChange('autorefraccion_od_eje', value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="px-2 py-2 font-medium text-gray-700">OI</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.autorefraccion_oi_esfera || ''}
                                                    onValueChange={(value) => onChange('autorefraccion_oi_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.autorefraccion_oi_cilindro || ''}
                                                    onValueChange={(value) => onChange('autorefraccion_oi_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.autorefraccion_oi_eje || ''}
                                                    onValueChange={(value) => onChange('autorefraccion_oi_eje', value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    {/* Row 2: Retinoscopía Dinámica + Retinoscopía Estática */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {/* Retinoscopía Dinámica Section */}
                        <section className="rounded-lg border border-gray-200 p-4">
                            <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Retinoscopía Dinámica</h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="px-2 py-2 text-left font-medium text-gray-700"></th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Esfera</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Cilindro</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Eje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="px-2 py-2 font-medium text-gray-700">OD</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.refraccion_od_esfera || ''}
                                                    onValueChange={(value) => onChange('refraccion_od_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.refraccion_od_cilindro || ''}
                                                    onValueChange={(value) => onChange('refraccion_od_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.refraccion_od_eje || ''}
                                                    onValueChange={(value) => onChange('refraccion_od_eje', value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="px-2 py-2 font-medium text-gray-700">OI</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.refraccion_oi_esfera || ''}
                                                    onValueChange={(value) => onChange('refraccion_oi_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.refraccion_oi_cilindro || ''}
                                                    onValueChange={(value) => onChange('refraccion_oi_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.refraccion_oi_eje || ''}
                                                    onValueChange={(value) => onChange('refraccion_oi_eje', value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {/* Retinoscopía Estática Section */}
                        <section className="rounded-lg border border-gray-200 p-4">
                            <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Retinoscopía Estática</h3>

                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="px-2 py-2 text-left font-medium text-gray-700"></th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Esfera</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Cilindro</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Eje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="px-2 py-2 font-medium text-gray-700">OD</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.retinoscopia_od_esfera || ''}
                                                    onValueChange={(value) => onChange('retinoscopia_od_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.retinoscopia_od_cilindro || ''}
                                                    onValueChange={(value) => onChange('retinoscopia_od_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.retinoscopia_od_eje || ''}
                                                    onValueChange={(value) => onChange('retinoscopia_od_eje', value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="px-2 py-2 font-medium text-gray-700">OI</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.retinoscopia_oi_esfera || ''}
                                                    onValueChange={(value) => onChange('retinoscopia_oi_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.retinoscopia_oi_cilindro || ''}
                                                    onValueChange={(value) => onChange('retinoscopia_oi_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.retinoscopia_oi_eje || ''}
                                                    onValueChange={(value) => onChange('retinoscopia_oi_eje', value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    {/* Row 3: Sobre-subjetivo (Refracción) + Prescripción Final */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {/* Sobre-subjetivo (en sección de refracción) Section */}
                        <section className="rounded-lg border border-gray-200 p-4">
                            <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Sobre-subjetivo (Refracción)</h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="px-2 py-2 text-left font-medium text-gray-700"></th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Esfera</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Cilindro</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Eje</th>
                                            <th className="px-2 py-2 text-center font-medium text-gray-700">Adición</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="border-b border-gray-100">
                                            <td className="px-2 py-2 font-medium text-gray-700">OD</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.refraccion_subjetivo_od_esfera || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_od_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.refraccion_subjetivo_od_cilindro || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_od_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.refraccion_subjetivo_od_eje || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_od_eje', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAdd
                                                    value={data.refraccion_subjetivo_od_adicion || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_od_adicion', value)}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="px-2 py-2 font-medium text-gray-700">OI</td>
                                            <td className="px-2 py-2">
                                                <SelectSphere
                                                    value={data.refraccion_subjetivo_oi_esfera || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_oi_esfera', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectCylinder
                                                    value={data.refraccion_subjetivo_oi_cilindro || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_oi_cilindro', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAxis
                                                    value={data.refraccion_subjetivo_oi_eje || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_oi_eje', value)}
                                                />
                                            </td>
                                            <td className="px-2 py-2">
                                                <SelectAdd
                                                    value={data.refraccion_subjetivo_oi_adicion || ''}
                                                    onValueChange={(value) => onChange('refraccion_subjetivo_oi_adicion', value)}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {/* Prescripción Final Section */}
                        <section className="rounded-lg border border-gray-200 p-4">
                            <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Prescripción Final</h3>
                            <div className="space-y-3">
                                {/* OD Row */}
                                <div>
                                    <h4 className="mb-2 text-xs font-medium text-gray-700">OD</h4>
                                    <div className="mb-2 grid grid-cols-4 gap-2">
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Esfera</label>
                                            <SelectSphere
                                                value={data.subjetivo_od_esfera || ''}
                                                onValueChange={(value) => onChange('subjetivo_od_esfera', value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Cilindro</label>
                                            <SelectCylinder
                                                value={data.subjetivo_od_cilindro || ''}
                                                onValueChange={(value) => onChange('subjetivo_od_cilindro', value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Eje</label>
                                            <SelectAxis
                                                value={data.subjetivo_od_eje || ''}
                                                onValueChange={(value) => onChange('subjetivo_od_eje', value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Add</label>
                                            <SelectAdd
                                                value={data.subjetivo_od_add || ''}
                                                onValueChange={(value) => onChange('subjetivo_od_add', value)}
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* OI Row */}
                                <div>
                                    <h4 className="mb-2 text-xs font-medium text-gray-700">OI</h4>
                                    <div className="mb-2 grid grid-cols-4 gap-2">
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Esfera</label>
                                            <SelectSphere
                                                value={data.subjetivo_oi_esfera || ''}
                                                onValueChange={(value) => onChange('subjetivo_oi_esfera', value)}
                                                triggerClassName="h-6 text-xs"
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Cilindro</label>
                                            <SelectCylinder
                                                value={data.subjetivo_oi_cilindro || ''}
                                                onValueChange={(value) => onChange('subjetivo_oi_cilindro', value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Eje</label>
                                            <SelectAxis
                                                value={data.subjetivo_oi_eje || ''}
                                                onValueChange={(value) => onChange('subjetivo_oi_eje', value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-gray-600">Add</label>
                                            <SelectAdd
                                                value={data.subjetivo_oi_add || ''}
                                                onValueChange={(value) => onChange('subjetivo_oi_add', value)}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    {/* Observaciones Section - Full Width */}
                    <section className="rounded-lg border border-gray-200 p-4">
                        <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Observaciones de Refracción</h3>
                        <textarea
                            value={data.refraccion_observaciones || ''}
                            onChange={(e) => onChange('refraccion_observaciones', e.target.value)}
                            className="h-20 w-full resize-none rounded border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            placeholder="Observaciones adicionales..."
                        />
                    </section>
                </div>

                {/* Footer */}
                <div className="flex items-center justify-end space-x-2 border-t border-gray-200 p-4">
                    <button
                        onClick={onClose}
                        className="rounded border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    );
}
