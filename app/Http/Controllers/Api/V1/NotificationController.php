<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notification\Services\NotificationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->notificationService->list([
            'type' => $request->query('type'),
            'is_read' => $request->query('is_read') !== null
                ? filter_var($request->query('is_read'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'per_page' => min((int) ($request->query('per_page', 15)), 100),
        ]);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    public function markAsRead(string $notification): NotificationResource
    {
        $updated = $this->notificationService->markAsRead($notification);

        return new NotificationResource($updated);
    }

    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead();

        return response()->json([
            'count' => $count,
        ]);
    }

    public function destroy(string $notification): JsonResponse
    {
        $this->notificationService->delete($notification);

        return response()->json([
            'message' => 'Notification deleted successfully.',
        ]);
    }
}
