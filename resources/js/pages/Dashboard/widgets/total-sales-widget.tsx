import { Info, TrendingDown, TrendingUp, X } from 'lucide-react';
import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { TooltipContent, TooltipProvider, TooltipTrigger, Tooltip as TooltipUI } from '@/components/ui/tooltip';

interface TotalSalesData {
    total: number;
    previous_total: number;
    change_percentage: number;
    current_period: {
        start: string;
        end: string;
    };
    previous_period: {
        start: string;
        end: string;
    };
    daily_data: Array<{
        date: string;
        day: number;
        current: number;
        previous: number;
    }>;
}

interface TotalSalesWidgetProps {
    data: TotalSalesData;
    formatCurrency: (value: number) => string;
    onRemove: () => void;
}

export function TotalSalesWidget({ data, formatCurrency, onRemove }: TotalSalesWidgetProps) {
    const isPositive = data.change_percentage >= 0;

    // Format currency for Y axis (abbreviated)
    const formatYAxis = (value: number) => {
        if (value >= 1000000) {
            return `${(value / 1000000).toFixed(1)}M`;
        }
        if (value >= 1000) {
            return `${(value / 1000).toFixed(0)}K`;
        }
        return value.toString();
    };

    // Custom tooltip component
    const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: Array<{ value: number; dataKey: string }>; label?: string }) => {
        if (active && payload && payload.length) {
            return (
                <div className="rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <p className="mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">{label}</p>
                    {payload.map((entry, index) => (
                        <div key={index} className="flex items-center gap-2 text-sm">
                            <div className={`h-2 w-2 rounded-full ${entry.dataKey === 'current' ? 'bg-blue-600' : 'bg-yellow-500'}`} />
                            <span className="text-gray-600 dark:text-gray-400">
                                {entry.dataKey === 'current' ? 'Período actual' : 'Período anterior'}:
                            </span>
                            <span className="font-medium text-gray-900 dark:text-gray-100">{formatCurrency(entry.value)}</span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    return (
        <div className="group relative flex h-full flex-col bg-white p-4">
            {/* Remove button */}
            <button
                onClick={onRemove}
                className="absolute top-2 right-2 z-10 rounded-full p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 dark:hover:bg-gray-700"
            >
                <X className="h-4 w-4 text-gray-500" />
            </button>

            {/* Header */}
            <div className="mb-4 flex items-start justify-between">
                <div>
                    <div className="mb-1 flex items-center gap-2">
                        <h3 className="border-b-2 border-gray-300 pb-0.5 text-base font-medium text-gray-900 dark:border-gray-600 dark:text-gray-100">
                            Total de ventas
                        </h3>
                        <TooltipProvider>
                            <TooltipUI>
                                <TooltipTrigger>
                                    <Info className="h-4 w-4 text-gray-400" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>La gráfica muestra el valor de tus ventas con impuestos incluidos.</p>
                                </TooltipContent>
                            </TooltipUI>
                        </TooltipProvider>
                    </div>
                    <p className="text-sm text-gray-500 dark:text-gray-400">La gráfica muestra el valor de tus ventas con impuestos incluidos.</p>
                </div>

                <div className="text-right">
                    <p className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{formatCurrency(data.total)}</p>
                    <div className={`flex items-center justify-end gap-1 ${isPositive ? 'text-green-600' : 'text-red-500'}`}>
                        {isPositive ? <TrendingUp className="h-4 w-4" /> : <TrendingDown className="h-4 w-4" />}
                        <span className="text-sm font-medium">
                            {isPositive ? '+' : ''}
                            {data.change_percentage}%
                        </span>
                    </div>
                </div>
            </div>

            {/* Chart */}
            <div className="min-h-0 flex-1">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={data.daily_data} margin={{ top: 5, right: 10, left: 10, bottom: 5 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                        <XAxis
                            dataKey="date"
                            tick={{ fontSize: 11, fill: 'hsl(var(--muted-foreground))' }}
                            tickLine={false}
                            axisLine={{ stroke: 'hsl(var(--border))' }}
                            interval="preserveStartEnd"
                        />
                        <YAxis
                            tickFormatter={formatYAxis}
                            tick={{ fontSize: 11, fill: 'hsl(var(--muted-foreground))' }}
                            tickLine={false}
                            axisLine={false}
                            width={60}
                        />
                        <Tooltip content={<CustomTooltip />} />
                        <Legend
                            verticalAlign="bottom"
                            height={36}
                            formatter={(value) => (
                                <span className="text-xs text-gray-600 dark:text-gray-400">
                                    {value === 'current'
                                        ? `${data.current_period.start} - ${data.current_period.end}`
                                        : `${data.previous_period.start} - ${data.previous_period.end}`}
                                </span>
                            )}
                        />
                        <Line
                            type="monotone"
                            dataKey="current"
                            name="current"
                            stroke="#2563eb"
                            strokeWidth={2}
                            dot={false}
                            activeDot={{ r: 4, strokeWidth: 0 }}
                        />
                        <Line
                            type="monotone"
                            dataKey="previous"
                            name="previous"
                            stroke="#10b981"
                            strokeWidth={2}
                            strokeDasharray="5 5"
                            dot={false}
                            activeDot={{ r: 4, strokeWidth: 0 }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
