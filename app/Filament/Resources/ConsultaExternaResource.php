<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultaExternaResource\Pages;
use App\Filament\Resources\ConsultaExternaResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Date;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Events\TurnoLlamado;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\Turno_Medico;
use App\Models\Consultorio;
use Illuminate\Support\Facades\DB;

class ConsultaExternaResource extends Resource
{
    protected static ?string $model = Turno::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';      
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Consulta externa';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->where('estado', 'en_espera')
            ->where('motivo', 'consulta externa');
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultaExterna_badge', 60, function () {
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
        return $form->schema([]);
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
                    ->date()
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->color('success'),
            ])
            ->defaultSort('hora', 'asc')
            ->filters([])
            ->actions([
           Tables\Actions\Action::make('llamar')
    ->label('Llamar')
    ->button()
    ->color('primary')
    ->icon('heroicon-o-phone')
    ->requiresConfirmation(false)   // 🔹 Permite que el formulario SÍ se abra

    ->modalHeading('Asignar Consultorio')
    ->modalSubmitActionLabel('Asignar')

    ->form([
        Forms\Components\Select::make('fk_consultorio')
            ->label('Consultorio')
            ->options(
                Consultorio::pluck('nombre', 'id_consultorio')
            )
            ->required()
            ->placeholder('Seleccione un consultorio'),
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
            'estado' => 'asignado',
            'fk_consultorio' => $data['fk_consultorio'],
            'hora' => now()->format('H:i:s'),
        ]);

        Notification::make()
            ->title('Consultorio asignado')
            ->body("Turno {$record->numero_turno} asignado correctamente")
            ->success()
            ->send();
    })

            ])
    
    //->hidden(fn (Turno $record): bool => $record->estado !== 'en_espera')

            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultaExternas::route('/'),
            'create' => Pages\CreateConsultaExterna::route('/create'),
            'edit' => Pages\EditConsultaExterna::route('/{record}/edit'),
        ];
    }
}