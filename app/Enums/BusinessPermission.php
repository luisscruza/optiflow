<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessPermission: string
{
    case ViewConfiguration = 'view configuration';
    case ManageMembers = 'manage members';
    case ManageTaxes = 'manage taxes';
    case ManageInvoiceDocuments = 'manage invoice documents';
    case ManageBankAccounts = 'manage bank accounts';
    case ManageCurrencies = 'manage currencies';
    case ManageBusinessDetails = 'manage business details';

    /**
     * Todos los permisos.
     */
    public static function allPermissions(): array
    {
        return [
            self::ViewConfiguration,
            self::ManageMembers,
            self::ManageTaxes,
            self::ManageInvoiceDocuments,
            self::ManageBankAccounts,
            self::ManageCurrencies,
            self::ManageBusinessDetails,
        ];
    }

    /**
     * Get the label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ViewConfiguration => 'Ver configuración',
            self::ManageMembers => 'Gestionar miembros',
            self::ManageTaxes => 'Gestionar impuestos',
            self::ManageInvoiceDocuments => 'Gestionar documentos de facturación',
            self::ManageBankAccounts => 'Gestionar cuentas bancarias',
            self::ManageCurrencies => 'Gestionar monedas',
            self::ManageBusinessDetails => 'Gestionar detalles del negocio',
        };
    }
}
