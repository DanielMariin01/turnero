<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReporteResource\Pages;
use App\Filament\Resources\ReporteResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Consultorio;
use App\Models\Modulo;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\TextColumn;



class ReporteResource extends Resource
{
    protected static ?string $model = Turno::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Modulos';
    protected static ?string $label = 'Reporte  ';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // No se pueden crear turnos manualmente
    }

    public static function canEdit(Model $record): bool
    {
        return false; // No se editan turnos aquí
    }
    //permisos para ver recursos 
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin']) ?? false;
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
            ->defaultSort('fecha', 'desc')

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
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->paciente
                            ? $record->paciente->nombre . ' ' . $record->paciente->apellido
                            : '-'
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('paciente.numero_documento')
                    ->label('Numero de Documento')
                    ->sortable()
                    ->searchable(),


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

                Tables\Columns\TextColumn::make('hora_llamado')
                    ->label('Llamado')
                    ->sortable()
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('hora_atendido')
                    ->label('Hora atendido')
                    ->sortable()
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('hora_llamado_medico')
                    ->label('Hora llamado médico')
                    ->sortable()
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('hora_llamado_facturar')
                    ->label('Llamado a facturar')
                    ->sortable()
                    ->time('g:i A'),
                Tables\Columns\TextColumn::make('hora_finalizacion')
                    ->label('Hora finalizado')
                    ->sortable()
                    ->time('g:i A'),

            ])
            ->paginationPageOptions([5, 10, 20])

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
            'index' => Pages\ListReportes::route('/'),
            'create' => Pages\CreateReporte::route('/create'),
            'edit' => Pages\EditReporte::route('/{record}/edit'),
        ];
    }
}
