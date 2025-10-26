<?php

namespace Extensions\DarkTheme;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;
use Filament\View\PanelsRenderHook;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Register a render hook to inject our theme CSS
        $registry->renderHook(
            PanelsRenderHook::HEAD_END,
            fn () => $this->injectThemeStyles()
        );
    }

    public function boot(): void
    {
        // Boot logic if needed
    }

    public function disable(): void
    {
        // Cleanup when disabled
    }

    /**
     * Inject theme styles into the panel head.
     */
    protected function injectThemeStyles(): string
    {
        // Link to the CSS file published in public/extensions/dark-theme/
        // Add version parameter for cache busting
        $cssUrl = asset('extensions/dark-theme/style.css?v=1.0.0');

        return <<<HTML
        <!-- Dark Theme Extension -->
        <link rel="stylesheet" href="{$cssUrl}">
        HTML;
    }
}
