<?php

namespace App\Filament\Resources\Employers;

use App\Filament\Resources\Employers\Pages\ListEmployers;
use App\Models\Employer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;

class EmployerResource extends Resource
{
    protected static ?string $model = Employer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Employers';

    protected static ?string $recordTitleAttribute = 'name';

    // 🔥 FORM
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->maxLength(255),

            TextInput::make('phone')
                ->maxLength(50),

            TextInput::make('address')
                ->maxLength(255),
        ]);
    }

    // 🔥 TABLE
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('phone'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record))
                    ->modalHeading('Edit Employer')
                    ->modalSubmitActionLabel('Save Changes')
                    ->modalWidth('lg'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->visible(fn () => static::canCreate())
                    ->modalHeading('Create Employer')
                    ->modalSubmitActionLabel('Create')
                    ->modalWidth('lg'),

                DeleteBulkAction::make()
                    ->visible(fn () => static::canDeleteAny()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployers::route('/'),
        ];
    }

    // 🔥 NAVIGATION CONTROL
    public static function shouldRegisterNavigation(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('employers.view');
    }

    // 🔥 PERMISSIONS CONTROL (CORE FIX)

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('employers.view');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('employers.create');
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('employers.edit');
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('employers.delete');
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('employers.delete');
    }
}
