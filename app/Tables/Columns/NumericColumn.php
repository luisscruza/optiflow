<?php

declare(strict_types=1);

namespace App\Tables\Columns;

final class NumericColumn extends Column
{
    public function getType(): string
    {
        return 'number';
    }
}
