<?php

namespace App\Filament\Resources\ReporteResource\Pages;

use App\Filament\Resources\ReporteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReporte extends EditRecord
{
    protected static string $resource = ReporteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
