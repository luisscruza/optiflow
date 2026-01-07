import { X } from 'lucide-react';

interface AccountsReceivable {
    current: {
        amount: number;
        count: number;
    };
    overdue: {
        amount: number;
        count: number;
    };
    total: {
        amount: number;
        count: number;
    };
}

interface AccountsReceivableWidgetProps {
    data: AccountsReceivable;
    formatCurrency: (amount: number) => string;
    onRemove: () => void;
}

function formatNumber(num: number): string {
    return new Intl.NumberFormat('es-DO').format(num);
}

export function AccountsReceivableWidget({ data, formatCurrency, onRemove }: AccountsReceivableWidgetProps) {
    const total = data.total.amount;
    const currentPercent = total > 0 ? (data.current.amount / total) * 100 : 0;
    const overduePercent = total > 0 ? (data.overdue.amount / total) * 100 : 0;

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
            <h3 className="mb-1 border-b-2 border-gray-300 pb-1 text-lg font-medium text-gray-600 dark:border-gray-600 dark:text-gray-400">
                Cuentas por cobrar
            </h3>
            <p className="mb-3 text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{formatCurrency(total)}</p>
            <div className="mb-4 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                {currentPercent > 0 && <div className="h-full bg-green-500" style={{ width: `${currentPercent}%` }} />}
                {overduePercent > 0 && <div className="h-full bg-red-400" style={{ width: `${overduePercent}%` }} />}
            </div>
            <div className="grid flex-1 grid-cols-2 gap-3">
                <div className="flex gap-2">
                    <div className="w-1 rounded-full bg-green-500" />
                    <div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">Vigentes</p>
                        <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">{formatCurrency(data.current.amount)}</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">{formatNumber(data.current.count)} documentos</p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <div className="w-1 rounded-full bg-red-400" />
                    <div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">Vencidas</p>
                        <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">{formatCurrency(data.overdue.amount)}</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">{formatNumber(data.overdue.count)} documentos</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
