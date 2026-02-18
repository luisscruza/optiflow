import type { ChangeEvent } from 'react';

import SelectAdd from '../ui/select-add';
import SelectAxis from '../ui/select-axis';
import SelectCylinder from '../ui/select-cylinder';
import SelectSphere from '../ui/select-sphere';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';

interface RefraccionData {
    subjetivo_od_esfera?: string;
    subjetivo_od_cilindro?: string;
    subjetivo_od_eje?: string;
    subjetivo_od_add?: string;
    subjetivo_od_dp?: string;
    alt_bif_od?: string;
    subjetivo_od_av_lejos?: string;
    subjetivo_od_av_cerca?: string;
    subjetivo_oi_esfera?: string;
    subjetivo_oi_cilindro?: string;
    subjetivo_oi_eje?: string;
    subjetivo_oi_add?: string;
    subjetivo_oi_dp?: string;
    alt_bif_oi?: string;
    subjetivo_oi_av_lejos?: string;
    subjetivo_oi_av_cerca?: string;
}

interface RefraccionProps {
    data: RefraccionData;
    onChange: (field: string, value: string | boolean) => void;
    errors?: Record<string, string>;
}

export default function RefraccionSubjetivo({ data, onChange, errors }: RefraccionProps) {
    const handleTwoDigitChange = (field: string) => (event: ChangeEvent<HTMLInputElement>) => {
        const value = event.target.value.replace(/\D/g, '').slice(0, 2);
        onChange(field, value);
    };

    return (
        <div className="rounded-lg border border-gray-200 p-4">
            <div className="rounded-md border">
                <Table>
                    {/* Table Header */}
                    <TableHeader>
                        <TableRow>
                            <TableHead colSpan={9} className="py-3 text-center text-sm font-semibold text-gray-900">
                                Rx Prescripción Final
                            </TableHead>
                        </TableRow>
                        <TableRow>
                            <TableHead rowSpan={2} className="w-16 bg-gray-50 text-center text-xs font-medium text-gray-700">
                                {/* Empty header for eye designation */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">Esfera</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">Cilindro</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">Eje</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">ADD</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">DNP</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">ALT BIF</TableHead>
                            <TableHead colSpan={2} className="bg-gray-50 text-center text-xs font-medium text-gray-700">
                                AGUDEZA VISUAL
                            </TableHead>
                        </TableRow>
                        <TableRow>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">{/* Esfera subheader */}</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">{/* Cilindro subheader */}</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">{/* Eje subheader */}</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">{/* ADD subheader */}</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">{/* DP subheader */}</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">{/* Alt bif subheader */}</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">Lejos</TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">Cerca</TableHead>
                        </TableRow>
                    </TableHeader>

                    {/* Table Body */}
                    <TableBody>
                        {/* OD Row */}
                        <TableRow className="border-b border-gray-100">
                            <TableCell className="bg-gray-50 text-center text-xs font-medium text-gray-700">OD</TableCell>
                            <TableCell className="p-2">
                                <SelectSphere
                                    value={data.subjetivo_od_esfera || ''}
                                    onValueChange={(value) => onChange('subjetivo_od_esfera', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectCylinder
                                    value={data.subjetivo_od_cilindro || ''}
                                    onValueChange={(value) => onChange('subjetivo_od_cilindro', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAxis
                                    value={data.subjetivo_od_eje || ''}
                                    onValueChange={(value) => onChange('subjetivo_od_eje', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAdd
                                    value={data.subjetivo_od_add || ''}
                                    onValueChange={(value) => onChange('subjetivo_od_add', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <input
                                    type="text"
                                    value={data.subjetivo_od_dp || ''}
                                    onChange={(e) => onChange('subjetivo_od_dp', e.target.value)}
                                    className="h-8 w-full rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <input
                                    type="text"
                                    value={data.alt_bif_od || ''}
                                    onChange={(e) => onChange('alt_bif_od', e.target.value)}
                                    className="h-8 w-full rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="mr-1 text-xs text-gray-500">20/</span>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={2}
                                        value={data.subjetivo_od_av_lejos || ''}
                                        onChange={handleTwoDigitChange('subjetivo_od_av_lejos')}
                                        className="h-8 flex-1 rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="mr-1 text-xs text-gray-500">20/</span>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={2}
                                        value={data.subjetivo_od_av_cerca || ''}
                                        onChange={handleTwoDigitChange('subjetivo_od_av_cerca')}
                                        className="h-8 flex-1 rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>

                        {/* OI Row */}
                        <TableRow>
                            <TableCell className="bg-gray-50 text-center text-xs font-medium text-gray-700">OI</TableCell>
                            <TableCell className="p-2">
                                <SelectSphere
                                    value={data.subjetivo_oi_esfera || ''}
                                    onValueChange={(value) => onChange('subjetivo_oi_esfera', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectCylinder
                                    value={data.subjetivo_oi_cilindro || ''}
                                    onValueChange={(value) => onChange('subjetivo_oi_cilindro', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAxis
                                    value={data.subjetivo_oi_eje || ''}
                                    onValueChange={(value) => onChange('subjetivo_oi_eje', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAdd
                                    value={data.subjetivo_oi_add || ''}
                                    onValueChange={(value) => onChange('subjetivo_oi_add', value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <input
                                    type="text"
                                    value={data.subjetivo_oi_dp || ''}
                                    onChange={(e) => onChange('subjetivo_oi_dp', e.target.value)}
                                    className="h-8 w-full rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <input
                                    type="text"
                                    value={data.alt_bif_oi || ''}
                                    onChange={(e) => onChange('alt_bif_oi', e.target.value)}
                                    className="h-8 w-full rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="mr-1 text-xs text-gray-500">20/</span>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={2}
                                        value={data.subjetivo_oi_av_lejos || ''}
                                        onChange={handleTwoDigitChange('subjetivo_oi_av_lejos')}
                                        className="h-8 flex-1 rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="mr-1 text-xs text-gray-500">20/</span>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={2}
                                        value={data.subjetivo_oi_av_cerca || ''}
                                        onChange={handleTwoDigitChange('subjetivo_oi_av_cerca')}
                                        className="h-8 flex-1 rounded border border-gray-300 px-2 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
