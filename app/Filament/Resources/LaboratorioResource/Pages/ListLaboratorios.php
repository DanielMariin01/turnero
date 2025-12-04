<?php

namespace App\Filament\Resources\LaboratorioResource\Pages;

use App\Filament\Resources\LaboratorioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaboratorios extends ListRecords
{
    protected static string $resource = LaboratorioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
