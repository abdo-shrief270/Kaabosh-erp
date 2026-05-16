<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AddOnResource\Pages;

use App\Filament\Admin\Resources\AddOnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAddOn extends EditRecord
{
    protected static string $resource = AddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AddOnResource::normalizeFormPayload($data);
    }
}
