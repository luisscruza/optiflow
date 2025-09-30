import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { 
    TrendingUp, 
    TrendingDown, 
    DollarSign, 
    FileText, 
    Users, 
    CreditCard,
    AlertTriangle,
    RefreshCw,
    Package
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';

interface DailySale {
    date: string;
    day: string;
    day_name: string;
    invoice_count: number;
    total_sales: number;
    total_tax: number;
    total_subtotal: number;
}

interface SummaryStats {
    current_month: {
        total_invoices: number;
        total_sales: number;
        total_tax: number;
        average_invoice_amount: number;
        unique_products_sold: number;
        customers_with_sales: number;
    };
    last_year_same_month: {
        total_invoices: number;
        total_sales: number;
        total_tax: number;
        unique_products_sold: number;
        customers_with_sales: number;
    };
    changes: {
        sales_percentage: number;
        invoice_count_percentage: number;
        products_sold_percentage: number;
        customers_with_sales_percentage: number;
    };
}

interface AccountsData {
    accounts_receivable: {
        pending_count: number;
        total_pending: number;
        overdue_count: number;
        overdue_amount: number;
    };
    customer_returns: {
        return_count: number;
        total_returns: number;
    };
}

interface DashboardProps {
    dailySales: DailySale[];
    summaryStats: SummaryStats;
    accountsData: AccountsData;
    currentMonth: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resumen del negocio',
        href: dashboard().url,
    },
];

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: 'DOP',
        minimumFractionDigits: 2,
    }).format(amount);
}

function formatNumber(num: number): string {
    return new Intl.NumberFormat('es-DO').format(num);
}

function StatCard({ 
    title, 
    value, 
    change, 
    icon: Icon, 
    format = 'currency' 
}: { 
    title: string; 
    value: number; 
    change?: number; 
    icon: any; 
    format?: 'currency' | 'number' 
}) {
    const isPositive = change && change > 0;
    const isNegative = change && change < 0;
    
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">
                    {format === 'currency' ? formatCurrency(value) : formatNumber(value)}
                </div>
                {change !== undefined && (
                    <div className="flex items-center text-xs text-muted-foreground">
                        {isPositive && <TrendingUp className="h-3 w-3 mr-1 text-green-500" />}
                        {isNegative && <TrendingDown className="h-3 w-3 mr-1 text-red-500" />}
                        <span className={`font-medium ${isPositive ? 'text-green-500' : isNegative ? 'text-red-500' : ''}`}>
                            {change > 0 ? '+' : ''}{change}%
                        </span>
                        <span className="ml-1">vs mismo mes año pasado</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function DashboardIndex({ 
    dailySales, 
    summaryStats, 
    accountsData, 
    currentMonth 
}: DashboardProps) {
    // Transform daily sales data for charts
    const chartData = dailySales.map(day => ({
        day: day.day,
        date: day.date,
        sales: day.total_sales,
        invoices: day.invoice_count,
        label: `${day.day} ${day.day_name}`,
    }));

    const totalSales = dailySales.reduce((sum, day) => sum + day.total_sales, 0);
    const totalInvoices = dailySales.reduce((sum, day) => sum + day.invoice_count, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resumen del negocio" />
            
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Resumen del negocio</h1>
                        <p className="text-muted-foreground">
                            {currentMonth}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge variant="outline" className="text-xs">
                            Mes actual
                        </Badge>
                    </div>
                </div>

                {/* Summary Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total de ventas"
                        value={summaryStats.current_month.total_sales}
                        change={summaryStats.changes.sales_percentage}
                        icon={DollarSign}
                        format="currency"
                    />
                    <StatCard
                        title="Productos vendidos"
                        value={summaryStats.current_month.unique_products_sold}
                        change={summaryStats.changes.products_sold_percentage}
                        icon={Package}
                        format="number"
                    />
                    <StatCard
                        title="Impuestos en venta"
                        value={summaryStats.current_month.total_tax}
                        icon={CreditCard}
                        format="currency"
                    />
                    <StatCard
                        title="Clientes con ventas"
                        value={summaryStats.current_month.customers_with_sales}
                        change={summaryStats.changes.customers_with_sales_percentage}
                        icon={Users}
                        format="number"
                    />
                </div>

                {/* Accounts Status Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Cuentas por cobrar</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(accountsData.accounts_receivable.total_pending)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {formatNumber(accountsData.accounts_receivable.pending_count)} documentos
                            </p>
                        </CardContent>
                    </Card>
            

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Facturas vencidas</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">
                                {formatCurrency(accountsData.accounts_receivable.overdue_amount)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {formatNumber(accountsData.accounts_receivable.overdue_count)} documentos
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Section */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Sales Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Total de ventas</CardTitle>
                            <CardDescription>
                                La gráfica muestra el valor de tus ventas con impuestos incluidos.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={chartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis 
                                        dataKey="day" 
                                        tick={{ fontSize: 12 }}
                                    />
                                    <YAxis 
                                        tick={{ fontSize: 12 }}
                                        tickFormatter={(value) => formatCurrency(value)}
                                    />
                                    <Tooltip 
                                        formatter={(value) => [formatCurrency(Number(value)), 'Ventas']}
                                        labelFormatter={(label) => `Día ${label}`}
                                    />
                                    <Line 
                                        type="monotone" 
                                        dataKey="sales" 
                                        stroke="#3b82f6" 
                                        strokeWidth={2}
                                        dot={{ fill: '#3b82f6', strokeWidth: 2, r: 4 }}
                                        activeDot={{ r: 6 }}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                            <div className="flex items-center justify-between mt-4 text-sm text-muted-foreground">
                                <span>1 sept de 2025 - 30 sept de 2025</span>
                                <span>Total: {formatCurrency(totalSales)}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice Count Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Cantidad de facturas</CardTitle>
                            <CardDescription>
                                Número de facturas emitidas por día este mes.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={chartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis 
                                        dataKey="day" 
                                        tick={{ fontSize: 12 }}
                                    />
                                    <YAxis 
                                        tick={{ fontSize: 12 }}
                                    />
                                    <Tooltip 
                                        formatter={(value) => [formatNumber(Number(value)), 'Facturas']}
                                        labelFormatter={(label) => `Día ${label}`}
                                    />
                                    <Bar 
                                        dataKey="invoices" 
                                        fill="#10b981"
                                        radius={[2, 2, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                            <div className="flex items-center justify-between mt-4 text-sm text-muted-foreground">
                                <span>1 sept de 2025 - 30 sept de 2025</span>
                                <span>Total: {formatNumber(totalInvoices)} facturas</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}