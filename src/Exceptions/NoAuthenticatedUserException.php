<?php

namespace MujahidAbbas\LaravelUserTags\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoAuthenticatedUserException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'No authenticated user found. Either authenticate a user or pass one explicitly.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * This follows Laravel's renderable exception pattern for clean API error responses.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'unauthenticated',
            'message' => $this->getMessage(),
        ], 401);
    }
}
