import { Input } from '../ui/input';

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
        <div className="rounded-lg border border-gray-200 bg-white p-4">
            <h1 className="mb-4 text-lg font-semibold text-gray-900">Queratometría/PIO</h1>

            {/* Queratometría Section */}
            <section className="keratometry-data mb-6">
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th className="px-2 py-2 text-left font-medium text-gray-700"></th>
                                <th className="px-2 py-2 text-center font-medium text-gray-700">D</th>
                                <th className="px-2 py-2 text-center font-medium text-gray-700">MM</th>
                                <th className="px-2 py-2 text-center font-medium text-gray-700">A</th>
                                <th className="px-2 py-2 text-center font-medium text-gray-700">CYL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="border-b border-gray-100">
                                <td className="eye-label px-2 py-2 font-medium text-gray-700">OD</td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_horizontal || ''}
                                        onChange={(e) => onChange('quera_od_horizontal', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="45,50"
                                    />
                                    {errors?.quera_od_horizontal && <p className="mt-1 text-xs text-red-600">{errors.quera_od_horizontal}</p>}
                                </td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_vertical || ''}
                                        onChange={(e) => onChange('quera_od_vertical', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="42,00"
                                    />
                                    {errors?.quera_od_vertical && <p className="mt-1 text-xs text-red-600">{errors.quera_od_vertical}</p>}
                                </td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_eje || ''}
                                        onChange={(e) => onChange('quera_od_eje', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="10"
                                    />
                                    {errors?.quera_od_eje && <p className="mt-1 text-xs text-red-600">{errors.quera_od_eje}</p>}
                                </td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_od_dif || ''}
                                        onChange={(e) => onChange('quera_od_dif', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="3,50"
                                    />
                                    {errors?.quera_od_dif && <p className="mt-1 text-xs text-red-600">{errors.quera_od_dif}</p>}
                                </td>
                            </tr>
                            <tr>
                                <td className="eye-label px-2 py-2 font-medium text-gray-700">OI</td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_horizontal || ''}
                                        onChange={(e) => onChange('quera_oi_horizontal', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="42,50"
                                    />
                                    {errors?.quera_oi_horizontal && <p className="mt-1 text-xs text-red-600">{errors.quera_oi_horizontal}</p>}
                                </td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_vertical || ''}
                                        onChange={(e) => onChange('quera_oi_vertical', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="42,00"
                                    />
                                    {errors?.quera_oi_vertical && <p className="mt-1 text-xs text-red-600">{errors.quera_oi_vertical}</p>}
                                </td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_eje || ''}
                                        onChange={(e) => onChange('quera_oi_eje', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="10"
                                    />
                                    {errors?.quera_oi_eje && <p className="mt-1 text-xs text-red-600">{errors.quera_oi_eje}</p>}
                                </td>
                                <td className="px-2 py-2">
                                    <Input
                                        type="text"
                                        value={data.quera_oi_dif || ''}
                                        onChange={(e) => onChange('quera_oi_dif', e.target.value)}
                                        className="h-6 w-full px-1 text-center text-xs"
                                        placeholder="0,50"
                                    />
                                    {errors?.quera_oi_dif && <p className="mt-1 text-xs text-red-600">{errors.quera_oi_dif}</p>}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Presión Intraocular Section */}
            <section className="intraocular-pressure">
                <h2 className="mb-3 text-sm font-medium text-gray-900">Presión intraocular</h2>
                <div className="pressure-grid grid grid-cols-2 gap-3 text-xs">
                    {/* OD Row */}
                    <div className="grid-label font-medium text-gray-700">OD</div>
                    <div className="grid-input flex items-center space-x-1">
                        <Input
                            type="text"
                            value={data.presion_od || ''}
                            onChange={(e) => onChange('presion_od', e.target.value)}
                            className="h-6 w-full px-1 text-xs"
                            placeholder=" "
                            aria-label="Presión intraocular OD"
                        />
                        <span className="text-xs whitespace-nowrap text-gray-600">mmHg</span>
                    </div>

                    {/* OI Row */}
                    <div className="grid-label font-medium text-gray-700">OI</div>
                    <div className="grid-input flex items-center space-x-1">
                        <Input
                            type="text"
                            value={data.presion_oi || ''}
                            onChange={(e) => onChange('presion_oi', e.target.value)}
                            className="h-6 w-full px-1 text-xs"
                            placeholder=" "
                            aria-label="Presión intraocular OI"
                        />
                        <span className="text-xs whitespace-nowrap text-gray-600">mmHg</span>
                    </div>
                </div>

                {/* Error messages for pressure fields */}
                {errors?.presion_od && <p className="mt-2 text-xs text-red-600">{errors.presion_od}</p>}
                {errors?.presion_oi && <p className="mt-2 text-xs text-red-600">{errors.presion_oi}</p>}
            </section>
        </div>
    );
}
