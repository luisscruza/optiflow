<?php

declare(strict_types=1);

namespace App\Services\Automation\Support;

final readonly class NodeResult
{
    /**
     * @param  array<string, mixed>  $output
     */
    private function __construct(
        public bool $success,
        public array $output,
    ) {}

    /**
     * @param  array<string, mixed>  $output
     */
    public static function success(array $output = []): self
    {
        return new self(true, $output);
    }

    /**
     * @param  array<string, mixed>  $output
     */
    public static function failure(array $output = []): self
    {
        return new self(false, $output);
    }
}
