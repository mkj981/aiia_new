<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Employer;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Navigation\NavigationItem;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Admins';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),

            TextInput::make('email')
                ->email()
                ->required(),

            TextInput::make('password')
                ->password()
                ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\Users\Pages\CreateUser)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            Select::make('employer_id')
                ->label('Employer')
                ->options(fn () => Employer::pluck('name', 'id')->toArray())
                ->searchable()
                ->live()
                ->afterStateUpdated(fn ($set) => $set('roles', []))
                ->nullable()
                ->helperText('Leave empty for System Admin'),

            Select::make('roles')
                ->label('Roles')
                ->multiple()
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: function ($query, $get) {
                        $employerId = $get('employer_id');

                        return is_null($employerId)
                            ? $query->whereNull('employer_id')
                            : $query->where('employer_id', $employerId);
                    }
                )
                ->preload()
                ->searchable()
                ->reactive(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('employer.name')->label('Employer'),
                TextColumn::make('roles.name')->label('Roles')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record))
                    ->modalHeading('Edit Admin')
                    ->modalSubmitActionLabel('Save Changes')
                    ->modalWidth('lg'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->visible(fn () => static::canCreate())
                    ->modalHeading('Create Admin')
                    ->modalSubmitActionLabel('Create')
                    ->modalWidth('lg'),

                DeleteBulkAction::make()
                    ->visible(fn () => static::canDeleteAny()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }

    // 🔥 PERMISSIONS

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->can('admins.view');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->can('admins.create');
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->can('admins.edit');
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->can('admins.delete');
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->can('admins.delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->isSuperAdmin() || $user->hasPermissionTo('admins.view', 'web');
    }
}
