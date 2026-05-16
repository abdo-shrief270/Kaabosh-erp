<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notification\Models\AlertRule;
use App\Domain\Notification\Services\AlertEngineService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AlertRuleController extends Controller
{
    public function __construct(
        private readonly AlertEngineService $alertEngine,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->alertEngine->list([
            'is_active' => $request->query('is_active') !== null
                ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'metric' => $request->query('metric'),
            'per_page' => min((int) ($request->query('per_page', 15)), 100),
        ]);

        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'metric' => ['required', 'string', 'max:50', Rule::in([
                'dso', 'ar_total', 'ap_total', 'cash_balance',
                'overdue_invoices_count', 'overdue_bills_count',
                'vat_due_date', 'budget_utilization', 'collection_rate',
            ])],
            'operator' => ['required', 'string', 'max:10', Rule::in(['gt', 'gte', 'lt', 'lte', 'eq'])],
            'threshold' => ['required', 'numeric', 'between:-99999999999.99,99999999999.99'],
            'check_frequency' => ['sometimes', 'string', 'max:20', Rule::in(['hourly', 'daily', 'weekly'])],
            'notification_channels' => ['required', 'array', 'min:1'],
            'notification_channels.*' => ['string', Rule::in(['email', 'push', 'in_app'])],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['string'],
            'cooldown_hours' => ['sometimes', 'integer', 'min:1', 'max:720'],
        ]);

        $data['created_by'] = $request->user()?->id;

        $rule = $this->alertEngine->create($data);

        return response()->json([
            'data' => $rule,
            'message' => 'Alert rule created.',
        ], Response::HTTP_CREATED);
    }

    public function show(AlertRule $alertRule): JsonResponse
    {
        return response()->json([
            'data' => $alertRule->load('creator:id,name'),
        ]);
    }

    public function update(Request $request, AlertRule $alertRule): JsonResponse
    {
        $data = $request->validate([
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'metric' => ['sometimes', 'string', 'max:50', Rule::in([
                'dso', 'ar_total', 'ap_total', 'cash_balance',
                'overdue_invoices_count', 'overdue_bills_count',
                'vat_due_date', 'budget_utilization', 'collection_rate',
            ])],
            'operator' => ['sometimes', 'string', 'max:10', Rule::in(['gt', 'gte', 'lt', 'lte', 'eq'])],
            'threshold' => ['sometimes', 'numeric', 'between:-99999999999.99,99999999999.99'],
            'check_frequency' => ['sometimes', 'string', 'max:20', Rule::in(['hourly', 'daily', 'weekly'])],
            'notification_channels' => ['sometimes', 'array', 'min:1'],
            'notification_channels.*' => ['string', Rule::in(['email', 'push', 'in_app'])],
            'recipients' => ['sometimes', 'array', 'min:1'],
            'recipients.*' => ['string'],
            'cooldown_hours' => ['sometimes', 'integer', 'min:1', 'max:720'],
        ]);

        $rule = $this->alertEngine->update($alertRule, $data);

        return response()->json([
            'data' => $rule,
            'message' => 'Alert rule updated.',
        ]);
    }

    public function destroy(AlertRule $alertRule): JsonResponse
    {
        $this->alertEngine->delete($alertRule);

        return response()->json(['message' => 'Alert rule deleted.']);
    }

    public function toggle(AlertRule $alertRule): JsonResponse
    {
        $rule = $this->alertEngine->toggle($alertRule);

        return response()->json([
            'data' => $rule,
            'message' => $rule->is_active ? 'Alert rule activated.' : 'Alert rule deactivated.',
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $data = $this->alertEngine->history([
            'alert_rule_id' => $request->query('alert_rule_id'),
            'per_page' => min((int) ($request->query('per_page', 15)), 100),
        ]);

        return response()->json($data);
    }
}
