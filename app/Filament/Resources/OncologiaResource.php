<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OncologiaResource\Pages;
use App\Filament\Resources\OncologiaResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Columns\Date;
use Filament\Tables\Columns\TextColumn;



class OncologiaResource extends Resource
{
    protected static ?string $model = Turno::class;

protected static ?string $navigationIcon = 'heroicon-o-user';      
protected static ?int $navigationSort = 3;
protected static ?string $label = 'Oncologia ';

   public static function getNavigationBadge(): ?string
{
        return Cache::remember('facturado_badge', 60, function () {
        return static::getEloquentQuery()->count();
    });
   }

      public static function canCreate(): bool
    {
        return false;
    }
public static function canEdit(Model $record): bool
{
    return false;
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
          
            

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                        Tables\Columns\TextColumn::make('id_turno')
            ->label('ID turno')
            ->sortable(),

            Tables\Columns\TextColumn::make('numero_turno')
            ->label('Numero del turno')
            ->sortable(),

            TextColumn::make('condicion')
            ->label('Condicion')
            ->sortable()
            ->searchable(),

            TextColumn::make('hora')
            ->label('Hora')
            ->sortable()
           ->time('g:i A'),

                    TextColumn::make('fecha')
    ->label('Fecha')
    ->date()          // formatea como fecha
    ->sortable(), 

           TextColumn::make('estado')
           ->label('Estado')
          ->color('success')
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                      Tables\Actions\Action::make('llamar')
                ->label('Llamar')
                ->button()
                ->color('primary')
                ->icon('heroicon-o-phone')
                ->action(fn ($record) => null),
            ])
            ->bulkActions([
             
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
            'index' => Pages\ListOncologias::route('/'),
            'create' => Pages\CreateOncologia::route('/create'),
            'edit' => Pages\EditOncologia::route('/{record}/edit'),
        ];
    }
}
