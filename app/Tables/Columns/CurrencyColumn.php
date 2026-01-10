<?php

declare(strict_types=1);

namespace App\Tables\Columns;

final class CurrencyColumn extends Column
{
    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->align = 'right';
    }

    public function getType(): string
    {
        return 'currency';
    }
}
