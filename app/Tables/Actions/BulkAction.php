<?php

declare(strict_types=1);

namespace App\Tables\Actions;

class BulkAction
{
    protected string $name;

    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $color = null;

    protected bool $requiresConfirmation = false;

    protected ?string $confirmationMessage = null;

    protected ?string $permission = null;

    protected ?string $handler = null;

    protected ?string $href = null;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public static function make(string $name, ?string $label = null): static
    {
        /** @var static $instance */
        $instance = app(static::class, ['name' => $name, 'label' => $label]);

        return $instance;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function requiresConfirmation(string $message = '¿Está seguro de que desea realizar esta acción en los elementos seleccionados?'): static
    {
        $this->requiresConfirmation = true;
        $this->confirmationMessage = $message;

        return $this;
    }

    public function permission(string $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    public function handler(string $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    public function href(string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->name)->headline()->toString();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'icon' => $this->icon,
            'color' => $this->color,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmationMessage' => $this->confirmationMessage,
            'permission' => $this->permission,
            'handler' => $this->handler,
            'href' => $this->href,
        ];
    }
}
