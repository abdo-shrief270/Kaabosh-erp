<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Services\AddOnService;
use App\Http\Controllers\Controller;
use App\Http\Resources\AddOnResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public-ish read-only catalog of available add-ons. Anyone with a tenant
 * context can browse — purchase still requires manage_subscription.
 */
class AddOnCatalogController extends Controller
{
    public function __construct(
        private readonly AddOnService $addOnService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return AddOnResource::collection($this->addOnService->catalog());
    }
}
