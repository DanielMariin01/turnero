<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultorioUrgenciasResource\Pages;
use App\Filament\Resources\ConsultorioUrgenciasResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Consultorio;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsultorioUrgenciasResource extends Resource
{
    protected static ?string $model = Turno::class;
    protected static ?string $label = 'Consultorio urgencias';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->where('estado', 'asignado')
            ->whereHas('consultorio', function ($q) {
                $q->where('nombre', 'consultorio urgencias');
            });
        //->where('motivo', 'consulta externa');
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }


        //permisos para ver recursos 
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'medico_urgencias']) ?? false;
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
            ->poll('5s')
            ->columns([


                TextColumn::make('paciente_urgencias')
                    ->label('Paciente')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('consultorio.nombre')
                    ->label('Consultorio')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('hora_atendido')
                    ->label('Hora')
                    //->sortable()
                    ->time('g:i A'),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),


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
                    ->action(function (Turno $record) {

                        // ========================================
                        // PASO 3: Llamar al turno actual
                        // ========================================
                        $record->update([
                            'estado' => 'llamado_medico',
                            'hora_llamado_medico' => now()->format('H:i:s'),
                        ]);

                        // ========================================
                        // PASO 4: Notificar éxito
                        // ========================================
                        Notification::make()
                            ->title('Turno llamado')
                            ->body("Se llamó al turno {$record->numero_turno}")
                            ->success()
                            ->send();
                    })

                    ->visible(fn(Turno $record): bool => $record->estado === 'asignado'),
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
            'index' => Pages\ListConsultorioUrgencias::route('/'),
            'create' => Pages\CreateConsultorioUrgencias::route('/create'),
            'edit' => Pages\EditConsultorioUrgencias::route('/{record}/edit'),
        ];
    }
}
