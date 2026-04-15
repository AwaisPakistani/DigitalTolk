<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\{DB, Hash, Log};
use App\Models\User;
use App\Contracts\AuthServiceInterface;
use App\Exceptions\Auth\{AccountLockedException, InvalidCredentialsException};
use App\Http\Requests\LoginRequest;
use App\Events\{FailedLoginAttempt, UserLoggedIn};
class AuthenticationService
{
   public function __construct(
        private LoginAttemptService $loginAttemptService,
        private TokenService $tokenService
    ) {}

    /**
     * Authenticate user and generate access token
     */
    public function authenticate(LoginRequest $request): array
    {
        // Check if account is locked due to too many attempts
        if ($this->loginAttemptService->isLocked($request)) {
            $remainingSeconds = $this->loginAttemptService->getLockoutRemaining($request);
            throw new AccountLockedException($remainingSeconds);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Validate credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->handleFailedAttempt($request, $user);
            throw new InvalidCredentialsException();
        }

        // Check if user account is active
        if (!$user->is_active) {
            Log::warning('Attempt to login to inactive account', [
                'email' => $user->email,
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            throw new InvalidCredentialsException();
        }

        // Process successful login
        return $this->processSuccessfulLogin($request, $user);
    }

    /**
     * Process successful login
     */
    private function processSuccessfulLogin(LoginRequest $request, User $user): array
    {
        return DB::transaction(function () use ($request, $user) {
            // Clear failed attempts
            $this->loginAttemptService->clearAttempts($request);

            // Revoke old tokens (more than 30 days old)
            $this->tokenService->revokeOldTokens($user, 30);

            // Create new token
            $deviceName = $request->device_name;
            $remember = $request->remember_me ?? false;
            $token = $this->tokenService->createToken($user, $deviceName,    $remember);

            // Update last login information
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'login_count' => DB::raw('login_count + 1')
            ]);

            // Dispatch event for logging/notifications
            event(new UserLoggedIn($user, $request->ip(), $deviceName));

            // Prepare response data
            return [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $this->formatUserData($user),
                'device' => $deviceName,
                'login_time' => now()->toIso8601String()
            ];
        });
    }

    /**
     * Handle failed login attempt
     */
    private function handleFailedAttempt(LoginRequest $request, ?User $user): void
    {
        $this->loginAttemptService->recordFailedAttempt($request);

        $remainingAttempts = $this->loginAttemptService->getRemainingAttempts($request);

        event(new FailedLoginAttempt(
            $request->email,
            $request->ip(),
            $remainingAttempts
        ));

        Log::notice('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'remaining_attempts' => $remainingAttempts
        ]);
    }

    /**
     * Format user data for API response
     */
    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String()
        ];
    }

    /**
     * Revoke all user tokens (logout)
     */
    public function logout(User $user): bool
    {
        try {
            // Revoke current access token
            $user->currentAccessToken()->delete();

            // Log logout event
            Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_id' => $user->currentAccessToken()?->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate token and get user
     */
    public function validateToken(string $token): ?User
    {
        try {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if (!$tokenModel || !$tokenModel->tokenable) {
                return null;
            }

            $user = $tokenModel->tokenable;

            // Check if token is expired (if using expiration)
            if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
                $tokenModel->delete();
                return null;
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('Token validation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
