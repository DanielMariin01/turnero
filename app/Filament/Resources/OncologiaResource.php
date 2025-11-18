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
use App\Events\TurnoLlamado;
use Filament\Notifications\Notification;


class OncologiaResource extends Resource
{
    protected static ?string $model = Turno::class;

protected static ?string $navigationIcon = 'heroicon-o-user';      
protected static ?int $navigationSort = 3;
protected static ?string $label = 'Oncologia ';

public static function getEloquentQuery(): Builder
{
    // Combina ambos: solo turnos de hoy y estado 'en_espera'
    return parent::getEloquentQuery()
        ->hoy() // tu scope para turnos de hoy
        ->where('estado', 'en_espera')
        ->where('motivo', 'oncologia');  // solo los turnos en espera
}

   public static function getNavigationBadge(): ?string
{
        return Cache::remember('oncologia_badge', 60, function () {
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
          Tables\Columns\TextColumn::make('numero_turno')
            ->label('Numero del turno')
            ->sortable(),

            TextColumn::make('prioridad')
                ->label('Prioridad')
                ->getStateUsing(fn ($record) => match($record->condicion) {
                    'movilidad_reducida', 'adulto_mayor', 'gestante' => 'Alta',
                    'acompañado_con_un_menor' => 'Media',
                    default => 'Baja',
                })
                  ->colors([
        'danger' => fn ($state) => $state === 'Alta',
        'warning' => fn ($state) => $state === 'Media',
        'success' => fn ($state) => $state === 'Baja',
    ])
    ->formatStateUsing(fn ($state) => "● $state"),

TextColumn::make('paciente.nombre')
    ->label('Paciente')
    ->formatStateUsing(fn ($state, $record) =>
        $record->paciente
            ? $record->paciente->nombre . ' ' . $record->paciente->apellido
            : '-'
    )
    ->sortable()
    ->searchable(),

TextColumn::make('motivo')
->label('Motivo')
    ->sortable()
            ->searchable(),

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
            ->defaultSort('hora', 'asc')
       
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
        ->requiresConfirmation()
        ->action(function ($record) {
        


            $updated = Turno::where('id_turno', $record->id_turno)
                 ->where('estado', 'en_espera') // solo si está pendiente
                 ->update(['estado' => 'llamado']);

if ($updated) {
    // Turno actualizado con éxito
    \Filament\Notifications\Notification::make()
        ->title('Turno llamado')
        ->body("Se llamó al turno {$record->numero_turno}")
        ->success()
        ->send();
} else {
    // Otro módulo ya llamó este turno
    \Filament\Notifications\Notification::make()
        ->title('Error')
        ->body("El turno {$record->numero_turno} ya fue llamado por otro módulo")
        ->danger()
        ->send();
}
        }),
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
