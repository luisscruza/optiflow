<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Quotation;

final readonly class QuotationResult
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public ?Quotation $quotation = null,
        public ?string $error = null,
    ) {}

    /**
     * Whether the operation was successful.
     */
    public function isError(): bool
    {
        return $this->error !== null;
    }
}
