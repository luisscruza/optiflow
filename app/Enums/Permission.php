<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case ViewAllLocations = 'view all locations';
    // Products
    case ProductsView = 'view products';
    case ProductsCreate = 'create products';
    case ProductsEdit = 'edit products';
    case ProductsDelete = 'delete products';

    // Contacts
    case ContactsView = 'view contacts';
    case ContactsCreate = 'create contacts';
    case ContactsEdit = 'edit contacts';
    case ContactsDelete = 'delete contacts';

    // Invoices
    case InvoicesView = 'view invoices';
    case InvoicesCreate = 'create invoices';
    case InvoicesEdit = 'edit invoices';
    case InvoicesDelete = 'delete invoices';

    // Payments
    case PaymentsView = 'view payments';
    case PaymentsCreate = 'create payments';
    case PaymentsEdit = 'edit payments';
    case PaymentsDelete = 'delete payments';

    // Quotations
    case QuotationsView = 'view quotations';
    case QuotationsCreate = 'create quotations';
    case QuotationsEdit = 'edit quotations';
    case QuotationsDelete = 'delete quotations';

    // Prescriptions
    case PrescriptionsView = 'view prescriptions';
    case PrescriptionsCreate = 'create prescriptions';
    case PrescriptionsEdit = 'edit prescriptions';
    case PrescriptionsDelete = 'delete prescriptions';

    // Inventory
    case InventoryView = 'view inventory';
    case InventoryAdjust = 'adjust inventory';
    case InventoryTransfer = 'transfer inventory';

    // Configuration
    case ConfigurationView = 'view configuration';
    case ConfigurationEdit = 'edit configuration';

    // Reports
    case ReportsView = 'view reports';
    case ReportsExport = 'export reports';

    // Dashboard
    case ViewDashboardAccountReceivable = 'view dashboard accounts receivable';
    case ViewDashboardSalesStats = 'view dashboard sales stats';
    case ViewDashboardProductsStats = 'view dashboard products stats';
    case ViewDashboardCustomersStats = 'view dashboard customers stats';
    case ViewDashboardPrescriptionsStats = 'view dashboard prescriptions stats';
    case ViewDashboardWorkflowsStats = 'view dashboard workflows stats';

    // Workflows
    case ViewWorkflows = 'view workflows';
    case CreateWorkflows = 'create workflows';
    case EditWorkflows = 'edit workflows';
    case DeleteWorkflows = 'delete workflows';

    // Workflow Jobs
    case CreateWorkflowJobs = 'create workflow jobs';
    case EditWorkflowJobs = 'edit workflow jobs';
    case DeleteWorkflowJobs = 'delete workflow jobs';

    // Workflow Stages
    case CreateWorkflowStages = 'create workflow stages';
    case EditWorkflowStages = 'edit workflow stages';
    case DeleteWorkflowStages = 'delete workflow stages';

    // Mastertables
    case MastertablesView = 'view mastertables';
    case MastertablesCreate = 'create mastertables';
    case MastertablesEdit = 'edit mastertables';
    case MastertablesDelete = 'delete mastertables';

    public static function all(): array
    {
        return array_map(fn (self $permission) => $permission->value, self::cases());
    }

    public static function adminOnly(): array
    {
        return [
            self::ConfigurationView->value,
            self::ConfigurationEdit->value,
            self::ReportsView->value,
            self::ReportsExport->value,
            self::InventoryAdjust->value,
            self::InventoryTransfer->value,
            self::ProductsDelete->value,
            self::ContactsDelete->value,
            self::InvoicesDelete->value,
            self::PaymentsDelete->value,
            self::QuotationsDelete->value,
            self::PrescriptionsDelete->value,
            self::CreateWorkflows->value,
            self::EditWorkflows->value,
            self::DeleteWorkflows->value,
            self::DeleteWorkflowJobs->value,
            self::CreateWorkflowStages->value,
            self::EditWorkflowStages->value,
            self::DeleteWorkflowStages->value,
            self::ViewAllLocations->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::ViewAllLocations => 'Ver todas las sucursales',
            // Products
            self::ProductsView => 'Ver productos',
            self::ProductsCreate => 'Crear productos',
            self::ProductsEdit => 'Editar productos',
            self::ProductsDelete => 'Eliminar productos',

            // Contacts
            self::ContactsView => 'Ver contactos',
            self::ContactsCreate => 'Crear contactos',
            self::ContactsEdit => 'Editar contactos',
            self::ContactsDelete => 'Eliminar contactos',

            // Invoices
            self::InvoicesView => 'Ver facturas',
            self::InvoicesCreate => 'Crear facturas',
            self::InvoicesEdit => 'Editar facturas',
            self::InvoicesDelete => 'Eliminar facturas',

            // Payments
            self::PaymentsView => 'Ver pagos',
            self::PaymentsCreate => 'Registrar pagos',
            self::PaymentsEdit => 'Editar pagos',
            self::PaymentsDelete => 'Eliminar pagos',

            // Quotations
            self::QuotationsView => 'Ver cotizaciones',
            self::QuotationsCreate => 'Crear cotizaciones',
            self::QuotationsEdit => 'Editar cotizaciones',
            self::QuotationsDelete => 'Eliminar cotizaciones',

            // Prescriptions
            self::PrescriptionsView => 'Ver recetas',
            self::PrescriptionsCreate => 'Crear recetas',
            self::PrescriptionsEdit => 'Editar recetas',
            self::PrescriptionsDelete => 'Eliminar recetas',

            // Inventory
            self::InventoryView => 'Ver inventario',
            self::InventoryAdjust => 'Ajustar inventario',
            self::InventoryTransfer => 'Transferir inventario',

            // Configuration
            self::ConfigurationView => 'Ver configuración',
            self::ConfigurationEdit => 'Editar configuración',

            // Reports
            self::ReportsView => 'Ver reportes',
            self::ReportsExport => 'Exportar reportes',

            // Workflows
            self::ViewWorkflows => 'Ver flujos de trabajo',
            self::CreateWorkflows => 'Crear flujos de trabajo',
            self::EditWorkflows => 'Editar flujos de trabajo',
            self::DeleteWorkflows => 'Eliminar flujos de trabajo',

            // Workflow Jobs
            self::CreateWorkflowJobs => 'Crear procesos',
            self::EditWorkflowJobs => 'Editar procesos',
            self::DeleteWorkflowJobs => 'Eliminar procesos',

            // Workflow Stages
            self::CreateWorkflowStages => 'Crear etapas de procesos',
            self::EditWorkflowStages => 'Editar etapas de procesos',
            self::DeleteWorkflowStages => 'Eliminar etapas de procesos',

            // Dashboard
            self::ViewDashboardAccountReceivable => 'Ver panel de cuentas por cobrar',
            self::ViewDashboardSalesStats => 'Ver panel de estadísticas de ventas',
            self::ViewDashboardProductsStats => 'Ver panel de estadísticas de productos',
            self::ViewDashboardCustomersStats => 'Ver panel de estadísticas de clientes',
            self::ViewDashboardPrescriptionsStats => 'Ver panel de estadísticas de recetas',
            self::ViewDashboardWorkflowsStats => 'Ver panel de resumen de procesos',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ViewAllLocations => 'General',
            self::ProductsView, self::ProductsCreate, self::ProductsEdit, self::ProductsDelete => 'Productos',
            self::ContactsView, self::ContactsCreate, self::ContactsEdit, self::ContactsDelete => 'Contactos',
            self::InvoicesView, self::InvoicesCreate, self::InvoicesEdit, self::InvoicesDelete => 'Facturas',
            self::PaymentsView, self::PaymentsCreate, self::PaymentsEdit, self::PaymentsDelete => 'Pagos',
            self::QuotationsView, self::QuotationsCreate, self::QuotationsEdit, self::QuotationsDelete => 'Cotizaciones',
            self::PrescriptionsView, self::PrescriptionsCreate, self::PrescriptionsEdit, self::PrescriptionsDelete => 'Recetas',
            self::InventoryView, self::InventoryAdjust, self::InventoryTransfer => 'Inventario',
            self::ConfigurationView, self::ConfigurationEdit => 'Configuración',
            self::ReportsView, self::ReportsExport => 'Reportes',
            self::ViewDashboardAccountReceivable, self::ViewDashboardSalesStats, self::ViewDashboardProductsStats, self::ViewDashboardCustomersStats, self::ViewDashboardPrescriptionsStats, self::ViewDashboardWorkflowsStats => 'Panel',
            self::ViewWorkflows, self::CreateWorkflows, self::EditWorkflows, self::DeleteWorkflows, self::CreateWorkflowJobs, self::EditWorkflowJobs, self::DeleteWorkflowJobs, self::CreateWorkflowStages, self::EditWorkflowStages, self::DeleteWorkflowStages => 'Flujos de trabajo',
        };
    }
}
