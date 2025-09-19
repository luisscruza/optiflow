<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Document;

final readonly class InvoiceResult
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public ?Document $invoice = null,
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
