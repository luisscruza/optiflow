<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class EasyFactuException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message = 'Error de EasyFactu.',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly array $errors = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
}
