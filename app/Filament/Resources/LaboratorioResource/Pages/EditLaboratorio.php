<?php

namespace App\Filament\Resources\LaboratorioResource\Pages;

use App\Filament\Resources\LaboratorioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaboratorio extends EditRecord
{
    protected static string $resource = LaboratorioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
