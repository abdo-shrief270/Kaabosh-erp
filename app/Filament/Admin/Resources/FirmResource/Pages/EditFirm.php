<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FirmResource\Pages;

use App\Filament\Admin\Resources\FirmResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFirm extends EditRecord
{
    protected static string $resource = FirmResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}
