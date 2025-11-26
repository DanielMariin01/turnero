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
use App\Models\Turno_Medico;
use App\Models\Consultorio;
use App\Models\Modulo;
use Illuminate\Support\Facades\DB;


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
         ->whereIn('estado', ['en_espera', 'llamado'])
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
             ->poll('5s')
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

       TextColumn::make('modulo.nombre')
                    ->label('Ventanilla')
                    ->sortable()
                    ->searchable(),

           TextColumn::make('estado')
           ->label('Estado')
          ->color('success')
            
  
            ])
            ->defaultSort('hora', 'asc')
       
            ->filters([
                //
            ])
            ->actions([
      Tables\Actions\Action::make('llamar')
    ->label('Llamar')
    ->button()
    ->color('primary')
    ->icon('heroicon-o-phone')
    ->requiresConfirmation(false)   // 🔹 Permite que el formulario SÍ se abra

    ->modalHeading('Asignar Modulo')
    ->modalSubmitActionLabel('Llamar')

    ->form([
        Forms\Components\Select::make('fk_modulo')
            ->label('Modulo')
            ->options(
                Modulo::pluck('nombre', 'id_modulo')
            )
            ->required()
            ->placeholder('Seleccione un modulo'),
    ])

    // 🔹 Este “before” se ejecuta al abrir el formulario
    ->before(function (Turno $record) {
        $record->update(['estado' => 'llamado']);

        Notification::make()
            ->title('Turno llamado')
            ->body("Se llamó al turno {$record->numero_turno}")
            ->success()
            ->send();
    })

    // 🔹 Esta acción SÍ recibe el formulario ($data)
    ->action(function (Turno $record, array $data) {

        // Verificar que lleguen los datos
        // dd($data);  // <-- Actívalo si quieres ver qué llega

        $record->update([
            'fk_modulo' => $data['fk_modulo'],
        ]);

        Notification::make()
            ->title('Paciente Llamado')
            ->body("Turno {$record->numero_turno} asignado correctamente")
            ->success()
            ->send();
    })
        ->visible(fn (Turno $record): bool => $record->estado === 'en_espera'),

    // ACCIÓN: Asignar consultorio (solo visible cuando estado = 'llamado')
    Tables\Actions\Action::make('asignar_consultorio')
        ->label('Asignar Consultorio')
        ->button()
        ->color('success')
        ->icon('heroicon-o-check')
       ->modalHeading('Selecciona el consultorio para este turno')
    //->modalDescription('Selecciona el consultorio para este turno')
    ->modalSubmitActionLabel('Asignar')
    ->modalCancelActionLabel('Cancelar')
        ->form([
            Forms\Components\Select::make('fk_consultorio')
                ->label('Consultorio')
                ->options(Consultorio::pluck('nombre', 'id_consultorio'))
                ->placeholder('Selecciona el consultorio')
                ->required(),
        ])
        ->action(function (Turno $record, array $data) {
            $record->update([
                'estado' => 'asignado',
                'hora' => now()->format('H:i:s'),
                'fk_consultorio' => $data['fk_consultorio']
            ]);

            Notification::make()
                ->title('Consultorio asignado')
                ->body("Turno asignado correctamente")
                ->success()
                ->send();
        })
        ->visible(fn (Turno $record): bool => $record->estado === 'llamado'),

    // ACCIÓN: Cancelar (solo visible cuando estado = 'llamado')
    Tables\Actions\Action::make('cancelar')
        ->label('Cancelar')
        ->color('danger')
        ->icon('heroicon-o-x-circle')
        ->requiresConfirmation(false)
        ->modalHeading('Cancelar turno')
        ->modalSubmitActionLabel('Guardar')
        ->modalCancelActionLabel('Cancelar')
        ->form([
            Forms\Components\Textarea::make('observaciones')
                ->label('Observaciones')
                ->placeholder('Escribe el motivo de la cancelación...')
                ->required()
                ->columnSpanFull(),
        ])
        ->action(function (Turno $record, array $data) {
            $record->update([
                'estado' => 'no_atendido',
                'observaciones' => $data['observaciones'],
                'hora' => now()->format('H:i:s'),
            ]);

            Notification::make()
                ->title('Turno cancelado')
                ->body("El turno {$record->numero_turno} fue marcado como no atendido.")
                ->danger()
                ->send();
        })
        ->visible(fn (Turno $record): bool => $record->estado === 'llamado'),
             
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
