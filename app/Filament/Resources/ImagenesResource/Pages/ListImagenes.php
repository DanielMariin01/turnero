<?php

namespace App\Filament\Resources\ImagenesResource\Pages;

use App\Filament\Resources\ImagenesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImagenes extends ListRecords
{
    protected static string $resource = ImagenesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
