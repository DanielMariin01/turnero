<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicoResource\Pages;
use App\Filament\Resources\MedicoResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MedicoResource extends Resource
{
    protected static ?string $model = Turno::class;
  protected static ?string $label = 'Turnos medicos';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';



    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->hoy()
            ->where('estado', 'asignado');
            //->where('motivo', 'consulta externa');
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('asignado_badge', 60, function () {
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
                //
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

                TextColumn::make('consultorio.nombre')
                    ->label('Consultorio')
                    ->sortable()
                    ->searchable(),


               TextColumn::make('hora')
                    ->label('Hora')
                    //->sortable()
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
            
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListMedicos::route('/'),
            'create' => Pages\CreateMedico::route('/create'),
            'edit' => Pages\EditMedico::route('/{record}/edit'),
        ];
    }
}
