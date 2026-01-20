<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ActionNotFoundException extends ActionException
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(string $message = 'Resource not found.', array $errors = [])
    {
        parent::__construct($message, $errors);
    }
}
