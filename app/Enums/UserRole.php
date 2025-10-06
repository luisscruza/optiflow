<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Sales = 'sales';
    case Support = 'support';
    case User = 'user';
    case Marketing = 'marketing';

    /**
     * Get all the roles as an array of strings.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Owner->value => self::Owner->label(),
            self::Admin->value => self::Admin->label(),
            self::Sales->value => self::Sales->label(),
        ];
    }

    /**
     * Get the label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propietario',
            self::Admin => 'Administrador',
            self::Sales => 'Vendedor',
        };
    }

    /**
     * Get the description for the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Acceso completo a toda la aplicación, gestión de usuarios y configuración global.',
            self::Admin => 'Acceso administrativo completo, puede gestionar usuarios y workspaces.',
            self::Sales => 'Acceso a funciones de ventas y workspaces asignados.',
        };
    }
}
