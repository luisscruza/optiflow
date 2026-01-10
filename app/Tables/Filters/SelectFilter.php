<?php

declare(strict_types=1);

namespace App\Tables\Filters;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Builder;

final class SelectFilter extends Filter
{
    protected array $options = [];

    protected ?Closure $optionsUsing = null;

    public function options(array|BackedEnum|string $options): static
    {
        if (is_string($options) && enum_exists($options)) {
            $this->options = collect($options::cases())
                ->mapWithKeys(fn ($case) => [$case->value => method_exists($case, 'label') ? $case->label() : $case->name])
                ->toArray();
        } elseif ($options instanceof BackedEnum) {
            $this->options = collect($options::cases())
                ->mapWithKeys(fn ($case) => [$case->value => method_exists($case, 'label') ? $case->label() : $case->name])
                ->toArray();
        } else {
            $this->options = $options;
        }

        return $this;
    }

    public function optionsUsing(Closure $callback): static
    {
        $this->optionsUsing = $callback;

        return $this;
    }

    public function getOptions(): array
    {
        if ($this->optionsUsing) {
            return call_user_func($this->optionsUsing);
        }

        return $this->options;
    }

    public function getType(): string
    {
        return 'select';
    }

    public function apply(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '' || $value === 'all') {
            return;
        }

        $query->where($this->name, $value);
    }

    public function getDefinition(): array
    {
        return array_merge(parent::getDefinition(), [
            'options' => collect($this->getOptions())
                ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
                ->values()
                ->toArray(),
        ]);
    }
}
