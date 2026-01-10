<?php

declare(strict_types=1);

namespace App\Tables\Columns;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

final class DateColumn extends Column
{
    protected string $format = 'd/m/Y';

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getValue(Model $record): mixed
    {
        $value = data_get($record, $this->name);

        if ($value && $this->formatUsing === null) {
            return $value instanceof DateTimeInterface
                ? $value->format($this->format)
                : date($this->format, strtotime($value));
        }

        return parent::getValue($record);
    }

    public function getType(): string
    {
        return 'date';
    }
}
