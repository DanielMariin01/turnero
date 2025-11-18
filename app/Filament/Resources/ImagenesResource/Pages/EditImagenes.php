<?php

namespace App\Filament\Resources\ImagenesResource\Pages;

use App\Filament\Resources\ImagenesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImagenes extends EditRecord
{
    protected static string $resource = ImagenesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
