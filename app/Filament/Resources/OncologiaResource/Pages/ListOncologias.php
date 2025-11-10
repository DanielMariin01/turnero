<?php

namespace App\Filament\Resources\OncologiaResource\Pages;

use App\Filament\Resources\OncologiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOncologias extends ListRecords
{
    protected static string $resource = OncologiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
