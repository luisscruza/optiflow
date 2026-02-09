<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ReportableActionException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(string $message = 'Ha ocurrido un error.', protected array $errors = [])
    {
        parent::__construct($message);
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
