<?php

namespace App\Filament\Resources\HistorialResource\Pages;

use App\Filament\Resources\HistorialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHistorial extends EditRecord
{
    protected static string $resource = HistorialResource::class;

    protected ?string $heading = 'Editar Turno';

    protected function getHeaderActions(): array
    {
        return [
         
        ];
    }
    public function getHeading(): string
{
    return 'Editar Turno #' .' '. $this->record->numero_turno;
}


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Opcional: También puedes agregar una notificación de éxito personalizada
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Turno actualizado correctamente';
    }



}
