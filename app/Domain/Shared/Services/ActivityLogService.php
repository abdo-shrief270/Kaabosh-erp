<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Client\Models\Client;
use App\Domain\Document\Models\Document;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Webhook\Models\WebhookEndpoint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

/**
 * Activity log reader scoped to the current Company (tenant). Trimmed for
 * ERP — only references models that exist in this product. Accounting
 * artefacts (Invoice, JournalEntry, BlogPost, CmsPage, EtaDocument) used to
 * be mapped here; they no longer exist after the muhasebi → kaabosh-erp split.
 */
class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = (int) app('tenant.id');

        $userIds = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->pluck('id');

        $query = Activity::query()
            ->with('causer:id,name,email')
            ->whereIn('causer_id', $userIds)
            ->where('causer_type', (new User)->getMorphClass());

        if (isset($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }
        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $this->resolveSubjectType($filters['subject_type']));
        }
        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name,
                'email' => $activity->causer->email,
            ] : null,
            'properties' => $activity->properties,
            'created_at' => $activity->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(?string $from = null, ?string $to = null): array
    {
        return [
            'total_activities' => 0,
            'top_users' => [],
            'top_actions' => [],
            'daily_activity' => [],
        ];
    }

    private function resolveSubjectType(string $shortName): string
    {
        $map = [
            'Client' => Client::class,
            'Document' => Document::class,
            'Tenant' => Tenant::class,
            'User' => User::class,
            'Subscription' => Subscription::class,
            'WebhookEndpoint' => WebhookEndpoint::class,
        ];

        return $map[$shortName] ?? $shortName;
    }
}
