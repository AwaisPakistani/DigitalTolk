<?php

namespace App\Services\Auth;


use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

class LoginAttemptService
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 15;
    private const LOCKOUT_MINUTES = 30;

    public function __construct(
        private RateLimiter $rateLimiter
    ) {}
      /**
     * Get the rate limit key for the request
     */
    private function throttleKey(Request $request): string
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }
    /**
     * Check if the login attempts are locked
     */
    public function isLocked(Request $request): bool
    {
        $key = $this->throttleKey($request);

        // Check if the user has exceeded the lockout threshold
        if ($this->rateLimiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            // Check if lockout has been extended for repeated offenses
            $hitCount = $this->rateLimiter->attempts($key);

            if ($hitCount > self::MAX_ATTEMPTS * 2) {
                $this->rateLimiter->availableIn($key);
                return true;
            }

            return true;
        }

        return false;
    }

    /**
     * Get the remaining lockout time in seconds
     */
    public function getLockoutRemaining(Request $request): int
    {
        $key = $this->throttleKey($request);
        $availableAt = $this->rateLimiter->availableIn($key);

        // If availableIn returns 0, it means we need to calculate custom lockout
        if ($availableAt === 0) {
            $attempts = $this->rateLimiter->attempts($key);
            $excessAttempts = $attempts - self::MAX_ATTEMPTS;
            $lockoutMinutes = self::LOCKOUT_MINUTES * (1 + floor($excessAttempts / self::MAX_ATTEMPTS));
            return min($lockoutMinutes * 60, 3600); // Max 1 hour lockout
        }

        return $availableAt;
    }

    /**
     * Record a failed login attempt
     */
    public function recordFailedAttempt(Request $request): void
    {
        $key = $this->throttleKey($request);
        $this->rateLimiter->hit($key, self::DECAY_MINUTES * 60);
    }

    /**
     * Clear failed login attempts on successful login
     */
    public function clearAttempts(Request $request): void
    {
        $key = $this->throttleKey($request);
        $this->rateLimiter->clear($key);
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(Request $request): int
    {
        $key = $this->throttleKey($request);
        $attempts = $this->rateLimiter->attempts($key);

        return max(0, self::MAX_ATTEMPTS - $attempts);
    }
}
