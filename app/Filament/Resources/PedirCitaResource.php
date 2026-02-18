<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedirCitaResource\Pages;
use App\Filament\Resources\PedirCitaResource\RelationManagers;
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

class PedirCitaResource extends Resource
{
    protected static ?string $model = Turno::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $label = 'Admisiones Oncologia ';


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->whereIn('estado', ['en_espera', 'llamado'])
            ->where('motivo', 'Pedir Cita')
            ->with(['paciente', 'modulo']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'admisiones_oncologia']) ?? false;
    }
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

                TextColumn::make('paciente.numero_documento')
                    ->label('Numero de Documento')
                    ->sortable()
                    ->searchable(),
                // PACIENTE
                Tables\Columns\TextColumn::make('paciente.nombre')
                    ->label('Paciente')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->paciente
                            ? $record->paciente->nombre . ' ' . $record->paciente->apellido
                            : '-'
                    )
                    ->searchable()
                    ->sortable(),

                // CONDICIÓN
                //Tables\Columns\TextColumn::make('condicion')
                //->label('Condición')
                //->sortable()
                //->searchable(),

                // MOTIVO
                //Tables\Columns\TextColumn::make('motivo')
                //->label('Motivo')
                //->sortable()
                //->searchable(),

                // MÓDULO
                Tables\Columns\TextColumn::make('modulo.nombre')
                    ->label('Ventanilla')
                    ->sortable()
                    ->searchable(),

                // PRIORIDAD
                Tables\Columns\TextColumn::make('prioridad_texto')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'success',
                    }),

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
                        in_array($record->estado, ['llamado'])
                    )

                    //->requiresConfirmation()
                    ->action(function (Turno $record) {
                        $record->update([
                            'llamado_en' => now(),
                        ]);
                    }),


                /* ================================
                 | CANCELAR TURNO
                 ================================= */
                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->iconButton()
                    ->requiresConfirmation(false)
                    ->modalHeading('Cancelar turno')
                    ->modalSubmitActionLabel('Guardar')
                    ->form([
                        Forms\Components\Select::make('observaciones')
                            ->label('Motivo de cancelación')
                            ->placeholder('Selecciona una opción')
                            ->searchPrompt('Escribe para buscar...')
                            ->noSearchResultsMessage('No se encontraron resultados.')
                            ->required()
                            ->searchable()
                            ->options([
                                'laboratorios' => 'Laboratorios',
                                'imagenes' => 'Imagenes',
                                'Consulta_Externa' => 'Consulta Externa',
                                'no_atiende_llamado_facturar' => 'No atiende llamado para facturar',
                                'no_atiende_llamado_historia' => 'No atiende llamado para historia clínica',
                                'con_cita_posterior' => 'Con cita posterior',
                                'error_de_agendamiento' => 'Error de agendamiento',
                                'turno_doble' => 'Turno doble',
                                'perdio_cita' => 'Perdió cita',
                                'sin_autorizacion ' => 'Sin autorizacion',
                                'procedimiento_no_QX' => 'Procedimiento no QX',
                                'paciente_erroneo' => 'Paciente erróneo',
                                'Cirugia' => 'Cirugía',
                                'Informacion' => 'Información',
                                'otro' => 'Otro motivo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (Turno $record, array $data) {
                        $record->update([
                            'estado' => 'no_atendido',
                            'observaciones' => $data['observaciones'],
                            //'hora' => now()->format('H:i:s'),
                        ]);

                        Notification::make()
                            ->title('Turno cancelado')
                            ->body("El turno {$record->numero_turno} fue marcado como no atendido.")
                            ->danger()
                            ->send();
                    })
                    ->visible(
                        fn(Turno $record): bool =>
                        in_array($record->estado, ['llamado', 'llamado_facturar'])
                    ),
                //FINALIZAR ATENCIÓN
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
            'index' => Pages\ListPedirCitas::route('/'),
            'create' => Pages\CreatePedirCita::route('/create'),
            'edit' => Pages\EditPedirCita::route('/{record}/edit'),
        ];
    }
}
