<?php

namespace App\Filament\Resources\ConsultorioUrgenciasResource\Pages;

use App\Filament\Resources\ConsultorioUrgenciasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultorioUrgencias extends ListRecords
{
    protected static string $resource = ConsultorioUrgenciasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
