import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface LensometriaAgudezaProps {
    data: {
        // Lensometría
        lensometria_od?: string;
        lensometria_oi?: string;
        lensometria_add?: string;

        // Agudeza Visual - Lejana
        av_lejos_sc_od?: string;
        av_lejos_sc_oi?: string;
        av_lejos_cc_od?: string;
        av_lejos_cc_oi?: string;
        av_lejos_ph_od?: string;
        av_lejos_ph_oi?: string;

        // Agudeza Visual - Cercana
        av_cerca_sc_od?: string;
        av_cerca_sc_oi?: string;
        av_cerca_cc_od?: string;
        av_cerca_cc_oi?: string;
        av_cerca_ph_od?: string;
        av_cerca_ph_oi?: string;

        // Observaciones
        observaciones?: string;
    };
    onChange: (field: string, value: string) => void;
    errors?: Record<string, string>;
}

export default function LensometriaAgudeza({ data, onChange, errors }: LensometriaAgudezaProps) {
    return (
        <div className="space-y-4">
            {/* Lensometría Section */}
            <div className="rounded-lg border border-gray-200 p-3">
                <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Lensometría</h3>
                <div className="grid grid-cols-3 gap-2">
                    {/* OD */}
                    <div className="space-y-1">
                        <Label className="block text-center text-xs font-medium text-gray-700">OD</Label>
                        <Input
                            value={data.lensometria_od || ''}
                            onChange={(e) => onChange('lensometria_od', e.target.value)}
                            className="h-8 border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    {/* OI */}
                    <div className="space-y-1">
                        <Label className="block text-center text-xs font-medium text-gray-700">OI</Label>
                        <Input
                            value={data.lensometria_oi || ''}
                            onChange={(e) => onChange('lensometria_oi', e.target.value)}
                            className="h-8 border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    {/* ADD */}
                    <div className="space-y-1">
                        <Label className="block text-center text-xs font-medium text-gray-700">ADD</Label>
                        <Input
                            value={data.lensometria_add || ''}
                            onChange={(e) => onChange('lensometria_add', e.target.value)}
                            placeholder=""
                            className="h-8 border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>

            {/* Agudeza Visual Section */}
            <div className="rounded-lg border border-gray-200 p-3">
                <h3 className="mb-3 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900">Agudeza visual</h3>

                <div className="grid grid-cols-2 gap-3">
                    {/* Lejana */}
                    <div className="space-y-2">
                        <h4 className="border-b border-gray-300 pb-1 text-center text-xs font-medium text-gray-800">Lejana</h4>

                        {/* Headers */}
                        <div className="grid grid-cols-4 gap-1 text-xs font-medium text-gray-600">
                            <div className="text-center"></div>
                            <div className="text-center">SC</div>
                            <div className="text-center">CC</div>
                            <div className="text-center">PH</div>
                        </div>

                        {/* OD Row */}
                        <div className="grid grid-cols-4 items-center gap-1">
                            <div className="text-center text-xs font-medium text-gray-600">20/</div>
                            <Input
                                value={data.av_lejos_sc_od || ''}
                                onChange={(e) => onChange('av_lejos_sc_od', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_lejos_cc_od || ''}
                                onChange={(e) => onChange('av_lejos_cc_od', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_lejos_ph_od || ''}
                                onChange={(e) => onChange('av_lejos_ph_od', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        {/* OI Row */}
                        <div className="grid grid-cols-4 items-center gap-1">
                            <div className="text-center text-xs font-medium text-gray-600">20/</div>
                            <Input
                                value={data.av_lejos_sc_oi || ''}
                                onChange={(e) => onChange('av_lejos_sc_oi', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_lejos_cc_oi || ''}
                                onChange={(e) => onChange('av_lejos_cc_oi', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_lejos_ph_oi || ''}
                                onChange={(e) => onChange('av_lejos_ph_oi', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    {/* Cercana */}
                    <div className="space-y-2">
                        <h4 className="border-b border-gray-300 pb-1 text-center text-xs font-medium text-gray-800">Cercana</h4>

                        {/* Headers */}
                        <div className="grid grid-cols-4 gap-1 text-xs font-medium text-gray-600">
                            <div className="text-center"></div>
                            <div className="text-center">SC</div>
                            <div className="text-center">CC</div>
                            <div className="text-center">PH</div>
                        </div>

                        {/* OD Row */}
                        <div className="grid grid-cols-4 items-center gap-1">
                            <div className="text-center text-xs font-medium text-gray-600">20/</div>
                            <Input
                                value={data.av_cerca_sc_od || ''}
                                onChange={(e) => onChange('av_cerca_sc_od', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_cerca_cc_od || ''}
                                onChange={(e) => onChange('av_cerca_cc_od', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_cerca_ph_od || ''}
                                onChange={(e) => onChange('av_cerca_ph_od', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        {/* OI Row */}
                        <div className="grid grid-cols-4 items-center gap-1">
                            <div className="text-center text-xs font-medium text-gray-600">20/</div>
                            <Input
                                value={data.av_cerca_sc_oi || ''}
                                onChange={(e) => onChange('av_cerca_sc_oi', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_cerca_cc_oi || ''}
                                onChange={(e) => onChange('av_cerca_cc_oi', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <Input
                                value={data.av_cerca_ph_oi || ''}
                                onChange={(e) => onChange('av_cerca_ph_oi', e.target.value)}
                                maxLength={2}
                                className="h-6 min-w-[2.5rem] border-gray-300 text-center text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Observaciones */}
            <div className="rounded-lg border border-gray-200 p-3">
                <h3 className="mb-2 border-b border-gray-200 pb-1 text-sm font-semibold text-gray-900">Observaciones</h3>
                <textarea
                    value={data.observaciones || ''}
                    onChange={(e) => onChange('observaciones', e.target.value)}
                    className="min-h-[40px] w-full resize-none rounded-md border-gray-300 p-2 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    rows={2}
                    placeholder="Observaciones adicionales..."
                />
            </div>
        </div>
    );
}
