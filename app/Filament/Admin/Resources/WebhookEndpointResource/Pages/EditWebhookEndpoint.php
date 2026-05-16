<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WebhookEndpointResource\Pages;

use App\Filament\Admin\Resources\WebhookEndpointResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebhookEndpoint extends EditRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
