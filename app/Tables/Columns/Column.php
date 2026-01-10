<?php

declare(strict_types=1);

namespace App\Tables\Columns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class Column
{
    protected string $name;

    protected ?string $label = null;

    protected bool $sortable = false;

    protected ?Closure $sortUsing = null;

    protected ?string $align = null;

    protected ?string $href = null;

    protected ?Closure $hrefUsing = null;

    protected bool $hidden = false;

    protected ?Closure $formatUsing = null;

    protected ?string $className = null;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    abstract public function getType(): string;

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function sortUsing(Closure $callback): static
    {
        $this->sortUsing = $callback;
        $this->sortable = true;

        return $this;
    }

    public function alignRight(): static
    {
        $this->align = 'right';

        return $this;
    }

    public function alignCenter(): static
    {
        $this->align = 'center';

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

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function formatUsing(Closure $callback): static
    {
        $this->formatUsing = $callback;

        return $this;
    }

    public function className(string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->name)->afterLast('.')->headline()->toString();
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function applySort(Builder $query, string $direction): void
    {
        if ($this->sortUsing) {
            call_user_func($this->sortUsing, $query, $direction);
        } else {
            $query->orderBy($this->name, $direction);
        }
    }

    public function getValue(Model $record): mixed
    {
        $value = data_get($record, $this->name);

        if ($this->formatUsing) {
            $value = call_user_func($this->formatUsing, $value, $record);
        }

        return $value;
    }

    public function getHref(Model $record): ?string
    {
        if ($this->hrefUsing) {
            return call_user_func($this->hrefUsing, $record);
        }

        if ($this->href) {
            return preg_replace_callback('/\{(\w+)\}/', fn ($matches) => data_get($record, $matches[1]), $this->href);
        }

        return null;
    }

    public function toArray(Model $record): array
    {
        return [
            'value' => $this->getValue($record),
            'href' => $this->getHref($record),
        ];
    }

    public function getDefinition(): array
    {
        return [
            'key' => $this->name,
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'sortable' => $this->sortable,
            'align' => $this->align,
            'className' => $this->className,
        ];
    }
}
