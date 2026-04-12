<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use App\Models\Employer;
use App\Models\Permission;
use BackedEnum;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $recordTitleAttribute = 'name';

    // 🔥 FORM (FIXED)
    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Select::make('employer_id')
                ->label('Employer')
                ->options(fn () => Employer::pluck('name', 'id')->toArray())
                ->searchable()
                ->nullable()
                ->visible(fn () => auth()->user()->isSystemAdmin())
                ->helperText('Leave empty for Global Role'),

            // ✅ CLEAN PERMISSIONS (NO GROUPING BUGS)
            CheckboxList::make('permissions')
                ->label('Permissions')
                ->relationship('permissions', 'name')

                ->options(function () {
                    return \App\Models\Permission::query()
                        ->orderBy('name') // 🔥 better UX
                        ->get()
                        ->mapWithKeys(function ($permission) {

                            $group = ucfirst(\Illuminate\Support\Str::before($permission->name, '.'));
                            $action = \Illuminate\Support\Str::after($permission->name, '.');

                            return [
                                $permission->id => "{$group} → {$action}",
                            ];
                        })
                        ->toArray();
                })

                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3, // 🔥 responsive 3 columns
                ])

                ->gridDirection('row') // 🔥 FIX uneven layout

                ->bulkToggleable()
                ->searchable()

                ->columnSpanFull() // 🔥 FULL WIDTH FIX
        ]);
    }

    // 🔥 TABLE
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),

                TextColumn::make('employer.name')
                    ->label('Employer')
                    ->formatStateUsing(fn ($state) => $state ?: 'Global')
                    ->badge(),

                TextColumn::make('permissions.name')
                    ->label('Permissions')
                    ->badge()
                    ->limit(3),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record))
                    ->modalWidth('4xl'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->visible(fn () => static::canCreate())
                    ->modalWidth('4xl'),

                DeleteBulkAction::make()
                    ->visible(fn () => static::canDeleteAny()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->where('employer_id', $user->employer_id);
    }

    // 🔥 PERMISSIONS CONTROL

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('roles.view');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('roles.create');
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('roles.edit');
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('roles.delete');
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('roles.delete');
    }

    // 🔥 SIDEBAR CONTROL
    public static function shouldRegisterNavigation(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->isSuperAdmin()
            || $user->hasPermissionTo('roles.view');
    }
}
