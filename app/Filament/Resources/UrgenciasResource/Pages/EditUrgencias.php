<?php

namespace App\Filament\Resources\UrgenciasResource\Pages;

use App\Filament\Resources\UrgenciasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUrgencias extends EditRecord
{
    protected static string $resource = UrgenciasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
