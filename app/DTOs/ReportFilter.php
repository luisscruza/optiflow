<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ReportFilter
{
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public mixed $default = null,
        public ?array $options = null,
        public ?string $placeholder = null,
    ) {}

    /**
     * Convert to array for frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'default' => $this->default,
            'options' => $this->options,
            'placeholder' => $this->placeholder,
        ];
    }
}
