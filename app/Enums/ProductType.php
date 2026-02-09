<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case Product = 'product';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Producto',
            self::Service => 'Servicio',
        };
    }
}
