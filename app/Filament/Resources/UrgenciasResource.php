<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UrgenciasResource\Pages;
use App\Filament\Resources\UrgenciasResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use App\Models\Consultorio;
use App\Models\Modulo;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UrgenciasResource extends Resource
{

    protected static ?string $model = Turno::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Urgencias ';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->whereIn('estado', ['en_espera', 'llamado'])
            ->where('motivo', 'urgencias')
            ///->whereIn('motivo', ['Urgencias', 'pendiente para facturar'])
            //codigo para cargar las relaciones de paciente, modulo y consultorio
            ->with(['paciente', 'modulo', 'consultorio']);
    }
    /* ============================================
     |  PERMISOS DEL RESOURCE
     ============================================ */
    public static function canCreate(): bool
    {
        return false; // No se pueden crear turnos manualmente
    }

    public static function canEdit(Model $record): bool
    {
        return false; // No se editan turnos aquí
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s') // Auto refresco cada 60s
            ->defaultSort('hora', 'asc')
            ->columns([
                // FECHA
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                // NÚMERO DE TURNO
                TextColumn::make('numero_turno')
                    ->label('Turno')
                    ->sortable()
                    ->searchable(),


                // MOTIVO
                Tables\Columns\TextColumn::make('motivo')
                    ->label('Motivo')
                    ->sortable()
                    ->searchable(),

                // MÓDULO
                Tables\Columns\TextColumn::make('modulo.nombre')
                    ->label('Ventanilla')
                    ->sortable()
                    ->searchable(),

                // ESTADO
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'llamado' => 'success',
                        'en_espera' => 'warning',
                        'asignado' => 'success',
                        'facturar' => 'info',
                        'llamado_facturar' => 'info',
                        default => 'gray',
                    }),

                // HORA
                Tables\Columns\TextColumn::make('hora')
                    ->label('Hora')
                    ->sortable()
                    ->time('g:i A'),
            ])

            ->filters([
                //
            ])
            ->actions([
                /* ================================
                 | LLAMAR DESDE EN ESPERA
                 ================================= */
                Tables\Actions\Action::make('llamar_enespera')
                    ->label('Llamar')
                    ->iconButton()
                    ->color('primary')
                    ->icon('heroicon-o-phone')
                    ->requiresConfirmation(false)
                    ->modalHeading('Asignar Módulo')
                    ->modalSubmitActionLabel('Llamar')
                    ->form([
                        Forms\Components\Select::make('fk_modulo')
                            ->label('Módulo')
                            ->options(fn() => Cache::remember(
                                'modulos_select',
                                300,
                                fn() => Modulo::pluck('nombre', 'id_modulo')
                            ))
                            ->required()
                            ->placeholder('Seleccione un módulo'),
                    ])
                    ->before(function (Turno $record) {
                        $record->update([
                            'estado' => 'llamado',
                            'hora_llamado' => now()->format('H:i:s'),
                        ]);
                        Notification::make()->title('Turno llamado')->success()->send();
                    })
                    ->action(function (Turno $record, array $data) {
                        $record->update(['fk_modulo' => $data['fk_modulo']]);
                        Notification::make()
                            ->title('Paciente Llamado')
                            ->body("Turno asignado correctamente")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => $record->estado === 'en_espera'),
                /* ================================
                 | ASIGNAR CONSULTORIO
                 ================================= */
                Tables\Actions\Action::make('asignar_consultorio')
                    ->label('Asignar Consultorio')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->iconButton()
                    ->modalHeading('Selecciona el consultorio para este turno')
                    ->modalSubmitActionLabel('Asignar')
                    ->form([
                        Forms\Components\Select::make('fk_consultorio')
                            ->label('Consultorio')
                            ->options(fn() => Cache::remember(
                                'consultorio_select',
                                300,
                                fn() => Consultorio::pluck('nombre', 'id_consultorio')
                            ))
                            ->required(),
                        Forms\Components\TextInput::make('paciente_urgencias')
                            ->label('Nombre del Paciente')
                            ->required()
                            ->maxLength(255),
                    ])

                    ->action(function (Turno $record, array $data) {

                        $record->update([
                            'estado' => 'asignado',
                            'hora_atendido' => now()->format('H:i:s'),
                            'fk_consultorio' => $data['fk_consultorio'],
                            'paciente_urgencias' => $data['paciente_urgencias'],
                        ]);

                        Notification::make()
                            ->title('Consultorio asignado')
                            ->body("Turno asignado correctamente")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => $record->estado === 'llamado'),


            ])
            ->bulkActions([]);
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
            'index' => Pages\ListUrgencias::route('/'),
            'create' => Pages\CreateUrgencias::route('/create'),
            'edit' => Pages\EditUrgencias::route('/{record}/edit'),
        ];
    }
}
