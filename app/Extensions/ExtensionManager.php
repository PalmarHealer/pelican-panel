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

    /** @var array<string, ExtensionInterface> */
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
        $controllerClass = 'Extensions\\' . str($extensionId)->studly()->toString() . '\\' . ($metadata['controller'] ?? 'ExtensionController');

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

        $controller = \Illuminate\Support\Facades\App::make($controllerClass);

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

        $this->extensions->each(function ($extension, $extensionId) {
            // Set the current extension in the registry
            $this->registry->setCurrentExtension($extensionId);

            // Call extension's register method
            $extension['controller']->register($this->registry);

            // Clear the current extension
            $this->registry->setCurrentExtension(null);
        });

        $this->registered = true;
    }

    /**
     * Boot all enabled extensions.
     */
    public function bootAll(): void
    {
        $this->extensions->each(function ($extension, $extensionId) {
            // Load language pack translations if extension has lang directory
            $langPath = $extension['path'] . '/lang';
            if (File::isDirectory($langPath)) {
                $this->loadLanguagePackTranslations($extensionId, $langPath);
            }

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
            throw new \Exception('Extension metadata file not found: extension.json');
        }

        $metadata = json_decode(File::get($metadataFile), true);

        if (!$metadata) {
            throw new \Exception('Invalid extension metadata file');
        }

        // Create/update extension in database
        $extension = Extension::updateOrCreate(
            ['identifier' => $extensionId],
            [
                'name' => $metadata['name'] ?? $extensionId,
                'description' => $metadata['description'] ?? null,
                'version' => $metadata['version'] ?? '1.0.0',
                'author' => $metadata['author'] ?? null,
                'types' => $metadata['types'] ?? ['plugin'],
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

        // Publish themes and language packs based on types
        $types = $metadata['types'] ?? ['plugin'];
        if (in_array('theme', $types)) {
            $this->publishTheme($extensionId);
        }
        if (in_array('language-pack', $types)) {
            $result = $this->publishLanguagePack($extensionId);

            // Check for conflicts
            if (!empty($result['conflicts'])) {
                // Rollback: disable the extension
                $extension->update(['enabled' => false]);

                // Clean up what we've published so far
                $this->unpublishAssets($extensionId);
                $this->unpublishViews($extensionId);
                $this->unpublishFilamentComponents($extensionId);
                $this->unpublishConfig($extensionId);
                if (in_array('theme', $types)) {
                    $this->unpublishTheme($extensionId);
                }

                // Build conflict message
                $conflictMessages = collect($result['conflicts'])->map(function (array $conflict) {
                    return "'{$conflict['file']}' is already overridden by '{$conflict['blocking_extension']}'";
                })->join(', ');

                throw new \Exception(
                    "Language pack conflict detected: $conflictMessages. " .
                    'Please disable the conflicting extension(s) first before enabling this extension.'
                );
            }
        }

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

        // Unpublish themes and language packs
        $this->unpublishTheme($extensionId);
        $this->unpublishLanguagePack($extensionId);
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
     * @param  string  $zipPath  Path to the .zip file
     * @param  bool  $autoEnable  Whether to enable the extension after importing
     * @return array{success: bool, message: string, isUpdate: bool, extensionId: string|null}
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

            // Copy from temp to extensions directory (don't use moveDirectory as it may fail across volumes)
            if (!\File::copyDirectory($extensionRoot, $targetPath)) {
                throw new \Exception("Failed to copy extension files to extensions directory. Check permissions on: $targetPath");
            }

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
     *
     * @return array<string, ExtensionInterface>
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
     * @param  \Filament\Panel  $panel  The panel instance
     * @param  string  $panelId  The panel ID ('admin', 'server', 'app')
     */
    public function registerPanelComponents(\Filament\Panel $panel, string $panelId): void
    {
        foreach ($this->extensions as $extensionId => $extension) {
            $extensionPath = $extension['path'];
            $studlyId = str($extensionId)->studly()->toString();
            $panelClass = str($panelId)->studly()->toString();

            // Register pages
            $pagesDir = "$extensionPath/" . strtolower($panelId) . '/Pages';
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
            $resourcesDir = "$extensionPath/" . strtolower($panelId) . '/Resources';
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
            $widgetsDir = "$extensionPath/" . strtolower($panelId) . '/Widgets';
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
     * @param  string  $panelId  The panel ID ('admin', 'server', 'app')
     * @return array<mixed>
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
                ->label(is_callable($config['label']) ? $config['label'] : fn () => $config['label'])
                ->url(is_callable($config['url']) ? $config['url'] : fn () => $config['url'])
                ->icon($config['icon'] ?? 'tabler-puzzle');

            // Add visible if specified
            if (isset($config['visible'])) {
                $action->visible(is_callable($config['visible']) ? $config['visible'] : fn () => $config['visible']);
            }

            $userMenuItems[$itemId] = $action;
        }

        return $userMenuItems;
    }

    /**
     * Get navigation items for a specific panel.
     *
     * @param  string  $panelId  The panel ID ('admin', 'server', 'app')
     * @return array<mixed>
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
                ->label(is_callable($config['label']) ? $config['label'] : fn () => $config['label'])
                ->url(is_callable($config['url']) ? $config['url'] : fn () => $config['url'])
                ->icon($config['icon'] ?? 'tabler-puzzle')
                ->sort($config['sort'] ?? 999);

            // Add group for admin panel
            if ($panelId === 'admin' && isset($config['group'])) {
                $navItem->group(is_callable($config['group']) ? $config['group'] : fn () => $config['group']);
            }

            // Add egg tag restriction for server panel
            if ($panelId === 'server' && isset($config['egg_tags'])) {
                $eggTags = $config['egg_tags'];
                $existingVisible = $config['visible'] ?? null;

                $navItem->visible(function () use ($eggTags, $existingVisible) {
                    // Check existing visibility condition first
                    if ($existingVisible !== null) {
                        $isVisible = is_callable($existingVisible) ? $existingVisible() : $existingVisible;
                        if (!$isVisible) {
                            return false;
                        }
                    }

                    // Check egg tag restriction
                    $server = \Filament\Facades\Filament::getTenant();
                    if (!$server) {
                        return false;
                    }

                    $serverEggTags = $server->egg->tags ?? [];
                    foreach ($eggTags as $requiredTag) {
                        if (in_array($requiredTag, $serverEggTags)) {
                            return true;
                        }
                    }

                    return false;
                });
            } elseif (isset($config['visible'])) {
                // Add visible if specified (without egg tag restriction)
                $navItem->visible(is_callable($config['visible']) ? $config['visible'] : fn () => $config['visible']);
            }

            $navigationItems[] = $navItem;
        }

        return $navigationItems;
    }

    /**
     * Publish theme files (copy CSS to resources/css/themes/).
     */
    protected function publishTheme(string $extensionId): void
    {
        $sourcePath = base_path("extensions/$extensionId/theme");
        $targetPath = resource_path("css/themes/$extensionId");

        if (!File::isDirectory($sourcePath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($targetPath));

        // Remove existing directory if it exists
        if (File::exists($targetPath)) {
            if (is_link($targetPath)) {
                File::delete($targetPath);
            } else {
                File::deleteDirectory($targetPath);
            }
        }

        // Create symlink
        File::link($sourcePath, $targetPath);
    }

    /**
     * Unpublish theme files.
     */
    protected function unpublishTheme(string $extensionId): void
    {
        $targetPath = resource_path("css/themes/$extensionId");

        if (File::exists($targetPath)) {
            if (is_link($targetPath)) {
                File::delete($targetPath);
            } elseif (File::isDirectory($targetPath)) {
                File::deleteDirectory($targetPath);
            }
        }
    }

    /**
     * Publish language pack (symlink to resources/lang/).
     * Supports three modes:
     * 1. New language: extensions/ext/lang/fr/ -> lang/fr/ (new complete language)
     * 2. Overrides: extensions/ext/lang/overrides/en/ -> merges with lang/en/
     * 3. Extension namespace: extensions/ext/lang/en/ -> accessible via trans('ext::file.key')
     *
     * @return array{success: bool, conflicts: list<array{file: non-falsy-string, blocking_extension: string, blocking_extension_id: string}>}
     */
    protected function publishLanguagePack(string $extensionId): array
    {
        $sourcePath = base_path("extensions/$extensionId/lang");

        if (!File::isDirectory($sourcePath)) {
            return ['success' => true, 'conflicts' => []];
        }

        $allOverrides = [];
        $allConflicts = [];

        // Process new languages and overrides
        $directories = File::directories($sourcePath);

        foreach ($directories as $dir) {
            $langCode = basename($dir);

            // Handle overrides directory
            if ($langCode === 'overrides') {
                $result = $this->publishLanguageOverrides($extensionId, $dir);
                $allOverrides = array_merge($allOverrides, $result['overrides']);
                $allConflicts = array_merge($allConflicts, $result['conflicts']);

                continue;
            }

            // Handle new language (e.g., extensions/ext/lang/fr/ -> lang/fr/)
            // This creates a complete new language
            if (!File::isDirectory(base_path("lang/$langCode"))) {
                $targetPath = base_path("lang/$langCode");
                File::ensureDirectoryExists(dirname($targetPath));

                // Remove existing if present
                if (File::exists($targetPath)) {
                    if (is_link($targetPath)) {
                        File::delete($targetPath);
                    } else {
                        File::deleteDirectory($targetPath);
                    }
                }

                // Create symlink for new language
                File::link($dir, $targetPath);
            }
        }

        // Save override tracking to database
        if (!empty($allOverrides)) {
            $extension = Extension::where('identifier', $extensionId)->first();
            if ($extension) {
                $extension->update(['language_overrides' => $allOverrides]);
            }
        }

        // Register namespace for extension translations (e.g., trans('myext::messages.welcome'))
        $this->loadLanguagePackTranslations($extensionId, $sourcePath);

        return [
            'success' => empty($allConflicts),
            'conflicts' => $allConflicts,
        ];
    }

    /**
     * Publish language overriding with conflict detection.
     * Copies override files to lang/ directories and merge with existing translations.
     *
     * @return array{success: bool, conflicts: list<array{file: non-falsy-string, blocking_extension: string, blocking_extension_id: string}>, overrides: list<non-falsy-string>}
     */
    protected function publishLanguageOverrides(string $extensionId, string $overridesPath): array
    {
        $overrideLangDirs = File::directories($overridesPath);
        $conflicts = [];
        $successfulOverrides = [];
        $filesToProcess = [];

        foreach ($overrideLangDirs as $langDir) {
            $langCode = basename($langDir);
            $targetLangDir = base_path("lang/$langCode");

            // Skip if target language doesn't exist
            if (!File::isDirectory($targetLangDir)) {
                continue;
            }

            // Check each override file for conflicts
            $overrideFiles = File::files($langDir);
            foreach ($overrideFiles as $file) {
                $filename = $file->getFilename();
                $targetFile = "$targetLangDir/$filename";
                $overrideKey = "$langCode/$filename";

                // Check if another extension has already overridden this file
                $blockingExtension = $this->findExtensionOverridingFile($overrideKey, $extensionId);

                if ($blockingExtension) {
                    $conflicts[] = [
                        'file' => $overrideKey,
                        'blocking_extension' => $blockingExtension->name,
                        'blocking_extension_id' => $blockingExtension->identifier,
                    ];
                } else {
                    // No conflict, add to queue for processing
                    $filesToProcess[] = [
                        'file' => $file,
                        'targetFile' => $targetFile,
                        'overrideKey' => $overrideKey,
                    ];
                }
            }
        }

        // If ANY conflicts found, return immediately without processing ANY files
        if (!empty($conflicts)) {
            return [
                'success' => false,
                'conflicts' => $conflicts,
                'overrides' => [],
            ];
        }

        // No conflicts found, now process all files
        foreach ($filesToProcess as $fileData) {
            $file = $fileData['file'];
            $targetFile = $fileData['targetFile'];
            $overrideKey = $fileData['overrideKey'];

            if (File::exists($targetFile)) {
                $backupFile = "$targetFile.backup-before-$extensionId";

                // Only create backup if this is the first override (no backup exists)
                if (!File::exists($backupFile)) {
                    File::copy($targetFile, $backupFile);
                }

                // Merge translations
                $original = require $targetFile;
                $override = require $file->getPathname();
                $merged = array_replace_recursive($original, $override);

                // Write merged translations
                $export = "<?php\n\nreturn " . var_export($merged, true) . ";\n";
                File::put($targetFile, $export);

                $successfulOverrides[] = $overrideKey;
            } else {
                // No original file, just copy the override
                File::copy($file->getPathname(), $targetFile);
                $successfulOverrides[] = $overrideKey;
            }
        }

        return [
            'success' => true,
            'conflicts' => [],
            'overrides' => $successfulOverrides,
        ];
    }

    /**
     * Find which extension (if any) has overridden a specific language file.
     *
     * @param  string  $fileKey  Format: "locale/filename.php" (e.g., "en/activity.php")
     * @param  string|null  $excludeExtensionId  Extension to exclude from search
     */
    protected function findExtensionOverridingFile(string $fileKey, ?string $excludeExtensionId = null): ?Extension
    {
        $query = Extension::where('enabled', true)
            ->whereNotNull('language_overrides');

        if ($excludeExtensionId) {
            $query->where('identifier', '!=', $excludeExtensionId);
        }

        $extensions = $query->get();

        foreach ($extensions as $extension) {
            $overrides = $extension->language_overrides ?? [];
            if (in_array($fileKey, $overrides)) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * Load language pack translations with extension namespace.
     * Allows accessing translations via trans('extensionId::file.key')
     */
    protected function loadLanguagePackTranslations(string $extensionId, string $sourcePath): void
    {
        // Register the namespace with Laravel's translator
        \Illuminate\Support\Facades\App::make('translator')->addNamespace($extensionId, $sourcePath);
    }

    /**
     * Unpublish language pack.
     */
    protected function unpublishLanguagePack(string $extensionId): void
    {
        $sourcePath = base_path("extensions/$extensionId/lang");

        if (!File::isDirectory($sourcePath)) {
            return;
        }

        // Remove new languages created by this extension
        $directories = File::directories($sourcePath);

        foreach ($directories as $dir) {
            $langCode = basename($dir);

            // Skip overrides directory
            if ($langCode === 'overrides') {
                $this->unpublishLanguageOverrides($extensionId, $dir);

                continue;
            }

            // Remove symlinked language if it exists
            // Note: On Windows, is_link() may not properly detect directory symlinks,
            // so we also check if the real path points to the extension directory
            $targetPath = base_path("lang/$langCode");
            if (File::exists($targetPath)) {
                $isSymlink = is_link($targetPath);

                // On Windows, also check if realpath points to extension directory
                if (!$isSymlink && PHP_OS_FAMILY === 'Windows') {
                    $realPath = realpath($targetPath);
                    $extensionLangPath = realpath($dir);
                    $isSymlink = ($realPath === $extensionLangPath);
                }

                if ($isSymlink) {
                    // For directory symlinks on Windows, use rmdir instead of unlink
                    // This safely removes the symlink without deleting the target directory
                    if (PHP_OS_FAMILY === 'Windows') {
                        @rmdir($targetPath);
                    } else {
                        // On Unix-like systems, unlink works for symlinks
                        @unlink($targetPath);
                    }
                }
            }
        }
    }

    /**
     * Unpublish language overrides (restore backups).
     * Only restores files that THIS extension overrode (selective restoration).
     */
    protected function unpublishLanguageOverrides(string $extensionId, string $overridesPath): void
    {
        // Get the list of files this extension overrode
        $extension = Extension::where('identifier', $extensionId)->first();
        $trackedOverrides = $extension !== null ? $extension->language_overrides : null;
        $trackedOverrides = $trackedOverrides ?? [];

        if (empty($trackedOverrides)) {
            return; // No overrides to restore
        }

        $overrideLangDirs = File::directories($overridesPath);

        foreach ($overrideLangDirs as $langDir) {
            $langCode = basename($langDir);
            $targetLangDir = base_path("lang/$langCode");

            if (!File::isDirectory($targetLangDir)) {
                continue;
            }

            // Restore only the files that this extension actually overrode
            $overrideFiles = File::files($langDir);
            foreach ($overrideFiles as $file) {
                $filename = $file->getFilename();
                $overrideKey = "$langCode/$filename";

                // Only restore if this extension owns this override
                if (!in_array($overrideKey, $trackedOverrides)) {
                    continue;
                }

                $targetFile = "$targetLangDir/$filename";
                $backupFile = "$targetFile.backup-before-$extensionId";

                if (File::exists($backupFile)) {
                    // Restore original from backup
                    File::copy($backupFile, $targetFile);
                    File::delete($backupFile);
                } else {
                    // No backup exists, remove the file if it was added by extension
                    if (File::exists($targetFile)) {
                        File::delete($targetFile);
                    }
                }
            }
        }

        // Clear the override tracking from database
        if ($extension) {
            $extension->update(['language_overrides' => null]);
        }
    }
}
