<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
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

    public static function all(): array
    {
        return array_map(fn (self $permission) => $permission->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
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
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ProductsView, self::ProductsCreate, self::ProductsEdit, self::ProductsDelete => 'Productos',
            self::ContactsView, self::ContactsCreate, self::ContactsEdit, self::ContactsDelete => 'Contactos',
            self::InvoicesView, self::InvoicesCreate, self::InvoicesEdit, self::InvoicesDelete => 'Facturas',
            self::QuotationsView, self::QuotationsCreate, self::QuotationsEdit, self::QuotationsDelete => 'Cotizaciones',
            self::PrescriptionsView, self::PrescriptionsCreate, self::PrescriptionsEdit, self::PrescriptionsDelete => 'Recetas',
            self::InventoryView, self::InventoryAdjust, self::InventoryTransfer => 'Inventario',
            self::ConfigurationView, self::ConfigurationEdit => 'Configuración',
            self::ReportsView, self::ReportsExport => 'Reportes',
        };
    }
}
