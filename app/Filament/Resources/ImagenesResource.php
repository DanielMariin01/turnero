<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImagenesResource\Pages;
use App\Filament\Resources\ImagenesResource\RelationManagers;
use App\Models\Turno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use App\Events\TurnoLlamado;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Turno_Medico;
use App\Models\Consultorio;
use App\Models\Modulo;
use Illuminate\Support\Facades\DB;


class ImagenesResource extends Resource
{
  protected static ?string $model = Turno::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;
    protected static ?string $label = 'Imagenes ';


    public static function getEloquentQuery(): Builder
{
    // Combina ambos: solo turnos de hoy y estado 'en_espera'
    return parent::getEloquentQuery()
        ->hoy() // tu scope para turnos de hoy
         ->whereIn('estado', ['en_espera'])
        ->where('motivo', 'imagenes')
        ->with(['paciente', 'modulo', 'consultorio']);
}

   public static function getNavigationBadge(): ?string
{
        return Cache::remember('imagenes_badge', 60, function () {
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
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
             ->poll('60s')
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
            ->sortable()
           ->time('g:i A'),

                    TextColumn::make('fecha')
    ->label('Fecha')
    ->date()          // formatea como fecha
    ->sortable(), 



                 TextColumn::make('estado')
    ->label('Estado')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'llamado' => 'success',        // Azul
        'en_espera' => 'warning',   // Naranja
           // Verde
        default => 'gray',
    }),

            
  
            ])
            ->defaultSort('hora', 'asc')
       
            ->filters([
                 
            ])
            ->actions([
              
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
    })
      ->visible(fn (Turno $record): bool => $record->estado === 'en_espera'),

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
            'index' => Pages\ListImagenes::route('/'),
            'create' => Pages\CreateImagenes::route('/create'),
            'edit' => Pages\EditImagenes::route('/{record}/edit'),
        ];
    }
}
