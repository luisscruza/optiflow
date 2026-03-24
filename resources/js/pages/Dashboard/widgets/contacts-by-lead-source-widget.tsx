import { X } from 'lucide-react';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

interface ContactsByLeadSourceData {
    total: number;
    sources: Array<{
        id: number | string;
        label: string;
        count: number;
    }>;
}

interface ContactsByLeadSourceWidgetProps {
    data: ContactsByLeadSourceData;
    onRemove: () => void;
}

const COLORS = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

function formatNumber(value: number): string {
    return new Intl.NumberFormat('es-DO').format(value);
}

export function ContactsByLeadSourceWidget({ data, onRemove }: ContactsByLeadSourceWidgetProps) {
    return (
        <div className="group relative flex h-full flex-col bg-white p-4">
            <button
                onClick={(e) => {
                    e.stopPropagation();
                    onRemove();
                }}
                className="absolute top-2 right-2 z-10 rounded-full p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 dark:hover:bg-gray-700"
            >
                <X className="h-4 w-4 text-gray-500" />
            </button>

            <div className="mb-3 flex items-end justify-between gap-3 border-b border-gray-200 pb-2 dark:border-gray-700">
                <div>
                    <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300">Contactos</h3>
                    <p className="text-xs text-gray-500 dark:text-gray-400">Rango seleccionado</p>
                </div>
                <div className="text-right">
                    <p className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{formatNumber(data.total)}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">contactos</p>
                </div>
            </div>

            {data.sources.length === 0 ? (
                <div className="flex flex-1 items-center justify-center rounded-lg border border-dashed border-gray-200 text-sm text-muted-foreground dark:border-gray-700">
                    No hay contactos para mostrar.
                </div>
            ) : (
                <div className="grid min-h-0 flex-1 grid-cols-[140px_minmax(0,1fr)] gap-3">
                    <div className="min-h-[140px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={data.sources}
                                    dataKey="count"
                                    nameKey="label"
                                    innerRadius={34}
                                    outerRadius={54}
                                    paddingAngle={2}
                                    strokeWidth={0}
                                >
                                    {data.sources.map((entry, index) => (
                                        <Cell key={entry.id} fill={COLORS[index % COLORS.length]} />
                                    ))}
                                </Pie>
                                <Tooltip
                                    formatter={(value: number) => formatNumber(value)}
                                    contentStyle={{ borderRadius: '0.75rem', borderColor: 'rgb(229 231 235)' }}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>

                    <div className="min-h-0 space-y-1.5 overflow-y-auto pr-1">
                        {data.sources.map((source, index) => {
                            const percentage = data.total > 0 ? Math.round((source.count / data.total) * 100) : 0;

                            return (
                                <div key={source.id} className="rounded-md border border-gray-200 px-3 py-2 dark:border-gray-700">
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="flex min-w-0 items-center gap-2">
                                            <span
                                                className="h-2.5 w-2.5 shrink-0 rounded-full"
                                                style={{ backgroundColor: COLORS[index % COLORS.length] }}
                                            />
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{source.label}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">{percentage}%</p>
                                            </div>
                                        </div>
                                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{formatNumber(source.count)}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
