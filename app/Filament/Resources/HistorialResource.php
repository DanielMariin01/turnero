<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistorialResource\Pages;
use App\Filament\Resources\HistorialResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class HistorialResource extends Resource
{
    protected static ?string $model = Turno::class;


    protected static ?string $label = 'Historial de Turnos';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';


        public static function canCreate(): bool
    {
        return false;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               
         

      

            Forms\Components\Section::make('Detalles de Atención')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
            

                            Forms\Components\Select::make('fk_modulo')
                                ->label('Modulo')
                                ->relationship('modulo', 'nombre')
                                ->searchable()
                                ->preload(),
                         

                            Forms\Components\Select::make('consultorio.nombre')
                                ->label('Consultorio')
                                ->relationship('consultorio', 'nombre')
                                 ->placeholder('Seleccione el consultorio')
                                //->searchable()
                                ->preload(),
                                

                

                            Forms\Components\Select::make('estado')
                                ->label('Estado')
                                ->options([
                                    'en_espera' => 'En Espera',
                                    'llamado' => 'Llamado',
                                    'asignado' => 'Asignado',
                                    'facturar' => 'Facturar',
                                    //'llamado_medico' => 'Llamado por el Médico',
                                    'llamado_facturar' => 'Llamado Facturar',
                                ])
                             
                           ->preload()
                                ->native(false),
                        ]),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                       
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_turno')
                    ->label('Numero del turno')
                    ->sortable(),


                TextColumn::make('paciente.nombre')
                    ->label('Paciente')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->paciente
                            ? $record->paciente->nombre . ' ' . $record->paciente->apellido
                            : '-'
                    )
                    ->sortable()
                    ->searchable(),

           TextColumn::make('paciente.numero_documento')
                    ->label('Numero de Documento')
                    ->sortable()
                    ->searchable(),

          
                TextColumn::make('condicion')
                    ->label('Condicion')
                    ->sortable()
                    ->searchable(),

           
TextColumn::make('motivo')
                    ->label('Motivo')
                    ->sortable()
                    ->searchable(),
    
                TextColumn::make('modulo.nombre')
                    ->label('Ventanilla')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('consultorio.nombre')
                    ->label('Consultorio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('observaciones')
                    ->label('Observacion')
                    ->sortable(),
                    
                                
         TextColumn::make('prioridad_texto')
    ->label('Prioridad')
    ->badge()
    ->color(fn ($state) => match ($state) {
        'Alta' => 'danger',
        'Media' => 'warning',
        'Baja' => 'success',
    }),


              TextColumn::make('estado')
    ->label('Estado')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'llamado' => 'success',        // Azul
        'en_espera' => 'warning',   // Naranja
        'asignado' => 'success', 
        'facturar' => 'info', 
         'llamado_facturar' => 'success',
           // Verde
        default => 'gray',
    }),
           
           TextColumn::make('hora')
                    ->label('Hora')
                    ->sortable()
                    ->time('g:i A'),     
            ])
             ->paginationPageOptions([5, 10, 20])
             ->defaultSort('hora', 'asc')



            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->label('Editar'),
            ])
            ->bulkActions([
             
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
            'index' => Pages\ListHistorials::route('/'),
            'create' => Pages\CreateHistorial::route('/create'),
            'edit' => Pages\EditHistorial::route('/{record}/edit'),
        ];
    }
}
