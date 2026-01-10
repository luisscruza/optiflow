<?php

declare(strict_types=1);

namespace App\Tables\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class DateRangeFilter extends Filter
{
    protected string $startName;

    protected string $endName;

    protected ?string $column = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->startName = $name.'_start';
        $this->endName = $name.'_end';
    }

    /**
     * Configure the start and end parameter names.
     */
    public function names(string $startName, string $endName): static
    {
        $this->startName = $startName;
        $this->endName = $endName;

        return $this;
    }

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getStartName(): string
    {
        return $this->startName;
    }

    public function getEndName(): string
    {
        return $this->endName;
    }

    public function getColumn(): string
    {
        return $this->column ?? 'created_at';
    }

    public function getType(): string
    {
        return 'date_range';
    }

    public function apply(Builder $query, mixed $value): void
    {
        // This is handled by applyDateRange instead
    }

    public function applyDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        $column = $this->getColumn();

        if ($startDate) {
            $query->whereDate($column, '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->whereDate($column, '<=', Carbon::parse($endDate)->endOfDay());
        }
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->startName,
            'endName' => $this->endName,
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'default' => $this->default,
            'hidden' => $this->hidden,
        ];
    }
}
