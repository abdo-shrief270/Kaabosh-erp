<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AddOnResource\Pages;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use App\Filament\Admin\Resources\AddOnResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAddOn extends CreateRecord
{
    protected static string $resource = AddOnResource::class;

    /**
     * Coerce form input into the canonical shape per add-on type:
     *  - Credit packs are always Once-cycle and earn no recurring price.
     *  - Boost add-ons keep the `boost` JSON; non-boost types null it out so
     *    a stale value doesn't accidentally apply.
     *  - Feature add-ons keep `feature_slug`; others clear it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AddOnResource::normalizeFormPayload($data);
    }
}
