<?php

declare(strict_types=1);

namespace App\Enums;

enum DashboardWidget: string
{
    case AccountsReceivable = 'accounts-receivable';
    case SalesTax = 'sales-tax';
    case ProductsSold = 'products-sold';
    case CustomersWithSales = 'customers-with-sales';
    case PrescriptionsCreated = 'prescriptions-created';
    case WorkflowsSummary = 'workflows-summary';
    case TotalSales = 'total-sales';

    /**
     * Get all widgets as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $widget) => $widget->value, self::cases()),
            array_map(fn (self $widget) => $widget->label(), self::cases()),
        );
    }

    /**
     * Get all widget values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $widget) => $widget->value, self::cases());
    }

    /**
     * Get the default layouts for all widgets.
     *
     * @return array<array{id: string, x: int, y: int, w: int, h: int, minW: int, minH: int}>
     */
    public static function defaultLayouts(): array
    {
        return array_map(
            fn (self $widget) => ['id' => $widget->value, ...$widget->defaultLayout()],
            self::cases(),
        );
    }

    /**
     * Get the display label for the widget.
     */
    public function label(): string
    {
        return match ($this) {
            self::AccountsReceivable => 'Cuentas por cobrar',
            self::SalesTax => 'Impuestos en venta',
            self::ProductsSold => 'Productos vendidos',
            self::CustomersWithSales => 'Clientes con ventas',
            self::PrescriptionsCreated => 'Recetas creadas',
            self::WorkflowsSummary => 'Resumen de procesos',
            self::TotalSales => 'Total de ventas',
        };
    }

    /**
     * Get the required permission for the widget.
     */
    public function requiredPermission(): Permission
    {
        return match ($this) {
            self::AccountsReceivable => Permission::ViewDashboardAccountReceivable,
            self::SalesTax => Permission::ViewDashboardSalesStats,
            self::ProductsSold => Permission::ViewDashboardProductsStats,
            self::CustomersWithSales => Permission::ViewDashboardCustomersStats,
            self::PrescriptionsCreated => Permission::ViewDashboardPrescriptionsStats,
            self::WorkflowsSummary => Permission::ViewDashboardWorkflowsStats,
            self::TotalSales => Permission::ViewDashboardSalesStats,
        };
    }

    /**
     * Get the default layout for the widget.
     *
     * @return array{x: int, y: int, w: int, h: int, minW: int, minH: int}
     */
    public function defaultLayout(): array
    {
        return match ($this) {
            self::AccountsReceivable => ['x' => 0, 'y' => 0, 'w' => 5, 'h' => 3, 'minW' => 4, 'minH' => 3],
            self::SalesTax => ['x' => 5, 'y' => 0, 'w' => 3, 'h' => 2, 'minW' => 2, 'minH' => 1],
            self::ProductsSold => ['x' => 8, 'y' => 0, 'w' => 2, 'h' => 2, 'minW' => 2, 'minH' => 1],
            self::CustomersWithSales => ['x' => 10, 'y' => 0, 'w' => 2, 'h' => 2, 'minW' => 2, 'minH' => 1],
            self::PrescriptionsCreated => ['x' => 0, 'y' => 3, 'w' => 2, 'h' => 2, 'minW' => 2, 'minH' => 1],
            self::WorkflowsSummary => ['x' => 2, 'y' => 3, 'w' => 6, 'h' => 3, 'minW' => 4, 'minH' => 2],
            self::TotalSales => ['x' => 0, 'y' => 6, 'w' => 12, 'h' => 4, 'minW' => 6, 'minH' => 3],
        };
    }
}
