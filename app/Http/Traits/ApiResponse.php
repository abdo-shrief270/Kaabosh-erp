<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'data' => $data,
        ], fn ($v) => $v !== null), $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function deleted(string $message = 'Deleted successfully'): JsonResponse
    {
        return response()->json(['message' => $message]);
    }
}
