<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ActionValidationException extends ActionException
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(array $errors, string $message = 'Action validation failed.')
    {
        parent::__construct($message, $errors);
    }
}
