<?php

declare(strict_types=1);

namespace App\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    protected string $name;

    protected ?string $label = null;

    protected mixed $default = null;

    protected bool $hidden = false;

    protected ?string $placeholder = null;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    abstract public function getType(): string;

    abstract public function apply(Builder $query, mixed $value): void;

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

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

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'default' => $this->default,
            'hidden' => $this->hidden,
            'placeholder' => $this->placeholder,
        ];
    }
}
