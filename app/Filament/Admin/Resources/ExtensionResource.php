<?php

namespace App\Filament\Admin\Resources;

use App\Enums\RolePermissionModels;
use App\Filament\Admin\Resources\ExtensionResource\Pages;
use App\Models\Extension;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class ExtensionResource extends Resource
{
    protected static ?string $model = Extension::class;

    protected static ?string $slug = 'extensions';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-puzzle';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Extensions';
    }

    public static function getModelLabel(): string
    {
        return 'Extension';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Extensions';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('enabled', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canViewAny(): bool
    {
        return user()?->can(RolePermissionModels::Extension->viewAny()) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return user()?->can(RolePermissionModels::Extension->view()) ?? false;
    }

    public static function canCreate(): bool
    {
        return false; // Extensions are created by placing them in the extensions directory
    }

    public static function canEdit(Model $record): bool
    {
        return user()?->can(RolePermissionModels::Extension->update()) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Extensions are uninstalled, not deleted
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExtensions::route('/'),
            'view' => Pages\ViewExtension::route('/{record}'),
        ];
    }
}
