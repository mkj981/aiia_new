<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Models\Permission;
use BackedEnum;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder('e.g. users.view')
                    ->maxLength(255),

                TextInput::make('guard_name')
                    ->default('web')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group')
                    ->label('Group')
                    ->state(fn ($record) => ucfirst(Str::before($record->name, '.')))
                    ->badge()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('name', $direction)),

                TextColumn::make('action')
                    ->label('Action')
                    ->state(fn ($record) => Str::after($record->name, '.'))
                    ->badge(),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record))
                    ->modalWidth('md'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->visible(fn () => static::canCreate())
                    ->modalWidth('md'),

                DeleteBulkAction::make()
                    ->visible(fn () => static::canDeleteAny()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || $user->hasPermissionTo('permissions.view'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || $user->hasPermissionTo('permissions.create'));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || $user->hasPermissionTo('permissions.edit'));
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || $user->hasPermissionTo('permissions.delete'));
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || $user->hasPermissionTo('permissions.delete'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || $user->hasPermissionTo('permissions.view'));
    }
}
