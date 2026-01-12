<?php

declare(strict_types=1);

namespace App\Tables\Columns;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;

final class BadgeColumn extends Column
{
    protected array $colors = [];

    protected ?Closure $colorUsing = null;

    protected array $labels = [];

    protected ?Closure $labelUsing = null;

    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function colorUsing(Closure $callback): static
    {
        $this->colorUsing = $callback;

        return $this;
    }

    public function labels(array $labels): static
    {
        $this->labels = $labels;

        return $this;
    }

    public function labelUsing(Closure $callback): static
    {
        $this->labelUsing = $callback;

        return $this;
    }

    public function getValue(Model $record): mixed
    {
        $rawValue = data_get($record, $this->name);

        if ($record->{$this->name} instanceof BackedEnum) {
            return [
                'value' => $record->{$this->name}->value,
                'label' => $record->{$this->name}->label(),
                'variant' => $record->{$this->name}->badgeVariant(),
                'className' => $record->{$this->name}->badgeClassName(),
            ];
        }
        $label = $this->getDisplayLabel($rawValue, $record);
        $color = $this->getColor($rawValue, $record);
        $variant = $this->mapColorToVariant($color);
        $className = $this->getColorClassName($color);

        return [
            'value' => $rawValue,
            'label' => $label,
            'variant' => $variant,
            'className' => $className,
        ];

    }

    public function getType(): string
    {
        return 'badge';
    }

    protected function getDisplayLabel(mixed $value, Model $record): string
    {
        if ($this->labelUsing) {
            return call_user_func($this->labelUsing, $value, $record);
        }

        return $this->labels[$value] ?? ucfirst((string) $value);
    }

    protected function getColor(mixed $value, Model $record): string
    {
        if ($this->colorUsing) {
            return call_user_func($this->colorUsing, $value, $record);
        }

        return $this->colors[$value] ?? 'gray';
    }

    protected function mapColorToVariant(string $color): string
    {
        return match ($color) {
            'green', 'success' => 'default',
            'red', 'danger' => 'destructive',
            'yellow', 'warning' => 'secondary',
            default => 'outline',
        };
    }

    protected function getColorClassName(string $color): ?string
    {
        return match ($color) {
            'green', 'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'red', 'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'yellow', 'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'blue', 'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            default => null,
        };
    }
}
