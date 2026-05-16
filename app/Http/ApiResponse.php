<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API response builder.
 * Ensures consistent response envelope across all endpoints.
 *
 * Success: { "data": ..., "message": "..." }
 * Error:   { "error": "code", "message": "...", "errors": {...} }
 */
class ApiResponse
{
    /**
     * Success response with data.
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Created response (201).
     */
    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message ?? __('messages.success.created'), 201);
    }

    /**
     * Error response.
     */
    public static function error(
        string $code,
        string $message,
        int $status = 400,
        array $errors = [],
    ): JsonResponse {
        $response = [
            'error' => $code,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Validation error (422).
     */
    public static function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return self::error(
            code: 'validation_error',
            message: $message ?? __('messages.error.validation'),
            status: 422,
            errors: $errors,
        );
    }

    /**
     * Not found (404).
     */
    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error(
            code: 'not_found',
            message: $message ?? __('messages.error.not_found'),
            status: 404,
        );
    }

    /**
     * Unauthorized (401).
     */
    public static function unauthorized(?string $message = null): JsonResponse
    {
        return self::error(
            code: 'unauthorized',
            message: $message ?? __('messages.error.unauthorized'),
            status: 401,
        );
    }

    /**
     * Forbidden (403).
     */
    public static function forbidden(?string $message = null): JsonResponse
    {
        return self::error(
            code: 'forbidden',
            message: $message ?? __('messages.error.forbidden'),
            status: 403,
        );
    }

    /**
     * Server error (500).
     */
    public static function serverError(?string $message = null): JsonResponse
    {
        return self::error(
            code: 'server_error',
            message: $message ?? __('messages.error.server_error'),
            status: 500,
        );
    }

    /**
     * Too many requests (429).
     */
    public static function tooManyRequests(?string $message = null): JsonResponse
    {
        return self::error(
            code: 'too_many_requests',
            message: $message ?? __('messages.error.too_many_requests'),
            status: 429,
        );
    }
}
