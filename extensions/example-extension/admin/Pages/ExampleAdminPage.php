<?php

namespace App\Filament\Admin\Pages\Extensions\ExampleExtension;

use Filament\Pages\Page;

class ExampleAdminPage extends Page
{
    protected static ?string $slug = 'extensions/example-admin-page';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-sparkles';

    protected string $view = 'extensions.example-extension.admin.pages.example-admin-page';

    protected static ?int $navigationSort = 102;

    public static function getNavigationGroup(): ?string
    {
        return trans('admin/dashboard.advanced');
    }

    public static function getNavigationLabel(): string
    {
        return 'Example Admin Page';
    }

    public function getTitle(): string
    {
        return 'Example Admin Page';
    }

    public function getHeading(): string
    {
        return 'Example Admin Page';
    }

    // This method will be called by Filament to check if the user can access this page
    public static function canAccess(): bool
    {
        // Check if user has the custom permission registered by the extension
        // Note: permissions are stored as lowercase in the database
        return user()?->can('viewList exampleextension') ?? false;
    }
}
