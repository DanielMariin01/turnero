<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultorioResource\Pages;
use App\Filament\Resources\ConsultorioResource\RelationManagers;
use App\Models\Consultorio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsultorioResource extends Resource
{
    protected static ?string $model = Consultorio::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';
    protected static ?string $navigationGroup = 'Administración';



    //permisos para ver recursos 
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']) ?? false;
    }




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Forms\Components\Section::make('Información del modulo')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(250),
                    Forms\Components\TextInput::make('ubicacion')
                        ->label('Ubicacion')
                        ->maxLength(250),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                       Tables\Columns\TextColumn::make('nombre')
                ->label('Nombre')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('ubicacion')
                ->label('Ubicacion')
                ->sortable()
                ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultorios::route('/'),
            'create' => Pages\CreateConsultorio::route('/create'),
            'edit' => Pages\EditConsultorio::route('/{record}/edit'),
        ];
    }
}
