<?php

namespace App\Filament\Server\Pages\Extensions\ExampleExtension;

use App\Filament\Server\Pages\Concerns\HasExtensionPermissions;
use App\Filament\Server\Pages\Concerns\RestrictedByEggTags;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class ExampleServerPage extends Page
{
    use HasExtensionPermissions, RestrictedByEggTags;

    protected static ?string $slug = 'addon/example-server-page';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-sparkles';

    protected string $view = 'extensions.example-extension.server.pages.example-server-page';

    protected static ?int $navigationSort = 50;

    public function mount(): void
    {
        // Get all permissions the user has for this server using the trait
        $this->userPermissions = $this->getExtensionPermissions('example-extension');
    }

    public static function getNavigationLabel(): string
    {
        return 'Example Feature';
    }

    public function getTitle(): string
    {
        return 'Example Server Feature';
    }

    public function getHeading(): string
    {
        $server = Filament::getTenant();

        return 'Example Feature for ' . $server?->name;
    }

    /**
     * Check if the user can access this extension page.
     *
     * This page demonstrates egg tag restrictions - it's only available for vanilla/java servers.
     * The restrictions are defined in extension.json and checked automatically by checkEggRestrictions().
     *
     * Access requires:
     * 1. Parent access checks (server exists, not suspended, etc.)
     * 2. Server has matching egg tag (vanilla or java) - checked by RestrictedByEggTags trait
     * 3. User has the 'example_feature.read' permission
     */
    public static function canAccess(): bool
    {
        return parent::canAccess()
            && static::checkEggRestrictions() // Automatically checks egg tags from extension.json
            && (user()?->can('example_feature.read', Filament::getTenant()) ?? false);
    }
}
