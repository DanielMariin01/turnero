<?php

namespace App\Filament\Resources\PedirCitaResource\Pages;

use App\Filament\Resources\PedirCitaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedirCitas extends ListRecords
{
    protected static string $resource = PedirCitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
