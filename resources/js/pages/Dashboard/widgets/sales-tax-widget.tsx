import { TrendingDown, TrendingUp, X } from 'lucide-react';

interface SalesTax {
    amount: number;
    previous_amount: number;
    change_percentage: number;
}

interface SalesTaxWidgetProps {
    data: SalesTax;
    formatCurrency: (amount: number) => string;
    onRemove: () => void;
}

export function SalesTaxWidget({ data, formatCurrency, onRemove }: SalesTaxWidgetProps) {
    const isPositive = data.change_percentage > 0;
    const isNegative = data.change_percentage < 0;

    return (
        <div className="group  bg-white relative flex h-full flex-col justify-center p-4">
            <button
                onClick={(e) => {
                    e.stopPropagation();
                    onRemove();
                }}
                className="absolute top-2 right-2 z-10 rounded-full p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 dark:hover:bg-gray-700"
            >
                <X className="h-4 w-4 text-gray-500" />
            </button>
            <h3 className="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Impuestos en venta</h3>
            <div className="flex items-baseline gap-2">
                <p className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{formatCurrency(data.amount)}</p>
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
