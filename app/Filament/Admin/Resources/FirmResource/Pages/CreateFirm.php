<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FirmResource\Pages;

use App\Filament\Admin\Resources\FirmResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFirm extends CreateRecord
{
    protected static string $resource = FirmResource::class;
}
