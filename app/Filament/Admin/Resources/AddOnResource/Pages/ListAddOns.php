<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AddOnResource\Pages;

use App\Filament\Admin\Resources\AddOnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAddOns extends ListRecords
{
    protected static string $resource = AddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
