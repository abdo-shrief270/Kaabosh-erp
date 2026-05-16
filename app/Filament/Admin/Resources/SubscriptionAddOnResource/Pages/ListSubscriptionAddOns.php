<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SubscriptionAddOnResource\Pages;

use App\Filament\Admin\Resources\SubscriptionAddOnResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionAddOns extends ListRecords
{
    protected static string $resource = SubscriptionAddOnResource::class;
}
