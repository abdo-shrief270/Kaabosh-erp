<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * Cross-domain audit log viewer. Wraps Spatie's `activity_log` table with
 * filters useful in the SPA: subject_type (Task, Invoice, …), causer
 * (user), event verb, date range. Returns paginated rows ordered most
 * recent first, with the causer's name pre-joined so the SPA doesn't
 * need a second round-trip per row.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_activity_log'), 403);

        $perPage = (int) min(max($request->integer('per_page', 50), 1), 200);

        $query = Activity::query()
            ->select([
                'activity_log.id',
                'activity_log.log_name',
                'activity_log.description',
                'activity_log.event',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'activity_log.causer_type',
                'activity_log.causer_id',
                'activity_log.properties',
                'activity_log.created_at',
                'users.name as causer_name',
                'users.email as causer_email',
            ])
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'activity_log.causer_id')
                    ->whereRaw("activity_log.causer_type = 'App\\\\Models\\\\User'");
            })
            ->orderByDesc('activity_log.id');

        // Filters
        if ($v = $request->string('subject_type')->toString()) {
            $query->where('activity_log.subject_type', $v);
        }
        if ($v = $request->integer('subject_id')) {
            $query->where('activity_log.subject_id', $v);
        }
        if ($v = $request->integer('causer_id')) {
            $query->where('activity_log.causer_id', $v);
        }
        if ($v = $request->string('event')->toString()) {
            $query->where('activity_log.event', $v);
        }
        if ($v = $request->string('log_name')->toString()) {
            $query->where('activity_log.log_name', $v);
        }
        if ($v = $request->string('from')->toString()) {
            $query->where('activity_log.created_at', '>=', $v);
        }
        if ($v = $request->string('to')->toString()) {
            $query->where('activity_log.created_at', '<=', $v);
        }
        if ($v = $request->string('q')->toString()) {
            $query->where('activity_log.description', 'ilike', "%$v%");
        }

        // Tenant scoping — activity rows don't carry tenant_id, but we can
        // hide rows whose causer belongs to a different tenant (the common
        // case). System-generated rows (causer_id NULL) stay visible.
        $tenantId = (int) app('tenant.id');
        if ($tenantId > 0) {
            $query->where(function ($q) use ($tenantId) {
                $q->whereNull('activity_log.causer_id')
                    ->orWhereExists(function ($sub) use ($tenantId) {
                        $sub->select(DB::raw(1))
                            ->from('users')
                            ->whereColumn('users.id', 'activity_log.causer_id')
                            ->where('users.tenant_id', $tenantId);
                    });
            });
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Distinct subject types in the log — powers the filter dropdown without
     * a hardcoded enum.
     */
    public function subjectTypes(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_activity_log'), 403);

        $types = Activity::query()
            ->select('subject_type')
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderBy('subject_type')
            ->pluck('subject_type');

        return response()->json(['data' => $types->map(fn ($t) => [
            'value' => $t,
            'label' => class_basename((string) $t),
        ])]);
    }
}
