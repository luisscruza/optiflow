<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BankAccount extends Model
{
    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    protected function casts(): array
    {
        return [
            'type' => BankAccountType::class,
        ];
    }
}
