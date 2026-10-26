<?php

namespace MujahidAbbas\LaravelUserTags\Exceptions;

use Exception;

class TagNotOwnedException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'The given tag belongs to another user.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
