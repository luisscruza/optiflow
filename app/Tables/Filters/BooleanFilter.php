<?php

declare(strict_types=1);

namespace App\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;

final class BooleanFilter extends Filter
{
    protected ?string $trueLabel = 'Sí';

    protected ?string $falseLabel = 'No';

    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;

        return $this;
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

        if ($this->queryCallback) {
            ($this->queryCallback)($query, $value);

            return;
        }

        $query->where($this->name, $value === '1' || $value === 'true' || $value === true);
    }

    public function getDefinition(): array
    {
        return array_merge(parent::getDefinition(), [
            'options' => [
                ['value' => '1', 'label' => $this->trueLabel],
                ['value' => '0', 'label' => $this->falseLabel],
            ],
        ]);
    }
}
