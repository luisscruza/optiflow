<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportGroup: string
{
    case Sales = 'sales';
    case Prescriptions = 'prescriptions';
    case Workflow = 'workflow';
    case Inventory = 'inventory';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Ventas',
            self::Prescriptions => 'Recetas',
            self::Workflow => 'Flujo de trabajo',
            self::Inventory => 'Inventario',
        };
    }
}
