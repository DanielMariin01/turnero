<?php

namespace App\Filament\Resources\PedirCitaResource\Pages;

use App\Filament\Resources\PedirCitaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPedirCita extends EditRecord
{
    protected static string $resource = PedirCitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
