import { Input } from "../ui/input";

interface QueratometriaPresionData {
    // Queratometría - OD
    quera_od_horizontal?: string;
    quera_od_vertical?: string;
    quera_od_eje?: string;
    quera_od_dif?: string;

    // Queratometría - OI
    quera_oi_horizontal?: string;
    quera_oi_vertical?: string;
    quera_oi_eje?: string;
    quera_oi_dif?: string;

    // Presión Intraocular - OD
    presion_od?: string;
    presion_od_hora?: string;

    // Presión Intraocular - OI
    presion_oi?: string;
    presion_oi_hora?: string;
}

interface QueratometriaPresionProps {
    data: QueratometriaPresionData;
    onChange: (field: string, value: string) => void;
    errors?: Record<string, string>;
}

export default function QueratometriaPresion({ data, onChange, errors }: QueratometriaPresionProps) {
    return (
        <div className="bg-white rounded-lg border border-gray-200 p-4">
            <h1 className="text-lg font-semibold text-gray-900 mb-4">Queratometría</h1>
            
            {/* Queratometría Section */}
            <section className="keratometry-data mb-6">
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th className="text-left py-2 px-2 font-medium text-gray-700"></th>
                                <th className="text-center py-2 px-2 font-medium text-gray-700">Horizontal</th>
                                <th className="text-center py-2 px-2 font-medium text-gray-700">Vertical</th>
                                <th className="text-center py-2 px-2 font-medium text-gray-700">Eje</th>
                                <th className="text-center py-2 px-2 font-medium text-gray-700">Dif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="border-b border-gray-100">
                                <td className="py-2 px-2 font-medium text-gray-700 eye-label">OD</td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_horizontal || ""}
                                        onChange={(e) => onChange("quera_od_horizontal", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="45,50"
                                    />
                                    {errors?.quera_od_horizontal && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_od_horizontal}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_vertical || ""}
                                        onChange={(e) => onChange("quera_od_vertical", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="42,00"
                                    />
                                    {errors?.quera_od_vertical && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_od_vertical}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_eje || ""}
                                        onChange={(e) => onChange("quera_od_eje", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="10"
                                    />
                                    {errors?.quera_od_eje && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_od_eje}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_dif || ""}
                                        onChange={(e) => onChange("quera_od_dif", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="3,50"
                                    />
                                    {errors?.quera_od_dif && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_od_dif}</p>
                                    )}
                                </td>
                            </tr>
                            <tr>
                                <td className="py-2 px-2 font-medium text-gray-700 eye-label">OI</td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_horizontal || ""}
                                        onChange={(e) => onChange("quera_oi_horizontal", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="42,50"
                                    />
                                    {errors?.quera_oi_horizontal && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_oi_horizontal}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_vertical || ""}
                                        onChange={(e) => onChange("quera_oi_vertical", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="42,00"
                                    />
                                    {errors?.quera_oi_vertical && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_oi_vertical}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_eje || ""}
                                        onChange={(e) => onChange("quera_oi_eje", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="10"
                                    />
                                    {errors?.quera_oi_eje && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_oi_eje}</p>
                                    )}
                                </td>
                                <td className="py-2 px-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_dif || ""}
                                        onChange={(e) => onChange("quera_oi_dif", e.target.value)}
                                        className="w-full h-6 px-1 text-xs text-center "
                                        placeholder="0,50"
                                    />
                                    {errors?.quera_oi_dif && (
                                        <p className="text-xs text-red-600 mt-1">{errors.quera_oi_dif}</p>
                                    )}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Presión Intraocular Section */}
            <section className="intraocular-pressure">
                <h2 className="text-sm font-medium text-gray-900 mb-3">Presión intraocular</h2>
                <div className="pressure-grid grid grid-cols-4 gap-3 text-xs">
                    {/* OD Row */}
                    <div className="grid-label font-medium text-gray-700">OD</div>
                    <div className="grid-input flex items-center space-x-1">
                        <Input
                            type="text"
                            value={data.presion_od || ""}
                            onChange={(e) => onChange("presion_od", e.target.value)}
                            className="w-full h-6 px-1 text-xs "
                            placeholder=" "
                            aria-label="Presión intraocular OD"
                        />
                        <span className="text-xs text-gray-600 whitespace-nowrap">mmHg</span>
                    </div>
                    <div className="grid-label mirass-label font-medium text-gray-700">Miras</div>
                    <div className="grid-input">
                        <Input
                            type="text"
                            value={data.presion_od_hora || ""}
                            onChange={(e) => onChange("presion_od_hora", e.target.value)}
                            className="w-full h-6 px-1 text-xs "
                            placeholder=" "
                            aria-label="Miras OD"
                        />
                        {errors?.presion_od_hora && (
                            <p className="text-xs text-red-600 mt-1">{errors.presion_od_hora}</p>
                        )}
                    </div>

                    {/* OI Row */}
                    <div className="grid-label font-medium text-gray-700">OI</div>
                    <div className="grid-input flex items-center space-x-1">
                        <Input
                            type="text"
                            value={data.presion_oi || ""}
                            onChange={(e) => onChange("presion_oi", e.target.value)}
                            className="w-full h-6 px-1 text-xs "
                            placeholder=" "
                            aria-label="Presión intraocular OI"
                        />
                        <span className="text-xs text-gray-600 whitespace-nowrap">mmHg</span>
                    </div>
                    <div className="grid-label mirass-label"></div>
                    <div className="grid-input">
                        <Input
                            type="text"
                            value={data.presion_oi_hora || ""}
                            onChange={(e) => onChange("presion_oi_hora", e.target.value)}
                            className="w-full h-6 px-1 text-xs "
                            placeholder=" "
                            aria-label="Miras OI"
                        />
                        {errors?.presion_oi_hora && (
                            <p className="text-xs text-red-600 mt-1">{errors.presion_oi_hora}</p>
                        )}
                    </div>
                </div>
                
                {/* Error messages for pressure fields */}
                {errors?.presion_od && (
                    <p className="text-xs text-red-600 mt-2">{errors.presion_od}</p>
                )}
                {errors?.presion_oi && (
                    <p className="text-xs text-red-600 mt-2">{errors.presion_oi}</p>
                )}
            </section>
        </div>
    );
}