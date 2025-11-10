<?php

namespace App\Filament\Resources\ConsultaExternaResource\Pages;

use App\Filament\Resources\ConsultaExternaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultaExterna extends EditRecord
{
    protected static string $resource = ConsultaExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
