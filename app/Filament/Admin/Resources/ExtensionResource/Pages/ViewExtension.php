<?php

namespace App\Filament\Admin\Resources\ExtensionResource\Pages;

use App\Extensions\ExtensionManager;
use App\Extensions\ExtensionRegistry;
use App\Filament\Admin\Resources\ExtensionResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\File;

class ViewExtension extends Page
{
    protected static string $resource = ExtensionResource::class;

    public \App\Models\Extension $record;

    public function getView(): string
    {
        return 'filament.admin.resources.extension-resource.pages.view-extension';
    }

    public function mount(int|string $record): void
    {
        /** @var \App\Models\Extension $resolvedRecord */
        $resolvedRecord = ExtensionResource::resolveRecordRouteBinding($record);
        $this->record = $resolvedRecord;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle')
                ->label(fn () => $this->record->enabled ? 'Disable' : 'Enable')
                ->icon(fn () => $this->record->enabled ? 'tabler-square-x' : 'tabler-square-check')
                ->color(fn () => $this->record->enabled ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->enabled ? 'Disable Extension' : 'Enable Extension')
                ->modalDescription(fn () => $this->record->enabled
                    ? 'Are you sure you want to disable this extension?'
                    : 'Are you sure you want to enable this extension?')
                ->action(function () {
                    /** @var ExtensionManager $manager */
                    $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);

                    try {
                        if ($this->record->enabled) {
                            $manager->disable($this->record->identifier);
                            Notification::make()
                                ->title('Extension disabled')
                                ->success()
                                ->send();
                        } else {
                            $manager->enable($this->record->identifier);
                            Notification::make()
                                ->title('Extension enabled')
                                ->success()
                                ->send();
                        }

                        // Reload the page to reflect changes
                        redirect(request()->header('Referer'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('export')
                ->label('Export')
                ->icon('tabler-download')
                ->color('info')
                ->action(function () {
                    try {
                        $extensionPath = base_path("extensions/{$this->record->identifier}");

                        if (!File::isDirectory($extensionPath)) {
                            Notification::make()
                                ->title('Extension not found')
                                ->body('The extension directory does not exist.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Create a temporary zip file
                        $zipFileName = "{$this->record->identifier}-" . now()->format('Y-m-d-His') . '.zip';
                        $zipPath = storage_path("app/temp/{$zipFileName}");

                        // Ensure temp directory exists
                        if (!File::isDirectory(storage_path('app/temp'))) {
                            File::makeDirectory(storage_path('app/temp'), 0755, true);
                        }

                        // Create zip archive
                        $zip = new \ZipArchive();
                        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                            throw new \Exception('Failed to create ZIP archive');
                        }

                        // Add all files from extension directory
                        $files = File::allFiles($extensionPath);
                        foreach ($files as $file) {
                            $relativePath = str_replace($extensionPath . '/', '', $file->getPathname());
                            $zip->addFile($file->getPathname(), $relativePath);
                        }

                        $zip->close();

                        // Return download response and clean up after download
                        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Export failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('remove')
                ->label('Remove')
                ->icon('tabler-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Remove Extension')
                ->modalDescription('This will completely remove the extension from your system, including all files, migrations, and database records. This action cannot be undone.')
                ->visible(fn () => !$this->record->enabled)
                ->action(function () {
                    /** @var ExtensionManager $manager */
                    $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);

                    try {
                        $manager->uninstall($this->record->identifier);

                        Notification::make()
                            ->title('Extension deleted')
                            ->body('The extension and all its files have been permanently removed.')
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.extensions.index');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to delete extension')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getViewData(): array
    {
        $extensionPath = base_path("extensions/{$this->record->identifier}");
        $metadataFile = $extensionPath . '/extension.json';
        $metadata = File::exists($metadataFile) ? json_decode(File::get($metadataFile), true) : [];

        /** @var ExtensionManager $manager */
        $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);
        $registry = $manager->getRegistry();

        // Get registrations from registry
        $registrations = $this->getExtensionRegistrations($registry);

        // Get language pack info if it's a language pack
        $languageInfo = $this->getLanguagePackInfo($extensionPath);

        // Get theme info if it's a theme
        $themeInfo = $this->getThemeInfo($extensionPath);

        return compact('metadata', 'registrations', 'extensionPath', 'languageInfo', 'themeInfo');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getExtensionRegistrations(ExtensionRegistry $registry): array
    {
        $extensionId = $this->record->identifier;
        $studlyId = str($extensionId)->studly()->toString();

        // Auto-discovered components via symlinks
        $extensionPath = base_path("extensions/{$extensionId}");
        $panels = ['Admin', 'App', 'Server'];
        $types = ['Pages', 'Resources', 'Widgets'];

        $discovered = [];

        foreach ($panels as $panel) {
            foreach ($types as $type) {
                $dir = $extensionPath . '/' . strtolower($panel) . '/' . $type;
                if (File::isDirectory($dir)) {
                    $files = File::allFiles($dir);
                    $classes = [];
                    foreach ($files as $file) {
                        if ($file->getExtension() === 'php') {
                            $relativePath = str_replace([$dir . '/', '.php'], '', $file->getPathname());
                            $className = "App\\Filament\\{$panel}\\{$type}\\Extensions\\{$studlyId}\\" . str_replace('/', '\\', $relativePath);
                            if (class_exists($className)) {
                                $classes[] = [
                                    'class' => $className,
                                    'name' => class_basename($className),
                                ];
                            }
                        }
                    }
                    $key = strtolower($panel) . $type;
                    $discovered[$key] = $classes;
                }
            }
        }

        // Get navigation items with panel info
        $navigationItems = collect($registry->getNavigationItems())
            ->map(function ($config, $itemId) {
                $panels = [];
                foreach ($config['panels'] ?? [] as $panel => $enabled) {
                    if ($enabled) {
                        $panels[] = ucfirst($panel);
                    }
                }

                return [
                    'id' => $itemId,
                    'label' => is_callable($config['label']) ? 'Dynamic Label' : $config['label'],
                    'panels' => implode(', ', $panels),
                    'icon' => $config['icon'] ?? 'tabler-puzzle',
                ];
            })
            ->values()
            ->toArray();

        // Get user menu items with panel info
        $userMenuItems = collect($registry->getUserMenuItems())
            ->map(function ($config, $itemId) {
                $panels = [];
                foreach ($config['panels'] ?? [] as $panel => $enabled) {
                    if ($enabled) {
                        $panels[] = ucfirst($panel);
                    }
                }

                return [
                    'id' => $itemId,
                    'label' => is_callable($config['label']) ? 'Dynamic Label' : $config['label'],
                    'panels' => implode(', ', $panels),
                    'icon' => $config['icon'] ?? 'tabler-puzzle',
                ];
            })
            ->values()
            ->toArray();

        // Get render hooks
        $renderHooks = collect($registry->getRenderHooks())
            ->map(function ($callbacks, $hook) {
                return [
                    'hook' => $hook,
                    'count' => count($callbacks),
                ];
            })
            ->values()
            ->toArray();

        // Get server permissions
        $serverPermissions = collect($registry->getServerPermissions())
            ->map(function ($data, $extensionId) {
                return [
                    'extension' => $extensionId,
                    'category' => $data['name'] ?? 'Unknown',
                    'permissions' => $data['permissions'] ?? [],
                    'description' => $data['descriptions']['desc'] ?? 'No description',
                ];
            })
            ->values()
            ->toArray();

        // Get server page restrictions
        $serverPageRestrictions = collect($registry->getServerPageRestrictions())
            ->filter(fn ($data) => $data['extension_id'] === $extensionId)
            ->map(function ($data, $pageClass) {
                return [
                    'page_class' => $pageClass,
                    'page_name' => class_basename($pageClass),
                    'egg_tags' => $data['egg_tags'],
                ];
            })
            ->values()
            ->toArray();

        // Manually registered components
        return array_merge($discovered, [
            'navigationItems' => $navigationItems,
            'userMenuItems' => $userMenuItems,
            'renderHooks' => $renderHooks,
            'permissions' => collect($registry->getPermissions())->map(fn ($perms, $model) => ['model' => $model, 'permissions' => $perms])->values()->toArray(),
            'serverPermissions' => $serverPermissions,
            'serverPageRestrictions' => $serverPageRestrictions,
        ]);
    }

    /**
     * Get language pack information
     *
     * @return array<string, mixed>
     */
    protected function getLanguagePackInfo(string $extensionPath): array
    {
        $langPath = $extensionPath . '/lang';

        if (!File::isDirectory($langPath)) {
            return [];
        }

        $info = [
            'new_languages' => [],
            'overrides' => [],
            'custom_namespaces' => [],
        ];

        $directories = File::directories($langPath);

        foreach ($directories as $dir) {
            $langCode = basename($dir);

            // Check for overrides
            if ($langCode === 'overrides') {
                $overrideDirs = File::directories($dir);
                foreach ($overrideDirs as $overrideDir) {
                    $locale = basename($overrideDir);
                    $files = File::files($overrideDir);
                    $fileList = collect($files)->map(fn ($file) => $file->getFilename())->toArray();

                    $info['overrides'][] = [
                        'locale' => $locale,
                        'locale_name' => $this->getLocaleName($locale),
                        'files' => $fileList,
                        'count' => count($fileList),
                    ];
                }
            } else {
                // Check if this is a new language or custom namespace
                if (!File::isDirectory(base_path("lang/$langCode"))) {
                    // New language
                    $files = File::files($dir);
                    $info['new_languages'][] = [
                        'code' => $langCode,
                        'name' => $this->getLocaleName($langCode),
                        'files' => collect($files)->map(fn ($file) => $file->getFilename())->toArray(),
                        'file_count' => count($files),
                    ];
                } else {
                    // Custom namespace (extension-specific translations)
                    $files = File::files($dir);
                    $info['custom_namespaces'][] = [
                        'locale' => $langCode,
                        'locale_name' => $this->getLocaleName($langCode),
                        'files' => collect($files)->map(fn ($file) => $file->getFilename())->toArray(),
                        'namespace' => $this->record->identifier,
                    ];
                }
            }
        }

        // Get language overrides from database
        if ($this->record->language_overrides) {
            $info['active_overrides'] = $this->record->language_overrides;
        }

        return $info;
    }

    /**
     * Get theme information
     *
     * @return array<string, mixed>
     */
    protected function getThemeInfo(string $extensionPath): array
    {
        $publicPath = $extensionPath . '/public';

        if (!File::isDirectory($publicPath)) {
            return [];
        }

        $info = [
            'css_files' => [],
            'js_files' => [],
            'assets' => [],
        ];

        $files = File::allFiles($publicPath);

        foreach ($files as $file) {
            $extension = $file->getExtension();
            $relativePath = str_replace($publicPath . '/', '', $file->getPathname());

            if ($extension === 'css') {
                $info['css_files'][] = [
                    'path' => $relativePath,
                    'size' => $this->formatBytes($file->getSize()),
                    'url' => asset("extensions/{$this->record->identifier}/$relativePath"),
                ];
            } elseif ($extension === 'js') {
                $info['js_files'][] = [
                    'path' => $relativePath,
                    'size' => $this->formatBytes($file->getSize()),
                    'url' => asset("extensions/{$this->record->identifier}/$relativePath"),
                ];
            } else {
                $info['assets'][] = [
                    'path' => $relativePath,
                    'type' => $extension,
                    'size' => $this->formatBytes($file->getSize()),
                ];
            }
        }

        return $info;
    }

    /**
     * Get human-readable locale name
     */
    protected function getLocaleName(string $code): string
    {
        $locales = [
            'en' => 'English',
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'en-PIRATE' => 'Pirate English',
            'de-DE' => 'German',
            'fr-FR' => 'French',
            'es-ES' => 'Spanish',
            'it-IT' => 'Italian',
            'pt-BR' => 'Portuguese (Brazil)',
            'pt-PT' => 'Portuguese (Portugal)',
            'nl-NL' => 'Dutch',
            'pl-PL' => 'Polish',
            'ru-RU' => 'Russian',
            'ja-JP' => 'Japanese',
            'zh-CN' => 'Chinese (Simplified)',
            'zh-TW' => 'Chinese (Traditional)',
            'ko-KR' => 'Korean',
            'ar-SA' => 'Arabic',
        ];

        return $locales[$code] ?? $code;
    }

    /**
     * Format bytes to human-readable size
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
