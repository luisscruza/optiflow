<?php

declare(strict_types=1);

namespace App\Enums;

enum ShareTemplateChannel: string
{
    case Email = 'email';
    case WhatsApp = 'whatsapp';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Email->value => self::Email->label(),
            self::WhatsApp->value => self::WhatsApp->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Correo',
            self::WhatsApp => 'WhatsApp',
        };
    }
}
