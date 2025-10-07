import SelectSphere from '../ui/select-sphere';
import SelectCylinder from '../ui/select-cylinder';
import SelectAxis from '../ui/select-axis';
import SelectAdd from '../ui/select-add';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';

interface RefraccionData {
    subjetivo_od_esfera?: string;
    subjetivo_od_cilindro?: string;
    subjetivo_od_eje?: string;
    subjetivo_od_add?: string;
    subjetivo_od_dp?: string;
    subjetivo_od_av_lejos?: string;
    subjetivo_od_av_cerca?: string;
    subjetivo_oi_esfera?: string;
    subjetivo_oi_cilindro?: string;
    subjetivo_oi_eje?: string;
    subjetivo_oi_add?: string;
    subjetivo_oi_dp?: string;
    subjetivo_oi_av_lejos?: string;
    subjetivo_oi_av_cerca?: string;
}

interface RefraccionProps {
    data: RefraccionData;
    onChange: (field: string, value: string | boolean) => void;
    errors?: Record<string, string>;
}

export default function RefraccionSubjetivo({ data, onChange, errors }: RefraccionProps) {
    return (
        <div className="border border-gray-200 rounded-lg p-4">            
            <div className="rounded-md border">
                <Table>
                    {/* Table Header */}
                    <TableHeader>
                        <TableRow>
                            <TableHead 
                                colSpan={8} 
                                className=" text-center text-sm font-semibold text-gray-900 py-3"
                            >
                                SUBJETIVO
                            </TableHead>
                        </TableRow>
                        <TableRow>
                            <TableHead 
                                rowSpan={2} 
                                className="bg-gray-50 text-center text-xs font-medium text-gray-700 w-16"
                            >
                                {/* Empty header for eye designation */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">
                                Esfera
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">
                                Cilindro
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">
                                Eje
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">
                                ADD
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-700">
                                DP
                            </TableHead>
                            <TableHead 
                                colSpan={2} 
                                className="bg-gray-50 text-center text-xs font-medium text-gray-700"
                            >
                                AGUDEZA VISUAL
                            </TableHead>
                        </TableRow>
                        <TableRow>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                                {/* Esfera subheader */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                                {/* Cilindro subheader */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                                {/* Eje subheader */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                                {/* ADD subheader */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                                {/* DP subheader */}
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                              Lejos
                            </TableHead>
                            <TableHead className="bg-gray-50 text-center text-xs font-medium text-gray-600">
                                Cerca
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    
                    {/* Table Body */}
                    <TableBody>
                        {/* OD Row */}
                        <TableRow className="border-b border-gray-100">
                            <TableCell className="text-center text-xs font-medium text-gray-700 bg-gray-50">
                                OD
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectSphere
                                    value={data.subjetivo_od_esfera || ""}
                                    onValueChange={(value) => onChange("subjetivo_od_esfera", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectCylinder
                                    value={data.subjetivo_od_cilindro || ""}
                                    onValueChange={(value) => onChange("subjetivo_od_cilindro", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAxis
                                    value={data.subjetivo_od_eje || ""}
                                    onValueChange={(value) => onChange("subjetivo_od_eje", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAdd
                                    value={data.subjetivo_od_add || ""}
                                    onValueChange={(value) => onChange("subjetivo_od_add", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <input
                                    type="text"
                                    value={data.subjetivo_od_dp || ""}
                                    onChange={(e) => onChange("subjetivo_od_dp", e.target.value)}
                                    className="w-full h-8 px-2 text-xs text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="text-xs text-gray-500 mr-1">20/</span>
                                    <input
                                        type="text"
                                        value={data.subjetivo_od_av_lejos || ""}
                                        onChange={(e) => onChange("subjetivo_od_av_lejos", e.target.value)}
                                        className="flex-1 h-8 px-2 text-xs text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="text-xs text-gray-500 mr-1">20/</span>
                                    <input
                                        type="text"
                                        value={data.subjetivo_od_av_cerca || ""}
                                        onChange={(e) => onChange("subjetivo_od_av_cerca", e.target.value)}
                                        className="flex-1 h-8 px-2 text-xs text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                        
                        {/* OI Row */}
                        <TableRow>
                            <TableCell className="text-center text-xs font-medium text-gray-700 bg-gray-50">
                                OI
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectSphere
                                    value={data.subjetivo_oi_esfera || ""}
                                    onValueChange={(value) => onChange("subjetivo_oi_esfera", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectCylinder
                                    value={data.subjetivo_oi_cilindro || ""}
                                    onValueChange={(value) => onChange("subjetivo_oi_cilindro", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAxis
                                    value={data.subjetivo_oi_eje || ""}
                                    onValueChange={(value) => onChange("subjetivo_oi_eje", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <SelectAdd
                                    value={data.subjetivo_oi_add || ""}
                                    onValueChange={(value) => onChange("subjetivo_oi_add", value)}
                                    triggerClassName="w-full h-8 text-xs"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <input
                                    type="text"
                                    value={data.subjetivo_oi_dp || ""}
                                    onChange={(e) => onChange("subjetivo_oi_dp", e.target.value)}
                                    className="w-full h-8 px-2 text-xs text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="text-xs text-gray-500 mr-1">20/</span>
                                    <input
                                        type="text"
                                        value={data.subjetivo_oi_av_lejos || ""}
                                        onChange={(e) => onChange("subjetivo_oi_av_lejos", e.target.value)}
                                        className="flex-1 h-8 px-2 text-xs text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </TableCell>
                            <TableCell className="p-2">
                                <div className="flex items-center">
                                    <span className="text-xs text-gray-500 mr-1">20/</span>
                                    <input
                                        type="text"
                                        value={data.subjetivo_oi_av_cerca || ""}
                                        onChange={(e) => onChange("subjetivo_oi_av_cerca", e.target.value)}
                                        className="flex-1 h-8 px-2 text-xs text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
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