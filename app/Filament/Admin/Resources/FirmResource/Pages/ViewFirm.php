<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FirmResource\Pages;

use App\Filament\Admin\Resources\FirmResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFirm extends ViewRecord
{
    protected static string $resource = FirmResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
