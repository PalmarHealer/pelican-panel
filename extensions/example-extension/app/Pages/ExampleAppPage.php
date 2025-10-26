<?php

namespace App\Filament\App\Pages\Extensions\ExampleExtension;

use Filament\Pages\Page;

class ExampleAppPage extends Page
{
    protected static ?string $slug = 'extensions/example-app-page';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-sparkles';

    protected string $view = 'extensions.example-extension.app.pages.example-app-page';

    protected static ?int $navigationSort = 50;

    // App panel has navigation disabled by default, but pages can still be accessed by URL

    public static function getNavigationLabel(): string
    {
        return 'Example App Page';
    }

    public function getTitle(): string
    {
        return 'Example App Page';
    }

    public function getHeading(): string
    {
        return 'Example App Page';
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Don't show in navigation since app panel has navigation disabled
        return false;
    }

    // This method will be called by Filament to check if the user can access this page
    public static function canAccess(): bool
    {
        // No permission required - accessible to all authenticated users
        return true;
    }
}
