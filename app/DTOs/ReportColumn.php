<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ReportColumn
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type = 'text',
        public bool $sortable = false,
        public ?string $align = null,
        public ?string $href = null,
    ) {}

    /**
     * Convert to array for frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
            'align' => $this->align,
            'href' => $this->href,
        ];
    }
}
