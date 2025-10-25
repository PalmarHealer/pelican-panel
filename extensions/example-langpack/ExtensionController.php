<?php

namespace Extensions\ExampleLangpack;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Language pack extensions don't need to register anything here
        // Translations are automatically loaded by ExtensionManager
    }

    public function boot(): void
    {
        // Can listen to events, register middleware, etc.
    }

    public function disable(): void
    {
        // Cleanup when disabled
    }
}
