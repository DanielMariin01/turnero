<?php

namespace App\Filament\Resources\ConsultorioUrgenciasResource\Pages;

use App\Filament\Resources\ConsultorioUrgenciasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultorioUrgencias extends EditRecord
{
    protected static string $resource = ConsultorioUrgenciasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
