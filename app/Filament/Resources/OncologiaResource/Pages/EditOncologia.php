<?php

namespace App\Filament\Resources\OncologiaResource\Pages;

use App\Filament\Resources\OncologiaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOncologia extends EditRecord
{
    protected static string $resource = OncologiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
