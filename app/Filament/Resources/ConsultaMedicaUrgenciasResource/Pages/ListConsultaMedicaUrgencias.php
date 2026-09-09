<?php

namespace App\Filament\Resources\ConsultaMedicaUrgenciasResource\Pages;

use App\Filament\Resources\ConsultaMedicaUrgenciasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultaMedicaUrgencias extends ListRecords
{
    protected static string $resource = ConsultaMedicaUrgenciasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
