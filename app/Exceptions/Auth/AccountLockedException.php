<?php

namespace App\Exceptions\Auth;

use Exception;
use Illuminate\Http\JsonResponse;
class AccountLockedException extends Exception
{
    private int $remainingSeconds;

    public function __construct(int $remainingSeconds)
    {
        $this->remainingSeconds = $remainingSeconds;
        parent::__construct('Account temporarily locked due to too many failed attempts', 403);
    }
    /**
     * Render the exception with lockout information
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'ACCOUNT_LOCKED',
            'retry_after_seconds' => $this->remainingSeconds,
            'retry_after_minutes' => ceil($this->remainingSeconds / 60)
        ], $this->getCode());
    }
}
