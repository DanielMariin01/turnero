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
use App\Models\Modulo;
use Illuminate\Support\Facades\DB;

class ConsultaExternaResource extends Resource
{
    protected static ?string $model = Turno::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';      
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Consulta externa ';

  public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->hoy()
        ->whereIn('estado', ['en_espera', 'llamado','facturar'])
       ->whereIn('motivo', ['consulta externa', 'pendiente para facturar'])
       ->with(['paciente', 'modulo', 'consultorio']);
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
            ->poll('60s')
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
           // Verde
        default => 'gray',
    }),
           
           TextColumn::make('hora')
                    ->label('Hora')
                    ->sortable()
                    ->time('g:i A'),     
            ])
            ->defaultSort('hora', 'asc')
            ->filters([])
    ->actions([
    // ACCIÓN: Llamar (solo visible cuando estado = 'en_espera')
    Tables\Actions\Action::make('llamar')
    ->label('Llamar')
    ->button()
    ->color('primary')
    ->icon('heroicon-o-phone')
    ->requiresConfirmation(false)   // 🔹 Permite que el formulario SÍ se abra

    ->modalHeading('Asignar Modulo')
    ->modalSubmitActionLabel('Llamar')

    ->form([
        Forms\Components\Select::make('fk_modulo')
            ->label('Modulo')
           ->options(function () {
    return Cache::remember('modulos_select', 300, function () {
        return Modulo::pluck('nombre', 'id_modulo');
    });
})

            ->required()
            ->placeholder('Seleccione un modulo'),
    ])

    // 🔹 Este “before” se ejecuta al abrir el formulario
    ->before(function (Turno $record) {
        $record->update(['estado' => 'llamado']);

        Notification::make()
            ->title('Turno llamado')
            ->success()
            ->send();
    })

    // 🔹 Esta acción SÍ recibe el formulario ($data)
    ->action(function (Turno $record, array $data) {

        // Verificar que lleguen los datos
        // dd($data);  // <-- Actívalo si quieres ver qué llega

        $record->update([
            'fk_modulo' => $data['fk_modulo'],
        ]);

        Notification::make()
            ->title('Paciente Llamado')
            ->body("Turno asignado correctamente")
            ->success()
            ->send();
    }),
//->visible(fn (Turno $record): bool => in_array($record->estado, ['en_espera', 'facturar'])),


    // ACCIÓN: Asignar consultorio (solo visible cuando estado = 'llamado')
    Tables\Actions\Action::make('asignar_consultorio')
        ->label('Asignar Consultorio')
        ->button()
        ->color('success')
        ->icon('heroicon-o-check')
       ->modalHeading('Selecciona el consultorio para este turno')
    //->modalDescription('Selecciona el consultorio para este turno')
    ->modalSubmitActionLabel('Asignar')
    ->modalCancelActionLabel('Cancelar')
        ->form([
            Forms\Components\Select::make('fk_consultorio')
                ->label('Consultorio')
                         ->options(function () {
    return Cache::remember('consultorio_select', 300, function () {
        return Consultorio::pluck('nombre', 'id_consultorio');
    });
})

                ->placeholder('Selecciona el consultorio')
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

    // ACCIÓN: Cancelar (solo visible cuando estado = 'llamado')
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
        ->visible(fn (Turno $record): bool => $record->estado === 'llamado'),
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