<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ActionException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(string $message = 'Action failed.', protected array $errors = [])
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
