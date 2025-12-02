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
use App\Models\Consultorio;
use Filament\Notifications\Notification;

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

    //public static function getNavigationBadge(): ?string
   // {
       // return Cache::remember('llamado_medicobadge', 60, function () {
           // return static::getEloquentQuery()->count();
        //});
   // }

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

        TextColumn::make('prioridad_texto')
    ->label('Prioridad')
    ->badge()
    ->color(fn ($state) => match ($state) {
        'Alta' => 'danger',
        'Media' => 'warning',
        'Baja' => 'success',
    }),
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
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'llamado' => 'info',        // Azul
        'en_espera' => 'warning',   // Naranja
        'asignado' => 'success', 
        'llamado_medico' => 'info',    // Verde
        default => 'gray',
    }),
            ])
            ->defaultSort('hora', 'asc')
            
            ->filters([
                Tables\Filters\SelectFilter::make('fk_consultorio')
                    ->label('Consultorio')
                    ->options(Consultorio::pluck('nombre', 'id_consultorio'))
                    ->placeholder('Todos los consultorios')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('llamar')
        ->label('Llamar')
        ->button()
        ->color('primary')
        ->icon('heroicon-o-phone')
        //->requiresConfirmation()
        //->modalHeading('¿Llamar a este turno?')
        //->modalDescription('Se marcará como llamado')
        //->modalSubmitActionLabel('Sí, llamar')
        ->action(function (Turno $record) {
            $record->update(['estado' => 'llamado_medico']);

            Notification::make()
                ->title('Turno llamado')
                ->body("Se llamó al turno {$record->numero_turno}")
                ->success()
                ->send();
        })
        ->visible(fn (Turno $record): bool => $record->estado === 'asignado'),


        Tables\Actions\Action::make('Facturar')
        ->label('Enviar a facturar')
        ->button()
        ->color('info')
        //->icon('heroicon-o-phone')
        //->requiresConfirmation()
        //->modalHeading('¿Esta seguro de finalizar la consulta?')
        //->modalDescription('El paciente se enviara para que facture')
        //->modalSubmitActionLabel('Sí, Enviar a facturar')
        ->action(function (Turno $record) {
               $record->update([
        'estado' => 'facturar',
        'motivo' => 'pendiente para facturar',
        'ventanilla' => null, // o '' si realmente lo necesitas vacío
    ]);

            Notification::make()
                ->title('Paciente atendido')
                ->success()
                ->send();
        })
        ->visible(fn (Turno $record): bool => $record->estado === 'llamado_medico'),

         Tables\Actions\Action::make('cancelar')
        ->label('Cancelar')
        ->color('danger')
        ->icon('heroicon-o-x-circle')
        ->requiresConfirmation(false)
        ->modalHeading('Cancelar turno')
        ->modalSubmitActionLabel('Guardar')
        ->modalCancelActionLabel('Cancelar')
        ->form([
            Forms\Components\Textarea::make('observaciones')
                ->label('Observaciones')
                ->placeholder('Escribe el motivo de la cancelación...')
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
        ->visible(fn (Turno $record): bool => $record->estado === 'llamado_medico'),










        
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
