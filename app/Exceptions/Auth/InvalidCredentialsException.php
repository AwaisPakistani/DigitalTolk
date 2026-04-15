<?php

namespace App\Exceptions\Auth;

use Exception;
use Illuminate\Http\JsonResponse;
final class InvalidCredentialsException extends Exception
{
    protected $message = 'Invalid email or password';
    protected $code = 401;

    /**
     * Render the exception into an HTTP response
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'INVALID_CREDENTIALS'
        ], $this->getCode());
    }
}
