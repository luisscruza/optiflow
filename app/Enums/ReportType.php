<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportType: string
{
    // Sales Reports
    case GeneralSales = 'general_sales';
    case SalesByProduct = 'sales_by_product';
    case SalesByCustomer = 'sales_by_customer';
    case ProductProfitability = 'product_profitability';
    case SalesBySalesman = 'sales_by_salesman';
    case CustomerAccountStatement = 'customer_account_statement';
    case CustomersByBranch = 'customers_by_branch';
    case InvoicesVsExpenses = 'invoices_vs_expenses';

    // Expenses Reports
    case ExpensesSummary = 'expenses_summary';

    // Prescriptions Reports
    case PrescriptionsSummary = 'prescriptions_summary';
    case PrescriptionsByDoctor = 'prescriptions_by_doctor';

    // Workflow Reports
    case WorkflowSummary = 'workflow_summary';
    case PendingJobs = 'pending_jobs';

    // Inventory Reports
    case StockLevels = 'stock_levels';
    case StockMovements = 'stock_movements';
    case LowStockAlert = 'low_stock_alert';

    public function label(): string
    {
        return match ($this) {
            self::GeneralSales => 'Ventas generales',
            self::SalesByProduct => 'Ventas por producto/servicio',
            self::SalesByCustomer => 'Ventas por clientes',
            self::ProductProfitability => 'Rentabilidad por producto',
            self::SalesBySalesman => 'Ventas por vendedor',
            self::CustomerAccountStatement => 'Estado de cuenta por cliente',
            self::CustomersByBranch => 'Clientes por sucursal',
            self::InvoicesVsExpenses => 'Facturas vs gastos',
            self::ExpensesSummary => 'Gastos generales',
            self::PrescriptionsSummary => 'Resumen de recetas',
            self::PrescriptionsByDoctor => 'Recetas por evaluador',
            self::WorkflowSummary => 'Procesos por sucursal',
            self::PendingJobs => 'Trabajos pendientes',
            self::StockLevels => 'Niveles de inventario',
            self::StockMovements => 'Movimientos de inventario',
            self::LowStockAlert => 'Alertas de bajo stock',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GeneralSales => 'Revisa el desempeño de tus ventas para crear estrategias comerciales.',
            self::SalesByProduct => 'Consulta tus ventas detalladas por cada producto inventariable.',
            self::SalesByCustomer => 'Conoce las ventas asociadas a cada uno de tus clientes.',
            self::ProductProfitability => 'Conoce la utilidad que generan tus productos inventariables.',
            self::SalesBySalesman => 'Revisa el resumen de las ventas asociadas a cada vendedor/a.',
            self::CustomerAccountStatement => 'Revisa el detalle de las ventas asociadas a cada cliente.',
            self::CustomersByBranch => 'Exporta clientes consolidando las sucursales donde tuvieron facturas o recetas en el rango seleccionado.',
            self::InvoicesVsExpenses => 'Compara facturas y gastos para calcular el ITBIS vendido, comprado y neto por pagar.',
            self::ExpensesSummary => 'Consulta los gastos registrados por suplidor, sucursal, estado e impuestos.',
            self::PrescriptionsSummary => 'Resumen general de recetas.',
            self::PrescriptionsByDoctor => 'Detalle de recetas por médico.',
            self::WorkflowSummary => 'Consulta los procesos creados por sucursal y mes, mostrando su relacion con facturas o recetas.',
            self::PendingJobs => 'Lista de trabajos pendientes.',
            self::StockLevels => 'Niveles actuales de inventario.',
            self::StockMovements => 'Historial de movimientos de inventario.',
            self::LowStockAlert => 'Productos con bajo stock.',
        };
    }

    public function group(): ReportGroup
    {
        return match ($this) {
            self::GeneralSales, self::SalesByProduct, self::SalesByCustomer,
            self::ProductProfitability, self::SalesBySalesman, self::CustomerAccountStatement,
            self::CustomersByBranch, self::InvoicesVsExpenses => ReportGroup::Sales,
            self::ExpensesSummary => ReportGroup::Expenses,
            self::PrescriptionsSummary, self::PrescriptionsByDoctor => ReportGroup::Prescriptions,
            self::WorkflowSummary, self::PendingJobs => ReportGroup::Workflow,
            self::StockLevels, self::StockMovements, self::LowStockAlert => ReportGroup::Inventory,
        };
    }

    public function implemented(): bool
    {
        return match ($this) {
            self::GeneralSales,
            self::SalesByProduct,
            self::SalesByCustomer,
            self::CustomersByBranch,
            self::InvoicesVsExpenses,
            self::SalesBySalesman,
            self::ExpensesSummary,
            self::PrescriptionsSummary,
            self::PrescriptionsByDoctor,
            self::WorkflowSummary => true,
            default => false,
        };
    }
}
