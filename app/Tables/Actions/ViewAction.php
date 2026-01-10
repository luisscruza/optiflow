<?php

declare(strict_types=1);

namespace App\Tables\Actions;

final class ViewAction extends Action
{
    public function __construct(?string $label = 'Ver detalles')
    {
        parent::__construct('view', $label);
        $this->icon = 'eye';
    }

    public static function make(string $name = 'View', ?string $label = 'Ver detalles'): static
    {
        return new self($label);
    }
}
