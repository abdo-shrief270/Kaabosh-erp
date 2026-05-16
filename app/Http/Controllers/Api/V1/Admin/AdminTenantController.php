<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Admin\Services\TenantManagementService;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateTenantRequest;
use App\Http\Resources\AdminTenantDetailResource;
use App\Http\Resources\AdminTenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminTenantController extends Controller
{
    public function __construct(
        private readonly TenantManagementService $tenantService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminTenantResource::collection(
            $this->tenantService->list([
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_dir' => $request->query('sort_dir', 'desc'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $detail = $this->tenantService->getDetail($tenant);

        return response()->json([
            'data' => [
                'tenant' => new AdminTenantDetailResource($detail['tenant']),
                'subscription' => $detail['subscription'],
                'subscription_history' => $detail['subscription_history'],
                'users_count' => $detail['users_count'],
                'usage' => $detail['usage'],
                'plan_limits' => $detail['plan_limits'],
            ],
        ]);
    }

    public function update(AdminUpdateTenantRequest $request, Tenant $tenant): AdminTenantDetailResource
    {
        return new AdminTenantDetailResource(
            $this->tenantService->update($tenant, $request->validated()),
        );
    }

    public function suspend(Tenant $tenant): AdminTenantResource
    {
        return new AdminTenantResource($this->tenantService->suspend($tenant));
    }

    public function activate(Tenant $tenant): AdminTenantResource
    {
        return new AdminTenantResource($this->tenantService->activate($tenant));
    }

    public function cancel(Tenant $tenant): AdminTenantResource
    {
        return new AdminTenantResource($this->tenantService->cancel($tenant));
    }

    public function impersonate(Tenant $tenant): JsonResponse
    {
        $token = $this->tenantService->impersonate($tenant);

        return response()->json(['data' => ['token' => $token]]);
    }
}
