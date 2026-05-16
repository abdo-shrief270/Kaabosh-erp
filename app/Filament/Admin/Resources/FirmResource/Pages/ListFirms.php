<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FirmResource\Pages;

use App\Filament\Admin\Resources\FirmResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFirms extends ListRecords
{
    protected static string $resource = FirmResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
