<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Admin\Services\PlatformSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlatformSettingsRequest;
use App\Http\Resources\PlatformSettingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminPlatformSettingsController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settingsService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return PlatformSettingResource::collection(
            $this->settingsService->getAll(),
        );
    }

    public function update(UpdatePlatformSettingsRequest $request): JsonResponse
    {
        $settings = collect($request->validated('settings'))
            ->pluck('value', 'key')
            ->toArray();

        $this->settingsService->setMany($settings);

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
