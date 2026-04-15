<?php

namespace App\Http\Responses;
use Illuminate\Http\JsonResponse;
class ApiResponse
{
    /**
     * Send success response
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String()
        ], $code);
    }

    /**
     * Send error response
     */
    public static function error(
        string $message = 'Error occurred',
        mixed $errors = null,
        int $code = 400,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String()
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $code);
    }

    /**
     * Send created response (201)
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    /**
     * Send no content response (204)
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Send unauthorized response (401)
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, null, 401, 'UNAUTHORIZED');
    }

    /**
     * Send forbidden response (403)
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, null, 403, 'FORBIDDEN');
    }

    /**
     * Send not found response (404)
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, null, 404, 'NOT_FOUND');
    }

    /**
     * Send validation error response (422)
     */
    public static function validationError(mixed $errors): JsonResponse
    {
        return self::error('Validation failed', $errors, 422, 'VALIDATION_ERROR');
    }
}
