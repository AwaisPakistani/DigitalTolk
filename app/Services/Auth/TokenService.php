<?php

namespace App\Services\Auth;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
class TokenService
{
    /**
     * Create a new access token for the user
     */
    public function createToken(User $user, string $deviceName, bool $remember = false): NewAccessToken
    {
        // Create token with appropriate abilities
        $abilities = $this->getAbilitiesForToken($remember);

        // Generate a unique token name
        $tokenName = $this->generateTokenName($deviceName);

        // Create and return the token
        return $user->createToken($tokenName, $abilities);
    }

    /**
     * Get token abilities based on remember me flag
     */
    private function getAbilitiesForToken(bool $remember): array
    {
        $abilities = ['api:access'];

        if ($remember) {
            $abilities[] = 'api:extended';
        }

        return $abilities;
    }

    /**
     * Generate a unique token name
     */
    private function generateTokenName(string $deviceName): string
    {
        $hash = Str::random(8);
        $timestamp = now()->timestamp;

        return "{$deviceName}_{$timestamp}_{$hash}";
    }

    /**
     * Revoke all user tokens except current
     */
    public function revokeAllTokensExcept(User $user, string $currentTokenId): int
    {
        return $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();
    }

    /**
     * Revoke tokens older than specified days
     */
    public function revokeOldTokens(User $user, int $daysOld = 30): int
    {
        return $user->tokens()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get token expiration time in minutes
     */
    public function getTokenExpiration(User $user, string $tokenId): ?int
    {
        $token = $user->tokens()->find($tokenId);

        if ($token && $token->expires_at) {
            return now()->diffInMinutes($token->expires_at);
        }

        return null;
    }
}
