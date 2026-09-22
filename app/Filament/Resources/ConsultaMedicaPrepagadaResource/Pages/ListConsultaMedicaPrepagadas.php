<?php

namespace App\Filament\Resources\ConsultaMedicaPrepagadaResource\Pages;

use App\Filament\Resources\ConsultaMedicaPrepagadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultaMedicaPrepagadas extends ListRecords
{
    protected static string $resource = ConsultaMedicaPrepagadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
