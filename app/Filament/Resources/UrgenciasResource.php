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
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UrgenciasResource extends Resource
{
    protected static ?string $model = Turno::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $label = 'Admisiones Urgencias ';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->whereIn('estado_admisiones', ['pendiente', 'llamado'])
            ->where('motivo', 'urgencias')
            ->with(['paciente', 'consultorio', 'modulo']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'admisiones_urgencias']) ?? false;
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
                //
            ]);
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
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pendiente' => 'warning',
                        'llamado' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('modulo.nombre')
                    ->label('Ventanilla')
                    ->sortable(),

                TextColumn::make('contrato_nombre')
                    ->label('Contrato / EPS')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('consultorio.nombre')
                    ->label('Consultorio Triage')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                /* ================================
                 | LLAMAR A ADMISIONES
                 ================================= */
                Tables\Actions\Action::make('llamar_admisiones')
                    ->label('Llamar')
                    ->iconButton()
                    ->color('primary')
                    ->icon('heroicon-o-phone')
                    ->modalHeading('Asignar ventanilla de Admisiones')
                    ->modalSubmitActionLabel('Llamar')
                    ->form([
                        Forms\Components\Select::make('fk_modulo')
                            ->label('Ventanilla')
                            ->options(
                                fn() => Modulo::where('area', 'urgencias')
                                    ->pluck('nombre', 'id_modulo')
                            )
                            ->required(),
                    ])
                    ->action(function (Turno $record, array $data) {
                        $record->update([
                            'estado_admisiones' => 'llamado',
                            'fk_modulo' => $data['fk_modulo'],
                        ]);

                        Notification::make()
                            ->title('Paciente llamado a Admisiones')
                            ->body("Turno {$record->numero_turno} llamado correctamente")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => $record->estado_admisiones === 'pendiente'),

                /* ================================
                 | FINALIZAR ADMISIÓN
                 ================================= */
                Tables\Actions\Action::make('finalizar_admisiones')
                    ->label('Finalizar Admisión')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar proceso de admisión')
                    ->modalDescription('¿Confirmas que ya terminaste el proceso administrativo de este paciente?')
                    ->action(function (Turno $record) {
                        $updates = ['estado_admisiones' => 'atendido'];

                        if ($record->estado_consulta_medica === null) {
                            $updates['estado_consulta_medica'] = 'pendiente';
                        }

                        $record->update($updates);
                        $record->refresh();

                        if ($record->estado_consulta_medica === 'atendido') {
                            $record->update([
                                'estado' => 'atendido',
                                'hora_finalizacion' => now()->format('H:i:s'),
                            ]);
                        }

                        Notification::make()
                            ->title('Admisión finalizada')
                            ->body("Turno {$record->numero_turno} procesado correctamente")
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => $record->estado_admisiones === 'llamado'),

                /* ================================
                 | CANCELAR TURNO
                 ================================= */
                Tables\Actions\Action::make('cancelar')
                    ->label('CANCELAR TURNO')
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
                            'estado_admisiones' => 'atendido',
                            'observaciones' => $data['observaciones'],
                        ]);

                        Notification::make()
                            ->title('Turno cancelado')
                            ->body("El turno {$record->numero_turno} fue marcado como no atendido.")
                            ->danger()
                            ->send();
                    })
                    ->visible(fn(Turno $record): bool => in_array($record->estado_admisiones, ['pendiente', 'llamado'])),
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
