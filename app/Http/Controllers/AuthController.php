<?php

namespace App\Http\Controllers;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Cache, DB, Hash, Log, RateLimiter};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\AuthenticationService;
use App\Exceptions\Auth\{AccountLockedException, InvalidCredentialsException};
use App\Models\User;
class AuthController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {
        //
    }

    /**
     * Handle user login and issue access token
     *
     * @group Authentication
     * @bodyParam email string required User's email address
     * @bodyParam password string required User's password
     * @bodyParam device_name string optional Device identifier
     * @bodyParam remember_me boolean optional Remember me flag
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // dd($request->all());
        try {
            // Execute the authentication process
            $authData = $this->authService->authenticate($request);

            // Return successful response with token and user data
            return ApiResponse::success(
                $authData,
                'Login successful',
                200
            );

        } catch (InvalidCredentialsException $e) {
            return $e->render();

        } catch (AccountLockedException $e) {
            return $e->render();

        } catch (\Exception $e) {
            // Log unexpected errors
            \Illuminate\Support\Facades\Log::error('Unexpected login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            return ApiResponse::error(
                'An unexpected error occurred. Please try again later.',
                null,
                500,
                'SERVER_ERROR'
            );
        }
    }

    /**
     * Handle user logout (revoke current token)
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized('User not authenticated');
        }

        $success = $this->authService->logout($user);

        if ($success) {
            return ApiResponse::success(
                null,
                'Successfully logged out',
                200
            );
        }

        return ApiResponse::error(
            'Failed to logout. Please try again.',
            null,
            500,
            'LOGOUT_FAILED'
        );
    }

    /**
     * Get the authenticated user's profile
     *
     * @authenticated
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized();
        }

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'last_login_ip' => $user->last_login_ip,
            'login_count' => $user->login_count,
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String()
        ], 'User profile retrieved successfully');
    }

    /**
     * Revoke all tokens (logout from all devices)
     *
     * @authenticated
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all tokens except current
        $revokedCount = $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id)
            ->delete();

        \Illuminate\Support\Facades\Log::info('User logged out from all devices', [
            'user_id' => $user->id,
            'revoked_tokens' => $revokedCount,
            'kept_token' => $user->currentAccessToken()->id
        ]);

        return ApiResponse::success(
            ['revoked_devices_count' => $revokedCount],
            "Logged out from {$revokedCount} other device(s)",
            200
        );
    }

    /**
     * Refresh the current token (create new, delete old)
     *
     * @authenticated
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $oldToken = $user->currentAccessToken();

        // Create new token
        $deviceName = $oldToken->name ?? 'refreshed_token';
        $newToken = $user->createToken($deviceName, $oldToken->abilities);

        // Delete old token
        $oldToken->delete();

        return ApiResponse::success([
            'access_token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'refresh_time' => now()->toIso8601String()
        ], 'Token refreshed successfully');
    }
}
