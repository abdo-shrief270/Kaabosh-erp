<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query()
            ->with('causer:id,name,email')
            ->when($request->query('tenant_id'), fn ($q, $tenantId) => $q->where('properties->tenant_id', $tenantId))
            ->when($request->query('causer_id'), fn ($q, $id) => $q->where('causer_id', $id))
            ->when($request->query('log_name'), fn ($q, $name) => $q->where('log_name', $name))
            ->orderByDesc('created_at')
            ->paginate(min((int) ($request->query('per_page', 20)), 100));

        return response()->json($query);
    }

    public function forTenant(int $tenantId): JsonResponse
    {
        $activities = Activity::query()
            ->with('causer:id,name,email')
            ->whereJsonContains('properties->attributes->tenant_id', $tenantId)
            ->orWhere(function ($q) use ($tenantId) {
                $q->whereHasMorph('causer', [User::class], function ($q) use ($tenantId) {
                    $q->withoutGlobalScopes()->where('tenant_id', $tenantId);
                });
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'subject_type' => class_basename($a->subject_type ?? ''),
                'causer_name' => $a->causer?->name,
                'properties' => $a->properties,
                'created_at' => $a->created_at->toISOString(),
            ]);

        return response()->json(['data' => $activities]);
    }
}
