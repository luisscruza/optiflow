import { store as storeDashboardLayout } from '@/actions/App/Http/Controllers/DashboardLayoutController';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { useCurrency } from '@/utils/currency';
import { Head, router } from '@inertiajs/react';
import { GridStack, type GridStackNode } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';
import { FileText, LayoutGrid, Package, Users } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AccountsReceivableWidget, CountStatWidget, SalesTaxWidget } from './widgets';

type DateRangePreset = 'current_month' | 'last_3_months' | 'last_6_months';

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

interface SalesTax {
    amount: number;
    previous_amount: number;
    change_percentage: number;
}

interface CountStat {
    count: number;
    previous_count: number;
    change_percentage: number;
}

interface WidgetLayout {
    [key: string]: string | number | undefined;
    id: string;
    x: number;
    y: number;
    w: number;
    h: number;
    minW?: number;
    minH?: number;
}

interface DashboardProps {
    filters: {
        range: DateRangePreset;
    };
    accountsReceivable: AccountsReceivable;
    salesTax: SalesTax;
    productsSold: CountStat;
    customersWithSales: CountStat;
    prescriptionsCreated: CountStat;
    dashboardLayout: WidgetLayout[];
    availableWidgets: Record<string, string>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tablero',
        href: dashboard().url,
    },
];

const rangeLabels: Record<DateRangePreset, string> = {
    current_month: 'Mes actual',
    last_3_months: 'Últimos 3 meses',
    last_6_months: 'Últimos 6 meses',
};

// Default layouts for each widget (used when adding widgets back)
const DEFAULT_WIDGET_LAYOUTS: Record<string, { w: number; h: number; minW: number; minH: number }> = {
    'accounts-receivable': { w: 5, h: 3, minW: 4, minH: 3 },
    'sales-tax': { w: 3, h: 2, minW: 2, minH: 1 },
    'products-sold': { w: 2, h: 2, minW: 2, minH: 1 },
    'customers-with-sales': { w: 2, h: 2, minW: 2, minH: 1 },
    'prescriptions-created': { w: 2, h: 2, minW: 2, minH: 1 },
};

export default function DashboardIndex({
    filters,
    accountsReceivable,
    salesTax,
    productsSold,
    customersWithSales,
    prescriptionsCreated,
    dashboardLayout,
    availableWidgets,
}: DashboardProps) {
    const { format: formatCurrency } = useCurrency();
    const [isLoading, setIsLoading] = useState(false);
    const [layout, setLayout] = useState<WidgetLayout[]>(dashboardLayout);
    const gridRef = useRef<HTMLDivElement>(null);
    const gridInstanceRef = useRef<GridStack | null>(null);
    const saveTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isInitializedRef = useRef(false);

    // Get visible widget IDs
    const visibleWidgetIds = layout.map((w) => w.id);

    // Save layout to backend (debounced)
    const saveLayout = useCallback((newLayout: WidgetLayout[]) => {
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }

        saveTimeoutRef.current = setTimeout(() => {
            router.post(
                storeDashboardLayout().url,
                { layout: newLayout },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        }, 500);
    }, []);

    // Handle GridStack change event
    const handleGridChange = useCallback(() => {
        if (!gridInstanceRef.current || !isInitializedRef.current) return;

        const items = gridInstanceRef.current.getGridItems();
        const newLayout: WidgetLayout[] = items
            .map((el) => {
                const node = el.gridstackNode as GridStackNode | undefined;
                if (!node || !node.id) return null;
                return {
                    id: node.id as string,
                    x: node.x ?? 0,
                    y: node.y ?? 0,
                    w: node.w ?? 1,
                    h: node.h ?? 1,
                };
            })
            .filter((item): item is WidgetLayout => item !== null);

        setLayout(newLayout);
        saveLayout(newLayout);
    }, [saveLayout]);

    // Remove widget
    const removeWidget = useCallback(
        (widgetId: string) => {
            if (!gridInstanceRef.current) return;

            const el = gridRef.current?.querySelector(`[gs-id="${widgetId}"]`);
            if (el) {
                gridInstanceRef.current.removeWidget(el as HTMLElement, false);
            }

            const newLayout = layout.filter((w) => w.id !== widgetId);
            setLayout(newLayout);
            saveLayout(newLayout);
        },
        [layout, saveLayout],
    );

    // Add widget - just update state, React will re-render and useEffect will handle GridStack
    const addWidget = useCallback(
        (widgetId: string) => {
            const defaults = DEFAULT_WIDGET_LAYOUTS[widgetId];
            if (!defaults) return;

            // Check if already exists
            if (layout.some((w) => w.id === widgetId)) return;

            // Calculate position: find the rightmost edge of existing widgets
            let maxX = 0;
            let yAtMaxX = 0;
            layout.forEach((w) => {
                const rightEdge = w.x + w.w;
                if (rightEdge > maxX) {
                    maxX = rightEdge;
                    yAtMaxX = w.y;
                }
            });

            // If adding the new widget would exceed 12 columns, place it on next row
            let newX = maxX;
            let newY = yAtMaxX;
            if (maxX + defaults.w > 12) {
                newX = 0;
                // Find the lowest y + h to place on a new row
                newY = Math.max(...layout.map((w) => w.y + w.h), 0);
            }

            const newWidget: WidgetLayout = {
                id: widgetId,
                x: newX,
                y: newY,
                w: defaults.w,
                h: defaults.h,
                minW: defaults.minW,
                minH: defaults.minH,
            };

            const newLayout = [...layout, newWidget];
            setLayout(newLayout);
            saveLayout(newLayout);
        },
        [layout, saveLayout],
    );

    // Toggle widget visibility
    const toggleWidget = useCallback(
        (widgetId: string) => {
            if (visibleWidgetIds.includes(widgetId)) {
                removeWidget(widgetId);
            } else {
                addWidget(widgetId);
            }
        },
        [visibleWidgetIds, removeWidget, addWidget],
    );

    // Initialize GridStack once
    useEffect(() => {
        if (!gridRef.current) return;

        if (!gridInstanceRef.current) {
            gridInstanceRef.current = GridStack.init(
                {
                    column: 12,
                    cellHeight: 80,
                    margin: 12,
                    float: true,
                    animate: true,
                    resizable: {
                        handles: 'e,se,s,sw,w',
                    },
                },
                gridRef.current,
            );

            // Listen for change events
            gridInstanceRef.current.on('change', handleGridChange);
            isInitializedRef.current = true;
        }

        return () => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }
            if (gridInstanceRef.current) {
                gridInstanceRef.current.off('change');
                gridInstanceRef.current.destroy(false);
                gridInstanceRef.current = null;
            }
            isInitializedRef.current = false;
        };
    }, [handleGridChange]);

    // Make GridStack aware of new widgets when layout changes
    useEffect(() => {
        if (!gridInstanceRef.current || !gridRef.current) return;

        // Find elements that GridStack doesn't know about yet
        const gridItems = gridRef.current.querySelectorAll('.grid-stack-item');
        gridItems.forEach((el) => {
            const htmlEl = el as HTMLElement & { gridstackNode?: GridStackNode };
            if (!htmlEl.gridstackNode) {
                gridInstanceRef.current?.makeWidget(htmlEl);
            }
        });
    }, [layout]);

    const handleRangeChange = (value: DateRangePreset) => {
        setIsLoading(true);
        router.get(
            dashboard().url,
            { range: value },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsLoading(false),
            },
        );
    };

    // Render widget content based on ID
    const renderWidgetContent = (widgetId: string) => {
        switch (widgetId) {
            case 'accounts-receivable':
                return <AccountsReceivableWidget data={accountsReceivable} formatCurrency={formatCurrency} onRemove={() => removeWidget(widgetId)} />;
            case 'sales-tax':
                return <SalesTaxWidget data={salesTax} formatCurrency={formatCurrency} onRemove={() => removeWidget(widgetId)} />;
            case 'products-sold':
                return <CountStatWidget title="Productos vendidos" data={productsSold} icon={Package} onRemove={() => removeWidget(widgetId)} />;
            case 'customers-with-sales':
                return <CountStatWidget title="Clientes con ventas" data={customersWithSales} icon={Users} onRemove={() => removeWidget(widgetId)} />;
            case 'prescriptions-created':
                return (
                    <CountStatWidget title="Recetas creadas" data={prescriptionsCreated} icon={FileText} onRemove={() => removeWidget(widgetId)} />
                );
            default:
                return null;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tablero" />

            <style>{`
                .grid-stack {
                    background: transparent;
                }
                .grid-stack-item-content {
                    background: hsl(var(--card));
                    border: 1px solid hsl(var(--border));
                    border-radius: var(--radius);
                    overflow: hidden;
                    cursor: move;
                    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
                }
                .grid-stack-item-content:hover {
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }
                .grid-stack-placeholder > .placeholder-content {
                    background-color: hsl(var(--primary) / 0.1);
                    border: 2px dashed hsl(var(--primary));
                    border-radius: var(--radius);
                }
                .gs-resizing .grid-stack-item-content,
                .gs-dragging .grid-stack-item-content {
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                }
            `}</style>

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header with Range Filter */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Tablero</h1>
                        <p className="text-muted-foreground">Arrastra y reorganiza los widgets</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm">
                                    <LayoutGrid className="mr-2 h-4 w-4" />
                                    Widgets
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {Object.entries(availableWidgets).map(([id, label]) => (
                                    <DropdownMenuCheckboxItem
                                        key={id}
                                        checked={visibleWidgetIds.includes(id)}
                                        onCheckedChange={() => toggleWidget(id)}
                                    >
                                        {label}
                                    </DropdownMenuCheckboxItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Select value={filters.range} onValueChange={handleRangeChange} disabled={isLoading}>
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Seleccionar rango" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="current_month">{rangeLabels.current_month}</SelectItem>
                                <SelectItem value="last_3_months">{rangeLabels.last_3_months}</SelectItem>
                                <SelectItem value="last_6_months">{rangeLabels.last_6_months}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* GridStack Container */}
                <div className="grid-stack" ref={gridRef}>
                    {layout.map((widget) => (
                        <div
                            key={widget.id}
                            className="grid-stack-item"
                            gs-id={widget.id}
                            gs-x={widget.x}
                            gs-y={widget.y}
                            gs-w={widget.w}
                            gs-h={widget.h}
                            gs-min-w={widget.minW ?? DEFAULT_WIDGET_LAYOUTS[widget.id]?.minW ?? 1}
                            gs-min-h={widget.minH ?? DEFAULT_WIDGET_LAYOUTS[widget.id]?.minH ?? 1}
                        >
                            <div className="grid-stack-item-content">{renderWidgetContent(widget.id)}</div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
