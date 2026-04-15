<?php

namespace App\Contracts;
use App\Models\User;
use App\Http\Requests\Api\LoginRequest;
interface AuthServiceInterface
{
    /**
     * Authenticate user and generate access token
     */
    public function authenticate(LoginRequest $request): array;

    /**
     * Revoke all user tokens
     */
    public function logout(User $user): bool;

    /**
     * Validate token and get user
     */
    public function validateToken(string $token): ?User;
}
