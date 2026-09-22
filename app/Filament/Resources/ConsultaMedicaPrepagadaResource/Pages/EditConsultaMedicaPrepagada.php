<?php

namespace App\Filament\Resources\ConsultaMedicaPrepagadaResource\Pages;

use App\Filament\Resources\ConsultaMedicaPrepagadaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultaMedicaPrepagada extends EditRecord
{
    protected static string $resource = ConsultaMedicaPrepagadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
