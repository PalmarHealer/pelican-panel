<?php

namespace App\Extensions\Contracts;

use App\Extensions\ExtensionRegistry;

interface ExtensionInterface
{
    /**
     * Called when extension is enabled and needs to register components.
     */
    public function register(ExtensionRegistry $registry): void;

    /**
     * Called after all extensions are registered.
     * Use this for event listeners, middleware, etc.
     */
    public function boot(): void;

    /**
     * Called when extension is disabled.
     * Use this for cleanup logic.
     */
    public function disable(): void;
}
