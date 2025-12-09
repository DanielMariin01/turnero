<?php

namespace App\Filament\Resources\HistorialResource\Pages;

use App\Filament\Resources\HistorialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHistorials extends ListRecords
{
    protected static string $resource = HistorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
