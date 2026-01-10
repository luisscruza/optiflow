<?php

declare(strict_types=1);

namespace App\Tables\Actions;

final class DeleteAction extends Action
{
    public function __construct(?string $label = 'Eliminar')
    {
        parent::__construct('delete', $label);
        $this->icon = 'trash';
        $this->color = 'danger';
        $this->requiresConfirmation = true;
        $this->confirmationMessage = '¿Estás seguro de que deseas eliminar este registro?';
    }

    public static function make(string $name = 'Delete', ?string $label = 'Eliminar'): static
    {
        return new self($label);
    }
}
