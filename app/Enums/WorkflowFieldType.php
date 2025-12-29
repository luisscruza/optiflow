<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkflowFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Boolean = 'boolean';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Textarea => 'Texto largo',
            self::Number => 'Número',
            self::Date => 'Fecha',
            self::Select => 'Selección',
            self::Boolean => 'Sí/No',
        };
    }
}
