<?php

namespace App\Extensions;

use App\Extensions\Contracts\ExtensionInterface;
use App\Models\Extension;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ExtensionManager
{
    protected Collection $extensions;
    protected ExtensionRegistry $registry;
    protected array $enabledExtensions = [];
    protected bool $discovered = false;
    protected bool $registered = false;

    public function __construct(ExtensionRegistry $registry)
    {
        $this->extensions = collect();
        $this->registry = $registry;
    }

    /**
     * Discover all extensions in /extensions directory.
     * Safe to call multiple times - will only discover once.
     */
    public function discover(): void
    {
        // Only discover once
        if ($this->discovered) {
            return;
        }

        $extensionPath = base_path('extensions');

        if (!File::isDirectory($extensionPath)) {
            File::makeDirectory($extensionPath, 0755, true);
            $this->discovered = true;
            return;
        }

        $directories = File::directories($extensionPath);

        foreach ($directories as $dir) {
            $this->loadExtension($dir);
        }

        $this->discovered = true;
    }

    /**
     * Load a single extension from a directory.
     */
    protected function loadExtension(string $path): void
    {
        $metadataFile = $path . '/extension.json';

        if (!File::exists($metadataFile)) {
            return;
        }

        $metadata = json_decode(File::get($metadataFile), true);

        if (!$metadata || !isset($metadata['id'])) {
            return;
        }

        $extensionId = $metadata['id'];

        // Check if extension is enabled in database
        // Use try-catch to handle cases where database isn't ready yet
        try {
            $extension = Extension::where('identifier', $extensionId)->first();

            if (!$extension || !$extension->enabled) {
                return;
            }
        } catch (\Throwable $e) {
            // Database not available yet (e.g., during early service provider registration)
            // Skip this extension for now
            return;
        }

        // Register custom autoloader for this extension
        $this->registerExtensionAutoloader($path, $extensionId);

        // Load extension controller
        $controllerClass = "Extensions\\" . str($extensionId)->studly()->toString() . "\\" . ($metadata['controller'] ?? 'ExtensionController');

        if (!class_exists($controllerClass)) {
            // Auto-include the controller file
            $controllerFile = $path . '/' . ($metadata['controller'] ?? 'ExtensionController') . '.php';
            if (File::exists($controllerFile)) {
                require_once $controllerFile;
            }
        }

        if (!class_exists($controllerClass)) {
            return;
        }

        $controller = app($controllerClass);

        if (!$controller instanceof ExtensionInterface) {
            return;
        }

        $this->extensions->put($extensionId, [
            'metadata' => $metadata,
            'path' => $path,
            'controller' => $controller,
        ]);

        $this->enabledExtensions[] = $extensionId;
    }

    /**
     * Register all enabled extensions.
     * Safe to call multiple times - will only register once.
     */
    public function registerAll(): void
    {
        // Only register once
        if ($this->registered) {
            return;
        }

        $this->extensions->each(function ($extension) {
            $extension['controller']->register($this->registry);
        });

        $this->registered = true;
    }

    /**
     * Boot all enabled extensions.
     */
    public function bootAll(): void
    {
        $this->extensions->each(function ($extension) {
            $extension['controller']->boot();
        });
    }

    /**
     * Enable an extension.
     */
    public function enable(string $extensionId): void
    {
        $extensionPath = base_path("extensions/$extensionId");

        if (!File::isDirectory($extensionPath)) {
            throw new \Exception("Extension directory not found: $extensionId");
        }

        $metadataFile = $extensionPath . '/extension.json';

        if (!File::exists($metadataFile)) {
            throw new \Exception("Extension metadata file not found: extension.json");
        }

        $metadata = json_decode(File::get($metadataFile), true);

        if (!$metadata) {
            throw new \Exception("Invalid extension metadata file");
        }

        // Create/update extension in database
        $extension = Extension::updateOrCreate(
            ['identifier' => $extensionId],
            [
                'name' => $metadata['name'] ?? $extensionId,
                'version' => $metadata['version'] ?? '1.0.0',
                'author' => $metadata['author'] ?? null,
                'enabled' => true,
            ]
        );

        // Run migrations
        $this->runMigrations($extensionId);

        // Publish assets
        $this->publishAssets($extensionId);

        // Publish views
        $this->publishViews($extensionId);

        // Publish config
        $this->publishConfig($extensionId);

        // Publish Filament components (symlink to app/Filament)
        $this->publishFilamentComponents($extensionId);

        // Load and register extension
        $this->loadExtension($extensionPath);
        $this->registerAll();
    }

    /**
     * Disable an extension.
     */
    public function disable(string $extensionId): void
    {
        $extension = Extension::where('identifier', $extensionId)->first();

        if (!$extension) {
            return;
        }

        // Call disable hook
        if ($this->extensions->has($extensionId)) {
            $this->extensions->get($extensionId)['controller']->disable();
        }

        // Mark as disabled in database (keep migrations intact)
        $extension->update(['enabled' => false]);

        // Remove published assets
        $this->unpublishAssets($extensionId);

        // Remove published views
        $this->unpublishViews($extensionId);

        // Remove published Filament components
        $this->unpublishFilamentComponents($extensionId);

        // Remove published config
        $this->unpublishConfig($extensionId);
    }

    /**
     * Uninstall an extension (disable + rollback migrations + delete files).
     */
    public function uninstall(string $extensionId): void
    {
        $this->disable($extensionId);

        // Rollback migrations
        $this->rollbackMigrations($extensionId);

        // Delete the extension directory
        $extensionPath = base_path("extensions/{$extensionId}");
        if (\File::isDirectory($extensionPath)) {
            \File::deleteDirectory($extensionPath);
        }

        // Delete database record
        Extension::where('identifier', $extensionId)->delete();
    }

    /**
     * Delete an extension completely (alias for uninstall).
     */
    public function deleteExtension(string $extensionId): void
    {
        $this->uninstall($extensionId);
    }

    /**
     * Import an extension from a .zip file.
     *
     * @param string $zipPath Path to the .zip file
     * @param bool $autoEnable Whether to enable the extension after importing
     * @return array Returns ['success' => bool, 'message' => string, 'isUpdate' => bool, 'extensionId' => string|null]
     */
    public function importExtension(string $zipPath, bool $autoEnable = false): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Failed to open zip file', 'isUpdate' => false, 'extensionId' => null];
        }

        // Create temporary extraction directory
        $tempDir = base_path('storage/temp-extension-' . uniqid());
        \File::makeDirectory($tempDir, 0755, true);

        try {
            // Extract to temp directory
            $zip->extractTo($tempDir);
            $zip->close();

            // Look for extension.json in the extracted files
            $extensionJsonPath = null;
            $extensionRoot = null;

            // Check if extension.json is at root of zip
            if (\File::exists($tempDir . '/extension.json')) {
                $extensionJsonPath = $tempDir . '/extension.json';
                $extensionRoot = $tempDir;
            } else {
                // Look for extension.json in subdirectories (in case zip has a wrapper folder)
                $directories = \File::directories($tempDir);
                foreach ($directories as $dir) {
                    if (\File::exists($dir . '/extension.json')) {
                        $extensionJsonPath = $dir . '/extension.json';
                        $extensionRoot = $dir;
                        break;
                    }
                }
            }

            if (!$extensionJsonPath) {
                \File::deleteDirectory($tempDir);
                return ['success' => false, 'message' => 'extension.json not found in zip file', 'isUpdate' => false, 'extensionId' => null];
            }

            // Read extension.json
            $metadata = json_decode(\File::get($extensionJsonPath), true);
            if (!$metadata || !isset($metadata['id'])) {
                \File::deleteDirectory($tempDir);
                return ['success' => false, 'message' => 'Invalid extension.json format', 'isUpdate' => false, 'extensionId' => null];
            }

            $extensionId = $metadata['id'];
            $targetPath = base_path("extensions/{$extensionId}");

            // Check if extension already exists
            $isUpdate = \File::isDirectory($targetPath);

            if ($isUpdate) {
                // This is an update - delete the old extension first
                $this->deleteExtension($extensionId);
            }

            // Move from temp to extensions directory
            \File::moveDirectory($extensionRoot, $targetPath);

            // Clean up temp directory
            \File::deleteDirectory($tempDir);

            // Enable the extension if requested
            if ($autoEnable) {
                $this->enable($extensionId);
            }

            return [
                'success' => true,
                'message' => $isUpdate ? 'Extension updated successfully' : 'Extension imported successfully',
                'isUpdate' => $isUpdate,
                'extensionId' => $extensionId,
            ];

        } catch (\Exception $e) {
            // Clean up on error
            if (\File::isDirectory($tempDir)) {
                \File::deleteDirectory($tempDir);
            }

            return ['success' => false, 'message' => 'Error importing extension: ' . $e->getMessage(), 'isUpdate' => false, 'extensionId' => null];
        }
    }

    /**
     * Run migrations for an extension.
     */
    protected function runMigrations(string $extensionId): void
    {
        $migrationPath = base_path("extensions/$extensionId/migrations");

        if (!File::isDirectory($migrationPath)) {
            return;
        }

        Artisan::call('migrate', [
            '--path' => "extensions/$extensionId/migrations",
            '--force' => true,
        ]);

        // Track migrations in extension record
        $extension = Extension::where('identifier', $extensionId)->first();

        if ($extension) {
            $migrations = collect(File::files($migrationPath))
                ->map(fn ($file) => $file->getFilename())
                ->toArray();

            $extension->update(['migrations' => $migrations]);
        }
    }

    /**
     * Rollback migrations for an extension.
     */
    protected function rollbackMigrations(string $extensionId): void
    {
        $extension = Extension::where('identifier', $extensionId)->first();

        if (!$extension || empty($extension->migrations)) {
            return;
        }

        // Note: Laravel doesn't support rolling back specific migrations easily
        // This would require custom migration rollback logic
        // For now, we'll leave this as a placeholder
    }

    /**
     * Publish assets (copy to public directory).
     */
    protected function publishAssets(string $extensionId): void
    {
        $sourcePath = base_path("extensions/$extensionId/public");
        $targetPath = public_path("extensions/$extensionId");

        if (!File::isDirectory($sourcePath)) {
            return;
        }

        File::ensureDirectoryExists($targetPath);
        File::copyDirectory($sourcePath, $targetPath);

        // Assets published successfully
    }

    /**
     * Unpublish assets.
     */
    protected function unpublishAssets(string $extensionId): void
    {
        $targetPath = public_path("extensions/$extensionId");

        if (File::isDirectory($targetPath)) {
            File::deleteDirectory($targetPath);
        }

        // Assets unpublished successfully
    }

    /**
     * Publish views (symlink to resources/views/extensions).
     */
    protected function publishViews(string $extensionId): void
    {
        $sourcePath = base_path("extensions/$extensionId/views");
        $targetPath = resource_path("views/extensions/$extensionId");

        if (!File::isDirectory($sourcePath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($targetPath));

        // Remove existing symlink/directory if it exists
        if (File::exists($targetPath)) {
            if (is_link($targetPath)) {
                File::delete($targetPath);
            } else {
                File::deleteDirectory($targetPath);
            }
        }

        // Create symlink
        File::link($sourcePath, $targetPath);

        // Views published successfully
    }

    /**
     * Unpublish views.
     */
    protected function unpublishViews(string $extensionId): void
    {
        $targetPath = resource_path("views/extensions/$extensionId");

        if (File::exists($targetPath)) {
            // Handle both symlinks and directories
            if (is_link($targetPath)) {
                File::delete($targetPath);
            } elseif (File::isDirectory($targetPath)) {
                File::deleteDirectory($targetPath);
            }
        }

        // Views unpublished successfully
    }

    /**
     * Publish config (merge into app config).
     */
    protected function publishConfig(string $extensionId): void
    {
        $configFile = base_path("extensions/$extensionId/config/{$extensionId}.php");

        if (!File::exists($configFile)) {
            return;
        }

        // Load the config dynamically
        config([
            $extensionId => require $configFile,
        ]);

        // Config published successfully
    }

    /**
     * Unpublish config.
     */
    protected function unpublishConfig(string $extensionId): void
    {
        // Config is loaded dynamically, nothing to unpublish
    }

    /**
     * Publish Filament components (create symlinks).
     */
    protected function publishFilamentComponents(string $extensionId): void
    {
        $extensionPath = base_path("extensions/$extensionId");

        $panels = ['Admin', 'App', 'Server'];
        $componentTypes = ['Pages', 'Resources', 'Widgets'];

        foreach ($panels as $panel) {
            foreach ($componentTypes as $type) {
                $sourcePath = "$extensionPath/" . strtolower($panel) . "/$type";
                $targetPath = app_path("Filament/$panel/$type/Extensions/$extensionId");

                // Only create symlink if source directory exists and has files
                if (File::isDirectory($sourcePath) && !empty(File::files($sourcePath))) {
                    // Ensure parent directory exists with proper permissions
                    File::ensureDirectoryExists(dirname($targetPath));
                    @chmod(dirname($targetPath), 0775);

                    // Remove existing symlink/directory if it exists
                    if (File::exists($targetPath) || is_link($targetPath)) {
                        if (is_link($targetPath)) {
                            @unlink($targetPath);
                        } elseif (File::isDirectory($targetPath)) {
                            File::deleteDirectory($targetPath);
                        }
                    }

                    // Create symlink
                    try {
                        File::link($sourcePath, $targetPath);
                    } catch (\Exception $e) {
                        // Log but don't fail - symlink might already exist
                        \Log::warning("Failed to create symlink for extension $extensionId: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Unpublish Filament components (remove symlinks).
     */
    protected function unpublishFilamentComponents(string $extensionId): void
    {
        $panels = ['Admin', 'App', 'Server'];
        $componentTypes = ['Pages', 'Resources', 'Widgets'];

        foreach ($panels as $panel) {
            foreach ($componentTypes as $type) {
                $targetPath = app_path("Filament/$panel/$type/Extensions/$extensionId");

                if (File::exists($targetPath)) {
                    if (is_link($targetPath)) {
                        // Use unlink() directly for symlinks (more reliable than File::delete)
                        @unlink($targetPath);
                    } elseif (File::isDirectory($targetPath)) {
                        File::deleteDirectory($targetPath);
                    }
                }
            }
        }
    }

    /**
     * Get registry.
     */
    public function getRegistry(): ExtensionRegistry
    {
        return $this->registry;
    }

    /**
     * Get all enabled extensions.
     */
    public function getEnabledExtensions(): array
    {
        return $this->enabledExtensions;
    }

    /**
     * Get all loaded extensions.
     */
    public function getExtensions(): Collection
    {
        return $this->extensions;
    }

    /**
     * Register autoloaders for all extension directories (without database check).
     * This is called early in the service provider register() phase.
     */
    public function registerAutoloaders(): void
    {
        $extensionPath = base_path('extensions');

        if (!File::isDirectory($extensionPath)) {
            return;
        }

        $directories = File::directories($extensionPath);

        foreach ($directories as $dir) {
            $metadataFile = $dir . '/extension.json';

            if (!File::exists($metadataFile)) {
                continue;
            }

            $metadata = json_decode(File::get($metadataFile), true);

            if (!$metadata || !isset($metadata['id'])) {
                continue;
            }

            $extensionId = $metadata['id'];
            $this->registerExtensionAutoloader($dir, $extensionId);
        }
    }

    /**
     * Register a custom autoloader for an extension.
     * This handles the PSR-4 mismatch between kebab-case directory names and PascalCase namespaces.
     */
    protected function registerExtensionAutoloader(string $extensionPath, string $extensionId): void
    {
        $studlyId = str($extensionId)->studly()->toString();

        // Register autoloader for Filament components
        // Maps App\Filament\Admin\Pages\Extensions\ExampleExtension\* to extensions/example-extension/admin/Pages/*
        spl_autoload_register(function ($class) use ($studlyId, $extensionPath) {
            $panels = ['Admin', 'App', 'Server'];
            $types = ['Pages', 'Resources', 'Widgets'];

            foreach ($panels as $panel) {
                foreach ($types as $type) {
                    $prefix = "App\\Filament\\$panel\\$type\\Extensions\\$studlyId\\";

                    if (strpos($class, $prefix) === 0) {
                        $relativeClass = substr($class, strlen($prefix));
                        $file = $extensionPath . '/' . strtolower($panel) . '/' . $type . '/' . str_replace('\\', '/', $relativeClass) . '.php';

                        if (File::exists($file)) {
                            require_once $file;
                            return;
                        }
                    }
                }
            }
        });

        // Keep legacy namespace support for Services, etc.
        $namespace = 'Extensions\\' . $studlyId . '\\';

        spl_autoload_register(function ($class) use ($namespace, $extensionPath) {
            if (strpos($class, $namespace) !== 0) {
                return;
            }

            $relativeClass = substr($class, strlen($namespace));
            $file = $extensionPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            if (File::exists($file)) {
                require_once $file;
            }
        });
    }

    /**
     * Register extension components (pages, resources, widgets) for a specific panel.
     * This method should be called from panel providers.
     *
     * @param \Filament\Panel $panel The panel instance
     * @param string $panelId The panel ID ('admin', 'server', 'app')
     */
    public function registerPanelComponents(\Filament\Panel $panel, string $panelId): void
    {
        foreach ($this->extensions as $extensionId => $extension) {
            $extensionPath = $extension['path'];
            $studlyId = str($extensionId)->studly()->toString();
            $panelClass = str($panelId)->studly()->toString();

            // Register pages
            $pagesDir = "$extensionPath/" . strtolower($panelId) . "/Pages";
            if (File::isDirectory($pagesDir)) {
                foreach (File::allFiles($pagesDir) as $file) {
                    if ($file->getExtension() === 'php') {
                        $relativePath = str_replace([$pagesDir . '/', '.php'], '', $file->getPathname());
                        $className = "App\\Filament\\{$panelClass}\\Pages\\Extensions\\{$studlyId}\\" . str_replace('/', '\\', $relativePath);
                        if (class_exists($className)) {
                            $panel->pages([$className]);
                        }
                    }
                }
            }

            // Register resources
            $resourcesDir = "$extensionPath/" . strtolower($panelId) . "/Resources";
            if (File::isDirectory($resourcesDir)) {
                foreach (File::allFiles($resourcesDir) as $file) {
                    if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Resource.php')) {
                        $relativePath = str_replace([$resourcesDir . '/', '.php'], '', $file->getPathname());
                        $className = "App\\Filament\\{$panelClass}\\Resources\\Extensions\\{$studlyId}\\" . str_replace('/', '\\', $relativePath);
                        if (class_exists($className)) {
                            $panel->resources([$className]);
                        }
                    }
                }
            }

            // Register widgets
            $widgetsDir = "$extensionPath/" . strtolower($panelId) . "/Widgets";
            if (File::isDirectory($widgetsDir)) {
                foreach (File::allFiles($widgetsDir) as $file) {
                    if ($file->getExtension() === 'php') {
                        $relativePath = str_replace([$widgetsDir . '/', '.php'], '', $file->getPathname());
                        $className = "App\\Filament\\{$panelClass}\\Widgets\\Extensions\\{$studlyId}\\" . str_replace('/', '\\', $relativePath);
                        if (class_exists($className)) {
                            $panel->widgets([$className]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get user menu items for a specific panel.
     *
     * @param string $panelId The panel ID ('admin', 'server', 'app')
     * @return array Array of user menu items for this panel
     */
    public function getUserMenuItemsForPanel(string $panelId): array
    {
        $userMenuItems = [];

        foreach ($this->registry->getUserMenuItems() as $itemId => $config) {
            $panels = $config['panels'] ?? [];

            if (!isset($panels[$panelId]) || !$panels[$panelId]) {
                continue;
            }

            // Build the action
            $action = \Filament\Actions\Action::make($itemId)
                ->label(is_callable($config['label']) ? $config['label'] : fn() => $config['label'])
                ->url(is_callable($config['url']) ? $config['url'] : fn() => $config['url'])
                ->icon($config['icon'] ?? 'tabler-puzzle');

            // Add visible if specified
            if (isset($config['visible'])) {
                $action->visible(is_callable($config['visible']) ? $config['visible'] : fn() => $config['visible']);
            }

            $userMenuItems[$itemId] = $action;
        }

        return $userMenuItems;
    }

    /**
     * Get navigation items for a specific panel.
     *
     * @param string $panelId The panel ID ('admin', 'server', 'app')
     * @return array Array of navigation items for this panel
     */
    public function getNavigationItemsForPanel(string $panelId): array
    {
        $navigationItems = [];

        foreach ($this->registry->getNavigationItems() as $itemId => $config) {
            $panels = $config['panels'] ?? [];

            if (!isset($panels[$panelId]) || !$panels[$panelId]) {
                continue;
            }

            // Build the navigation item
            $navItem = \Filament\Navigation\NavigationItem::make($itemId)
                ->label(is_callable($config['label']) ? $config['label'] : fn() => $config['label'])
                ->url(is_callable($config['url']) ? $config['url'] : fn() => $config['url'])
                ->icon($config['icon'] ?? 'tabler-puzzle')
                ->sort($config['sort'] ?? 999);

            // Add group for admin panel
            if ($panelId === 'admin' && isset($config['group'])) {
                $navItem->group(is_callable($config['group']) ? $config['group'] : fn() => $config['group']);
            }

            // Add visible if specified
            if (isset($config['visible'])) {
                $navItem->visible(is_callable($config['visible']) ? $config['visible'] : fn() => $config['visible']);
            }

            $navigationItems[] = $navItem;
        }

        return $navigationItems;
    }
}
