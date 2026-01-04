import { TrendingDown, TrendingUp, X } from 'lucide-react';

interface CountStat {
    count: number;
    previous_count: number;
    change_percentage: number;
}

interface CountStatWidgetProps {
    title: string;
    data: CountStat;
    icon: React.ElementType;
    onRemove: () => void;
}

function formatNumber(num: number): string {
    return new Intl.NumberFormat('es-DO').format(num);
}

export function CountStatWidget({ title, data, icon: Icon, onRemove }: CountStatWidgetProps) {
    const isPositive = data.change_percentage > 0;
    const isNegative = data.change_percentage < 0;

    return (
        <div className="group relative flex h-full flex-col justify-center p-4">
            <button
                onClick={(e) => {
                    e.stopPropagation();
                    onRemove();
                }}
                className="absolute top-2 right-2 z-10 rounded-full p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 dark:hover:bg-gray-700"
            >
                <X className="h-4 w-4 text-gray-500" />
            </button>
            <div className="flex items-center gap-2">
                <Icon className="h-4 w-4 text-gray-500" />
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">{title}</h3>
            </div>
            <div className="mt-2 flex items-baseline gap-2">
                <p className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{formatNumber(data.count)}</p>
                {data.change_percentage !== 0 && (
                    <div className={`flex items-center gap-0.5 ${isPositive ? 'text-green-600' : isNegative ? 'text-red-500' : ''}`}>
                        {isPositive ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
                        <span className="text-sm font-medium">
                            {isPositive ? '+' : ''}
                            {data.change_percentage}%
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}
