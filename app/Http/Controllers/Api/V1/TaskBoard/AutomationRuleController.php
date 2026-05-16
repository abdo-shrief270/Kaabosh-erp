<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\AutomationRule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationRuleController extends Controller
{
    public function index(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $query = AutomationRule::query()->orderBy('name');
        if ($boardId = $request->integer('board_id')) {
            $query->where(fn ($q) => $q->where('board_id', $boardId)->orWhereNull('board_id'));
        }

        return JsonResource::collection($query->get());
    }

    public function store(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $this->validatedPayload($request);
        $data['tenant_id'] = (int) app('tenant.id');
        $data['created_by'] = $request->user()?->id;

        $rule = AutomationRule::create($data);

        return JsonResource::make($rule);
    }

    public function update(Request $request, AutomationRule $automationRule): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $automationRule->update($this->validatedPayload($request));

        return JsonResource::make($automationRule->fresh());
    }

    public function toggle(Request $request, AutomationRule $automationRule): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $automationRule->forceFill(['is_active' => ! $automationRule->is_active])->save();

        return JsonResource::make($automationRule);
    }

    public function destroy(Request $request, AutomationRule $automationRule)
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $automationRule->delete();
        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request): array
    {
        $tenantId = (int) app('tenant.id');

        return $request->validate([
            'board_id' => ['nullable', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'trigger_type' => ['required', 'in:task_created,task_moved,task_completed,task_assigned'],
            'trigger_config' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', 'in:priority,task_type_id,tag_id,assignee_id,primary_assignee_id'],
            'conditions.*.op' => ['required_with:conditions', 'in:is,not,in,not_in,has,has_not'],
            'conditions.*.value' => ['present'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'in:move_to_column,assign_to,add_tag,remove_tag,set_priority,post_comment,add_to_sprint'],
            'actions.*.payload' => ['required', 'array'],
        ]);
    }
}
