<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notification\Services\PushNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenController extends Controller
{
    public function __construct(
        private readonly PushNotificationService $pushService,
    ) {}

    /**
     * Register a device token for push notifications.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'string', 'in:ios,android,web'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $deviceToken = $this->pushService->registerToken(
            userId: auth()->id(),
            token: $data['token'],
            platform: $data['platform'],
            deviceName: $data['device_name'] ?? null,
        );

        return response()->json([
            'data' => $deviceToken,
            'message' => 'Device registered for push notifications.',
        ], Response::HTTP_CREATED);
    }

    /**
     * List current user's registered devices.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->pushService->listTokens(auth()->id()),
        ]);
    }

    /**
     * Unregister a device token.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $this->pushService->unregisterToken($data['token']);

        return response()->json(['message' => 'Device unregistered.']);
    }
}
