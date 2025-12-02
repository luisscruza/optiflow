import SelectSphere from '../ui/select-sphere';
import SelectCylinder from '../ui/select-cylinder';
import SelectAxis from '../ui/select-axis';
import SelectAdd from '../ui/select-add';

interface RefraccionData {
    // Refracción - OD
    refraccion_od_esfera?: string;
    refraccion_od_cilindro?: string;
    refraccion_od_eje?: string;
    refraccion_subjetivo_od_adicion?: string; // Using subjetivo adicion for the table

    // Refracción - OI
    refraccion_oi_esfera?: string;
    refraccion_oi_cilindro?: string;
    refraccion_oi_eje?: string;
    refraccion_subjetivo_oi_adicion?: string; // Using subjetivo adicion for the table

    // Retinoscopía tipo
    retinoscopia_dinamica?: boolean;
    retinoscopia_estatica?: boolean;

    // Observaciones
    refraccion_observaciones?: string;
}

interface RefraccionProps {
    data: RefraccionData;
    onChange: (field: string, value: string | boolean) => void;
    errors?: Record<string, string>;
}

export default function Refraccion({ data, onChange, errors }: RefraccionProps) {
    return (
        <div className="bg-white rounded-lg border border-gray-200 p-4">
            <h1 className="text-lg font-semibold text-gray-900 mb-4">Refracción</h1>
            
            {/* Refracción Table */}
            <section className="refraction-data mb-4">
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
                                <td className="py-2 px-2 font-medium text-gray-700 eye-label">OD</td>
                                <td className="py-2 px-2">
                                    <SelectSphere
                                        value={data.refraccion_od_esfera || ""}
                                        onValueChange={(value) => onChange("refraccion_od_esfera", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_od_esfera && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_od_esfera}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <SelectCylinder
                                        value={data.refraccion_od_cilindro || ""}
                                        onValueChange={(value) => onChange("refraccion_od_cilindro", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_od_cilindro && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_od_cilindro}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <SelectAxis
                                        value={data.refraccion_od_eje || ""}
                                        onValueChange={(value) => onChange("refraccion_od_eje", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_od_eje && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_od_eje}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <SelectAdd
                                        value={data.refraccion_subjetivo_od_adicion || ""}
                                        onValueChange={(value) => onChange("refraccion_subjetivo_od_adicion", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_subjetivo_od_adicion && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_subjetivo_od_adicion}</p>
                                    )}
                                </td>
                            </tr>
                            <tr>
                                <td className="py-2 px-2 font-medium text-gray-700 eye-label">OI</td>
                                <td className="py-2 px-2">
                                    <SelectSphere
                                        value={data.refraccion_oi_esfera || ""}
                                        onValueChange={(value) => onChange("refraccion_oi_esfera", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_oi_esfera && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_oi_esfera}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <SelectCylinder
                                        value={data.refraccion_oi_cilindro || ""}
                                        onValueChange={(value) => onChange("refraccion_oi_cilindro", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_oi_cilindro && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_oi_cilindro}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <SelectAxis
                                        value={data.refraccion_oi_eje || ""}
                                        onValueChange={(value) => onChange("refraccion_oi_eje", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_oi_eje && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_oi_eje}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <SelectAdd
                                        value={data.refraccion_subjetivo_oi_adicion || ""}
                                        onValueChange={(value) => onChange("refraccion_subjetivo_oi_adicion", value)}
                                        placeholder=""
                                    />
                                    {errors?.refraccion_subjetivo_oi_adicion && (
                                        <p className="text-xs text-red-600 mt-1">{errors.refraccion_subjetivo_oi_adicion}</p>
                                    )}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Bottom Section with Checkboxes and Reflejos */}
            <section className="bottom-section">
                <div className="grid grid-cols-2 gap-4 text-xs">
                    {/* Left side - Checkboxes */}
                    <div className="space-y-2">
                        <div className="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="retinoscopia_dinamica"
                                checked={data.retinoscopia_dinamica || false}
                                onChange={(e) => onChange("retinoscopia_dinamica", e.target.checked)}
                                className="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label htmlFor="retinoscopia_dinamica" className="font-medium text-gray-700">
                                Dinámica
                            </label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="retinoscopia_estatica"
                                checked={data.retinoscopia_estatica || false}
                                onChange={(e) => onChange("retinoscopia_estatica", e.target.checked)}
                                className="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label htmlFor="retinoscopia_estatica" className="font-medium text-gray-700">
                                Estática
                            </label>
                        </div>
                    </div>

                    {/* Right side - Reflejos */}
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                            Observaciones:
                        </label>
                        <textarea
                            value={data.refraccion_observaciones || ""}
                            onChange={(e) => onChange("refraccion_observaciones", e.target.value)}
                            className="w-full h-16 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder=""
                        />
                        {errors?.refraccion_observaciones && (
                            <p className="text-xs text-red-600 mt-1">{errors.refraccion_observaciones}</p>
                        )}
                    </div>
                </div>
            </section>
        </div>
    );
}