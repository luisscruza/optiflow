<?php

declare(strict_types=1);

namespace App\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;

final class SearchFilter extends Filter
{
    protected array $columns = [];

    public function __construct(?string $label = 'Buscar')
    {
        parent::__construct('search', $label);
        $this->placeholder = 'Buscar...';
    }

    public static function make(string $name = 'search', ?string $label = 'Buscar'): static
    {
        return new self($label);
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getType(): string
    {
        return 'search';
    }

    public function apply(Builder $query, mixed $value): void
    {
        if (empty($value) || empty($this->columns)) {
            return;
        }

        $query->where(function (Builder $query) use ($value): void {
            foreach ($this->columns as $column) {
                if (str_contains($column, '.')) {
                    // Handle relationship columns
                    $parts = explode('.', $column);
                    $relation = implode('.', array_slice($parts, 0, -1));
                    $field = end($parts);

                    $query->orWhereHas($relation, function (Builder $q) use ($field, $value): void {
                        $q->where($field, 'like', "%{$value}%");
                    });
                } else {
                    $query->orWhere($column, 'like', "%{$value}%");
                }
            }
        });
    }
}
