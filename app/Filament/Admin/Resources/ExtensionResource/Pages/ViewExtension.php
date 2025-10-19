<?php

namespace App\Filament\Admin\Resources\ExtensionResource\Pages;

use App\Extensions\ExtensionManager;
use App\Filament\Admin\Resources\ExtensionResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\File;

class ViewExtension extends Page
{
    protected static string $resource = ExtensionResource::class;

    public $record;

    public function getView(): string
    {
        return 'filament.admin.resources.extension-resource.pages.view-extension';
    }

    public function mount(int | string $record): void
    {
        $this->record = ExtensionResource::resolveRecordRouteBinding($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle')
                ->label(fn() => $this->record->enabled ? 'Disable' : 'Enable')
                ->icon(fn() => $this->record->enabled ? 'tabler-square-x' : 'tabler-square-check')
                ->color(fn() => $this->record->enabled ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn() => $this->record->enabled ? 'Disable Extension' : 'Enable Extension')
                ->modalDescription(fn() => $this->record->enabled
                    ? 'Are you sure you want to disable this extension?'
                    : 'Are you sure you want to enable this extension?')
                ->action(function () {
                    $manager = app(ExtensionManager::class);

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
                ->visible(fn() => !$this->record->enabled)
                ->action(function () {
                    $manager = app(ExtensionManager::class);

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

        $manager = app(ExtensionManager::class);
        $registry = $manager->getRegistry();

        // Get registrations from registry
        $registrations = $this->getExtensionRegistrations($registry);

        return compact('metadata', 'registrations', 'extensionPath');
    }

    protected function getExtensionRegistrations($registry): array
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

        // Manually registered components
        return array_merge($discovered, [
            'navigationItems' => $navigationItems,
            'userMenuItems' => $userMenuItems,
            'renderHooks' => $renderHooks,
            'permissions' => collect($registry->getPermissions())->map(fn($perms, $model) => ['model' => $model, 'permissions' => $perms])->values()->toArray(),
            'serverPermissions' => $serverPermissions,
        ]);
    }
}
