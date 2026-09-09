<?php

namespace App\Filament\Resources\ConsultaMedicaUrgenciasResource\Pages;

use App\Filament\Resources\ConsultaMedicaUrgenciasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultaMedicaUrgencias extends EditRecord
{
    protected static string $resource = ConsultaMedicaUrgenciasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
