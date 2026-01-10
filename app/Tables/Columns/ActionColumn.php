<?php

declare(strict_types=1);

namespace App\Tables\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;

final class ActionColumn extends Column
{
    protected array $actions = [];

    protected ?Closure $actionsUsing = null;

    public function __construct(?string $label = 'Acciones')
    {
        parent::__construct('actions', $label);
        $this->align = 'right';
    }

    public static function make(string $name = 'Actions', ?string $label = 'Acciones'): static
    {
        return new self($label);
    }

    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    public function actionsUsing(Closure $callback): static
    {
        $this->actionsUsing = $callback;

        return $this;
    }

    public function getValue(Model $record): mixed
    {
        if ($this->actionsUsing) {
            return call_user_func($this->actionsUsing, $record);
        }

        return array_map(fn ($action) => $action->toArray($record), $this->actions);
    }

    public function getType(): string
    {
        return 'actions';
    }
}
