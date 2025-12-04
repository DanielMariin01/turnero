<?php

namespace App\Filament\Resources\CirugiaResource\Pages;

use App\Filament\Resources\CirugiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCirugias extends ListRecords
{
    protected static string $resource = CirugiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
