<?php

namespace App\Filament\Resources\ConsultorioResource\Pages;

use App\Filament\Resources\ConsultorioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultorio extends EditRecord
{
    protected static string $resource = ConsultorioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
