<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WebhookEndpointResource\Pages;

use App\Filament\Admin\Resources\WebhookEndpointResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookEndpoint extends CreateRecord
{
    protected static string $resource = WebhookEndpointResource::class;
}
