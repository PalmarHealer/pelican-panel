<?php

namespace Extensions\GermanLangpack;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Language pack extensions don't need to register anything here
    }

    public function boot(): void
    {
        // Nothing to boot
    }

    public function disable(): void
    {
        // Cleanup when disabled
    }
}
