<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultaExternaResource\Pages;
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

class ConsultaExternaResource extends Resource
{
    /* ============================================
     |  CONFIGURACIÓN GENERAL DEL RESOURCE
     ============================================ */

    protected static ?string $model = Turno::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';      
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Consulta externa ';

    /* ============================================
     |  QUERY PRINCIPAL DEL RESOURCE
     |  - Filtra turnos de HOY
     |  - Solo estados permitidos
     |  - Carga relaciones
     ============================================ */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->whereIn('estado', ['en_espera', 'llamado', 'facturar', 'llamado_facturar'])
            ->whereIn('motivo', ['consulta externa', 'pendiente para facturar'])
            ->with(['paciente', 'modulo', 'consultorio']);
    }

    /* ============================================
     |  BADGE DEL MENÚ LATERAL
     ============================================ */
    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultaExterna_badge', 60, function () {
            return static::getEloquentQuery()->count();
        });
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
        return $form->schema([]); // Este módulo no usa formularios
    }

    /* ============================================
     |  TABLA PRINCIPAL
     ============================================ */
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
                Tables\Columns\TextColumn::make('numero_turno')
                    ->label('Número del turno')
                    ->sortable(),

                // PACIENTE
                Tables\Columns\TextColumn::make('paciente.nombre')
                    ->label('Paciente')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->paciente
                            ? $record->paciente->nombre . ' ' . $record->paciente->apellido
                            : '-'
                    )
                    ->searchable()
                    ->sortable(),

                // CONDICIÓN
                Tables\Columns\TextColumn::make('condicion')
                    ->label('Condición')
                    ->sortable()
                    ->searchable(),

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
                    ->color(fn ($state) => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'success',
                    }),

                // ESTADO
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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

            /* ============================================
             |  ACCIONES
             ============================================ */
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
                            ->options(fn () => Cache::remember('modulos_select', 300,
                                fn () => Modulo::pluck('nombre', 'id_modulo')))
                            ->required()
                            ->placeholder('Seleccione un módulo'),
                    ])
                    ->before(function (Turno $record) {
                        $record->update(['estado' => 'llamado']);
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
                    ->visible(fn (Turno $record): bool => $record->estado === 'en_espera'),

                /* ================================
                 | LLAMAR PARA FACTURAR
                 ================================= */
                Tables\Actions\Action::make('llamar')
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
                            ->options(fn () => Cache::remember('modulos_select', 300,
                                fn () => Modulo::pluck('nombre', 'id_modulo')))
                            ->required(),
                    ])
                    ->before(function (Turno $record) {
                        $record->update(['estado' => 'llamado_facturar']);
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
                    ->visible(fn (Turno $record): bool => $record->estado === 'facturar'),

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
                            ->options(fn () => Cache::remember('consultorio_select', 300,
                                fn () => Consultorio::pluck('nombre', 'id_consultorio')))
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


   /* ================================
                 | ACCION RELLAMAR
                 ================================= */
                    Tables\Actions\Action::make('rellamar')
     ->label('Volver a llamar')
    ->icon('heroicon-o-speaker-wave')
    ->iconButton()
    ->color('warning')
      ->visible(fn (Turno $record): bool =>
                        in_array($record->estado, ['llamado', 'llamado_facturar'])
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
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
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
                    ->visible(fn (Turno $record): bool =>
                        in_array($record->estado, ['llamado', 'llamado_facturar'])
                    ),

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
                            'hora' => now()->format('H:i:s'),
                        ]);

                        Notification::make()
                            ->title('Turno atendido')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Turno $record): bool => $record->estado === 'llamado_facturar'),
            ])
            ->bulkActions([]);
    }

    /* ============================================
     |  RELACIONES
     ============================================ */
    public static function getRelations(): array
    {
        return [];
    }

    /* ============================================
     |  PÁGINAS
     ============================================ */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultaExternas::route('/'),
            'create' => Pages\CreateConsultaExterna::route('/create'),
            'edit' => Pages\EditConsultaExterna::route('/{record}/edit'),
        ];
    }
}
