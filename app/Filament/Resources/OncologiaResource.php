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
    //protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Quimioterapia ';


    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'Quimioterapia']) ?? false;
    }
    public static function getEloquentQuery(): Builder
    {
        // Combina ambos: solo turnos de hoy y estado 'en_espera'
        return parent::getEloquentQuery()
            ->hoy() // tu scope para turnos de hoy
            ->whereIn('estado', ['en_espera', 'llamado'])
            ->where('motivo', 'oncologia')
            ->with(['paciente', 'modulo', 'consultorio']);
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
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([

                TextColumn::make('paciente.nombre')
                    ->label('Paciente')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->paciente
                            ? $record->paciente->nombre . ' ' . $record->paciente->apellido
                            : '-'
                    )
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
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'llamado' => 'success',        // Azul
                        'en_espera' => 'warning',   // Naranja
                        // Verde
                        default => 'gray',
                    }),


            ])
            ->defaultSort('hora', 'asc')

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
                 | ACCION RELLAMAR
                 ================================= */
                Tables\Actions\Action::make('rellamar')
                    ->label('Volver a llamar')
                    ->icon('heroicon-o-speaker-wave')
                    ->iconButton()
                    ->color('warning')
                    ->visible(
                        fn(Turno $record): bool =>
                        in_array($record->estado, ['llamado', 'llamado_facturar'])
                    )

                    //->requiresConfirmation()
                    ->action(function (Turno $record) {
                        $record->update([
                            'llamado_en' => now(),
                        ]);
                    }),

                /* ================================
                 | FINALIZAR ATENCIÓN
                 ================================= */
                Tables\Actions\Action::make('Finalizar')
                    ->label('Finalizar Atención')
                    ->iconButton()
                    ->color('gray')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar atención')
                    ->modalDescription('¿Está seguro de finalizar la atención?')
                    ->modalSubmitActionLabel('Guardar')
                    ->action(function (Turno $record) {
                        $record->update([
                            'estado' => 'atendido',
                            //'hora' => now()->format('H:i:s'),
                            'hora_finalizacion' => now()->format('H:i:s'),
                        ]);

                        Notification::make()
                            ->title('Turno atendido')
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
            'index' => Pages\ListOncologias::route('/'),
            'create' => Pages\CreateOncologia::route('/create'),
            'edit' => Pages\EditOncologia::route('/{record}/edit'),
        ];
    }
}
