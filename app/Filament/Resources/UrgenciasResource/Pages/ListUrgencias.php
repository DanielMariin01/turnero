<?php

namespace App\Filament\Resources\UrgenciasResource\Pages;

use App\Filament\Resources\UrgenciasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUrgencias extends ListRecords
{
    protected static string $resource = UrgenciasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
