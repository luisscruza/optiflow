<?php

declare(strict_types=1);

namespace App\Tables\Actions;

final class EditAction extends Action
{
    public function __construct(?string $label = 'Editar')
    {
        parent::__construct('edit', $label);
        $this->icon = 'edit';
    }

    public static function make(string $name = 'Edit', ?string $label = 'Editar'): static
    {
        return new self($label);
    }
}
