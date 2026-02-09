<?php

declare(strict_types=1);

namespace App\Tables;

use App\Tables\Actions\BulkAction;
use App\Tables\Columns\Column;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\Filter;
use App\Tables\Filters\SearchFilter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonSerializable;

abstract class Table implements Arrayable, JsonSerializable
{
    protected string $model;

    protected ?string $defaultSort = null;

    protected string $defaultSortDirection = 'desc';

    protected int $perPage = 30;

    protected array $perPageOptions = [5, 15, 30, 50, 100];

    protected array $with = [];

    protected ?string $rowHref = null;

    protected bool $rowPrefetch = false;

    protected bool $selectable = false;

    protected ?Request $request = null;

    protected ?Builder $query = null;

    /**
     * Define the columns for the table.
     *
     * @return array<Column>
     */
    abstract public function columns(): array;

    /**
     * Create a new table instance.
     */
    public static function make(?Request $request = null): static
    {
        /** @var static $instance */
        $instance = app(static::class);
        $instance->request = $request ?? request();

        return $instance;
    }

    /**
     * Define the filters for the table.
     *
     * @return array<Filter>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Define the bulk actions for the table.
     *
     * @return array<BulkAction>
     */
    public function bulkActions(): array
    {
        return [];
    }

    /**
     * Set eager loading relationships.
     */
    public function with(array $relations): static
    {
        $this->with = $relations;

        return $this;
    }

    /**
     * Modify the base query.
     */
    public function query(Builder $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Convert the table to an array.
     */
    public function toArray(): array
    {
        $paginator = $this->getData();
        $bulkActions = $this->bulkActions();

        return [
            'data' => $paginator->through(fn (Model $record) => $this->transformRecord($record)),
            'columns' => $this->getColumnDefinitions(),
            'filters' => $this->getFilterDefinitions(),
            'appliedFilters' => $this->getAppliedFilters(),
            'sortBy' => $this->request->get('sort_by', $this->defaultSort),
            'sortDirection' => $this->request->get('sort_direction', $this->defaultSortDirection),
            'perPage' => (int) $this->request->get('per_page', $this->perPage),
            'perPageOptions' => $this->perPageOptions,
            'rowHref' => $this->rowHref,
            'rowPrefetch' => $this->rowPrefetch,
            'selectable' => $this->selectable || count($bulkActions) > 0,
            'bulkActions' => array_map(fn (BulkAction $action) => $action->toArray(), $bulkActions),
        ];
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the base query for the table.
     */
    protected function getQuery(): Builder
    {
        if ($this->query) {
            return $this->query;
        }

        return $this->model::query();
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query): void
    {
        $filters = $this->filters();

        foreach ($filters as $filter) {
            if ($filter instanceof SearchFilter) {
                $value = $this->request->get($filter->getName());
                if ($value) {
                    $filter->apply($query, $value);
                }
            } elseif ($filter instanceof DateRangeFilter) {
                $startValue = $this->request->get($filter->getStartName());
                $endValue = $this->request->get($filter->getEndName());
                if ($startValue || $endValue) {
                    $filter->applyDateRange($query, $startValue, $endValue);
                }
            } else {
                $value = $this->request->get($filter->getName());
                if ($value !== null && $value !== '') {
                    $filter->apply($query, $value);
                }
            }
        }
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(Builder $query): void
    {
        $sortBy = $this->request->get('sort_by', $this->defaultSort);
        $sortDirection = $this->request->get('sort_direction', $this->defaultSortDirection);

        if (! $sortBy) {
            return;
        }

        $column = collect($this->columns())->first(fn (Column $col) => $col->getName() === $sortBy && $col->isSortable());

        if ($column) {
            $column->applySort($query, $sortDirection === 'asc' ? 'asc' : 'desc');
        }
    }

    /**
     * Get the paginated data.
     */
    protected function getData(): LengthAwarePaginator
    {
        $query = $this->getQuery();

        if (! empty($this->with)) {
            $query->with($this->with);
        }

        $this->applyFilters($query);
        $this->applySorting($query);

        $perPage = $this->request->get('per_page', $this->perPage);
        if (! in_array((int) $perPage, $this->perPageOptions, true)) {
            $perPage = $this->perPage;
        }

        return $query->paginate((int) $perPage)->withQueryString();
    }

    /**
     * Transform a record into row data.
     */
    protected function transformRecord(Model $record): array
    {
        $row = [
            'id' => $record->getKey(),
            // Include raw model data for actions/modals that need full record access
            ...$record->toArray(),
        ];

        foreach ($this->columns() as $column) {
            if ($column->isHidden()) {
                continue;
            }

            $row[$column->getName()] = $column->getValue($record);

            $href = $column->getHref($record);

            if ($href) {
                $row[$column->getName().'_href'] = $href;
            }

            $tooltip = $column->getCellTooltip($record);

            if ($tooltip) {
                $row[$column->getName().'_tooltip'] = $tooltip;
            }

            $copyValue = $column->getCopyValue($record);

            if ($copyValue !== null && $copyValue !== '') {
                $row[$column->getName().'_copy'] = $copyValue;
            }
        }

        return $row;
    }

    /**
     * Get column definitions for the frontend.
     */
    protected function getColumnDefinitions(): array
    {
        return collect($this->columns())
            ->reject(fn (Column $column) => $column->isHidden())
            ->map(fn (Column $column) => $column->getDefinition())
            ->values()
            ->toArray();
    }

    /**
     * Get filter definitions for the frontend.
     */
    protected function getFilterDefinitions(): array
    {
        $definitions = [];

        foreach ($this->filters() as $filter) {
            if ($filter instanceof DateRangeFilter) {
                // Split date range into two date filters for frontend compatibility
                $baseDefinition = $filter->getDefinition();
                $definitions[] = [
                    'name' => $filter->getStartName(),
                    'label' => $filter->getLabel(),
                    'type' => 'date',
                    'default' => $filter->getDefault(),
                    'hidden' => $filter->isHidden(),
                    'inline' => $baseDefinition['inline'] ?? false,
                ];
                $definitions[] = [
                    'name' => $filter->getEndName(),
                    'label' => $filter->getLabel(),
                    'type' => 'date',
                    'default' => $filter->getDefault(),
                    'hidden' => $filter->isHidden(),
                    'inline' => $baseDefinition['inline'] ?? false,
                ];
            } else {
                $definitions[] = $filter->getDefinition();
            }
        }

        return $definitions;
    }

    /**
     * Get the currently applied filter values.
     */
    protected function getAppliedFilters(): array
    {
        $applied = [];

        foreach ($this->filters() as $filter) {
            if ($filter instanceof DateRangeFilter) {
                $applied[$filter->getStartName()] = $this->request->get($filter->getStartName(), '');
                $applied[$filter->getEndName()] = $this->request->get($filter->getEndName(), '');
            } else {
                $applied[$filter->getName()] = $this->request->get($filter->getName(), $filter->getDefault() ?? '');
            }
        }

        return $applied;
    }
}
