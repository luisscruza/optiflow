<?php

declare(strict_types=1);

namespace App\Tables\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Action
{
    protected string $name;

    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $href = null;

    protected ?Closure $hrefUsing = null;

    protected ?string $color = null;

    protected bool $requiresConfirmation = false;

    protected ?string $confirmationMessage = null;

    protected ?Closure $visibleWhen = null;

    protected ?string $permission = null;

    protected bool $isCustom = false;

    protected ?string $handler = null;

    protected bool $isInline = false;

    protected ?string $target = null;

    protected bool $prefetch = false;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public static function make(string $name, ?string $label = null): static
    {
        return new self($name, $label);
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

    public function href(string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function hrefUsing(Closure $callback): static
    {
        $this->hrefUsing = $callback;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function danger(): static
    {
        $this->color = 'danger';

        return $this;
    }

    public function requiresConfirmation(string $message = '¿Estás seguro?'): static
    {
        $this->requiresConfirmation = true;
        $this->confirmationMessage = $message;

        return $this;
    }

    public function visibleWhen(Closure $callback): static
    {
        $this->visibleWhen = $callback;

        return $this;
    }

    public function permission(string $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    public function custom(): static
    {
        $this->isCustom = true;

        return $this;
    }

    public function handler(string $handler): static
    {
        $this->handler = $handler;
        $this->isCustom = true;

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->isInline = $inline;

        return $this;
    }

    public function target(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function prefetch(bool $prefetch = true): static
    {
        $this->prefetch = $prefetch;

        return $this;
    }

    public function isVisible(Model $record): bool
    {
        if ($this->visibleWhen) {
            return call_user_func($this->visibleWhen, $record);
        }

        return true;
    }

    public function getHref(Model $record): ?string
    {
        if ($this->hrefUsing) {
            return call_user_func($this->hrefUsing, $record);
        }

        if ($this->href) {
            return preg_replace_callback('/\{(\w+)\}/', fn($matches) => data_get($record, $matches[1]), $this->href);
        }

        return null;
    }

    public function toArray(Model $record): ?array
    {
        if (! $this->isVisible($record)) {
            return null;
        }

        return [
            'name' => $this->name,
            'label' => $this->label ?? str($this->name)->headline()->toString(),
            'icon' => $this->icon,
            'href' => $this->getHref($record),
            'color' => $this->color,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmationMessage' => $this->confirmationMessage,
            'permission' => $this->permission,
            'isCustom' => $this->isCustom,
            'handler' => $this->handler,
            'isInline' => $this->isInline,
            'target' => $this->target,
            'prefetch' => $this->prefetch,
        ];
    }
}
