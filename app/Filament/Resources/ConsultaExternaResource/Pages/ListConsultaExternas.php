<?php

namespace App\Filament\Resources\ConsultaExternaResource\Pages;

use App\Filament\Resources\ConsultaExternaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultaExternas extends ListRecords
{
    protected static string $resource = ConsultaExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
