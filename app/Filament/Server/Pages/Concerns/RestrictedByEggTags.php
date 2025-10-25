<?php

namespace App\Filament\Server\Pages\Concerns;

use App\Extensions\ExtensionManager;
use Filament\Facades\Filament;

/**
 * Trait for extension server pages to automatically restrict access based on egg tags.
 *
 * Usage:
 *   1. Add `use RestrictedByEggTags;` to your extension server page
 *   2. The trait will automatically check egg restrictions registered via ExtensionRegistry
 *   3. Override canAccess() in your page to add additional checks:
 *      ```
 *      public static function canAccess(): bool
 *      {
 *          return parent::canAccess()
 *              && static::checkEggRestrictions()
 *              && user()?->can('your_permission.action', Filament::getTenant());
 *      }
 *      ```
 *
 * Note: This trait automatically overrides shouldRegisterNavigation() to hide the page
 * from navigation when egg restrictions don't match. You don't need to do anything extra!
 */
trait RestrictedByEggTags
{
    /**
     * Check if the current server's egg matches the page's egg restrictions.
     * Returns true if page is allowed, false if restricted.
     */
    public static function checkEggRestrictions(): bool
    {
        $server = Filament::getTenant();

        if (!$server) {
            return false;
        }

        $manager = app(ExtensionManager::class);

        // Ensure extensions are discovered and registered
        $manager->discover();
        $manager->registerAll();

        $registry = $manager->getRegistry();

        return $registry->isServerPageAllowed(static::class, $server);
    }

    /**
     * Get the navigation group.
     * Setting this to null prevents the page from appearing in navigation.
     */
    public static function getNavigationGroup(): ?string
    {
        if (!static::checkEggRestrictions()) {
            return null;
        }

        return parent::getNavigationGroup();
    }

    /**
     * Get the navigation label.
     * Return null if egg restrictions don't match to hide from navigation.
     */
    public static function getNavigationLabel(): ?string
    {
        if (!static::checkEggRestrictions()) {
            return null;
        }

        return parent::getNavigationLabel();
    }

    /**
     * Get the navigation icon.
     * Return null if egg restrictions don't match.
     */
    public static function getNavigationIcon(): ?string
    {
        if (!static::checkEggRestrictions()) {
            return null;
        }

        return parent::getNavigationIcon();
    }

    /**
     * Get the navigation sort order.
     * Return null if egg restrictions don't match.
     */
    public static function getNavigationSort(): ?int
    {
        if (!static::checkEggRestrictions()) {
            return null;
        }

        return parent::getNavigationSort();
    }

    /**
     * Determine if navigation should be registered.
     * Prevents navigation item from being created if egg restrictions don't match.
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Check egg restrictions first
        if (!static::checkEggRestrictions()) {
            return false;
        }

        // Then check parent
        return parent::shouldRegisterNavigation();
    }

    /**
     * Get the navigation items for this page.
     * Return empty array if egg restrictions don't match as a fallback.
     */
    public static function getNavigationItems(): array
    {
        // Check egg restrictions
        if (!static::checkEggRestrictions()) {
            return [];
        }

        return parent::getNavigationItems();
    }
}
