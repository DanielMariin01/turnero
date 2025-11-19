<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsuarioResource\Pages;
use App\Filament\Resources\UsuarioResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsuarioResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
protected static ?string $label = 'Usuarios ';


public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Información del usuario')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required(),

                    Forms\Components\Select::make('roles')
                        ->label('Roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required(),

                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Credenciales')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->required(fn (string $context): bool => $context === 'create')
                        ->confirmed()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmar contraseña')
                        ->password()
                        ->required(fn (string $context): bool => $context === 'create'),
                ])
                ->columns(2),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                     Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('email')
                ->label('Correo electrónico')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('roles.name')
                ->label('Roles')
                ->sortable()
                ->wrap()
                ->getStateUsing(fn($record) => $record->roles->pluck('name')->join(', ')),

            Tables\Columns\IconColumn::make('activo')
                ->label('Activo')
                ->boolean()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Creado')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        
            ])
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
            'index' => Pages\ListUsuarios::route('/'),
            'create' => Pages\CreateUsuario::route('/create'),
            'edit' => Pages\EditUsuario::route('/{record}/edit'),
        ];
    }
}
