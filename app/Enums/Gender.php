<?php

declare(strict_types=1);

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case NotSpecified = '-';

    /**
     * Get the label
     */
    public function label(): string
    {
        return match ($this) {
            self::Male => 'Masculino',
            self::Female => 'Femenino',
            self::NotSpecified => 'No especificado',
        };
    }
}
