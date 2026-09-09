<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultaMedicaUrgenciasResource\Pages;
use App\Models\Turno;
use App\Models\Consultorio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;

class ConsultaMedicaUrgenciasResource extends Resource
{
    protected static ?string $model = Turno::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $label = 'Consulta Médica Urgencias ';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->whereIn('estado_consulta_medica', ['pendiente', 'llamado'])
            ->where('motivo', 'urgencias')
            ->with(['paciente', 'consultorio', 'consultorioConsulta']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'medico_consulta_urgencias']) ?? false;
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
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('nivel_triage', 'asc')
            ->columns([
                TextColumn::make('numero_turno')
                    ->label('Turno')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('paciente.numero_documento')
                    ->label('Documento')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('paciente_urgencias')
                    ->label('Paciente')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nivel_triage')
                    ->label('Triage')
                    ->badge()
                    ->color(fn($state) => match ((int) $state) {
                        1 => 'danger',
                        2, 3 => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('estado_admisiones')
                    ->label('Admisiones')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'atendido' => 'success',
                        'llamado' => 'info',
                        'pendiente' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('contrato_nombre')
                    ->label('Contrato / EPS')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('consultorioConsulta.nombre')
                    ->label('Consultorio')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                /* ================================
                 | LLAMAR A CONSULTA MÉDICA
                 ================================= */
                Tables\Actions\Action::make('llamar_consulta')
                    ->label('Llamar')
                    ->iconButton()
                    ->color('primary')
                    ->icon('heroicon-o-phone')
                    ->modalHeading('Selecciona el consultorio de consulta médica')
                    ->modalSubmitActionLabel('Llamar')
                    ->form([
                        Forms\Components\Select::make('fk_consultorio_consulta')
                            ->label('Consultorio')
                            ->options(
                                fn() => Consultorio::where('area', 'consulta_medica_urgencias')
                                    ->pluck('nombre', 'id_consultorio')
                            )
                            ->required(),
                    ])
                    ->action(function (Turno $record, array $data) {
                        $record->update([
                            'estado_consulta_medica' => 'llamado',
                            'fk_consultorio_consulta' => $data['fk_consultorio_consulta'],
                        ]);

                        Notification::make()
                            ->title('Paciente llamado a Consulta Médica')
                            ->body("Turno {$record->numero_turno} llamado correctamente")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => $record->estado_consulta_medica === 'pendiente'),

                /* ================================
                 | VOLVER A LLAMAR
                 ================================= */
                Tables\Actions\Action::make('rellamar_consulta')
                    ->label('Volver a llamar')
                    ->icon('heroicon-o-speaker-wave')
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn(Turno $record): bool => $record->estado_consulta_medica === 'llamado')
                    ->action(function (Turno $record) {
                        $record->update(['llamado_en' => now()]);
                    }),

                /* ================================
                 | FINALIZAR CONSULTA
                 ================================= */
                Tables\Actions\Action::make('finalizar_consulta')
                    ->label('Finalizar Consulta')
                    ->iconButton()
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar consulta médica')
                    ->modalDescription('¿Confirmas que la atención médica de este paciente terminó?')
                    ->action(function (Turno $record) {
                        $record->update(['estado_consulta_medica' => 'atendido']);
                        $record->refresh();

                        if ($record->estado_admisiones === 'atendido') {
                            $record->update([
                                'estado' => 'atendido',
                                'hora_finalizacion' => now()->format('H:i:s'),
                            ]);
                        }

                        Notification::make()
                            ->title('Consulta finalizada')
                            ->body("Turno {$record->numero_turno} procesado correctamente")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => $record->estado_consulta_medica === 'llamado'),

                /* ================================
                 | CANCELAR TURNO
                 ================================= */
                Tables\Actions\Action::make('cancelar')
                    ->label('CANCELAR TURNO')
                    ->iconButton()
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
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
                                'turno_doble' => 'Turno doble',
                                'no_atiende_llamado' => 'No atiende al llamado',
                                'otro' => 'Otro motivo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (Turno $record, array $data) {
                        $record->update([
                            'estado' => 'no_atendido',
                            'estado_consulta_medica' => 'atendido',
                            'observaciones' => $data['observaciones'],
                        ]);

                        Notification::make()
                            ->title('Turno cancelado')
                            ->body("El turno {$record->numero_turno} fue marcado como no atendido.")
                            ->danger()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => in_array($record->estado_consulta_medica, ['pendiente', 'llamado'])),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultaMedicaUrgencias::route('/'),
            'create' => Pages\CreateConsultaMedicaUrgencias::route('/create'),
            'edit' => Pages\EditConsultaMedicaUrgencias::route('/{record}/edit'),
        ];
    }
}
