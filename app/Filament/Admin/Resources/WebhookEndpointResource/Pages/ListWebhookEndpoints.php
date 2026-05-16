<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WebhookEndpointResource\Pages;

use App\Filament\Admin\Resources\WebhookEndpointResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebhookEndpoints extends ListRecords
{
    protected static string $resource = WebhookEndpointResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
