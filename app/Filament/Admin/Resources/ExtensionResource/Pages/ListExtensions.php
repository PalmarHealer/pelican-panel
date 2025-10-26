<?php

namespace App\Filament\Admin\Resources\ExtensionResource\Pages;

use App\Extensions\ExtensionManager;
use App\Filament\Admin\Resources\ExtensionResource;
use App\Models\Extension;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class ListExtensions extends ListRecords
{
    protected static ?string $title = 'Extension System';

    protected ?string $subheading = 'Manage and configure extensions to add custom functionality to your panel';

    protected static string $resource = ExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import Extension')
                ->icon('tabler-upload')
                ->color('success')
                ->form([
                    FileUpload::make('extension_zip')
                        ->label('Extension ZIP File')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->required()
                        ->directory('temp-uploads')
                        ->visibility('private')
                        ->maxSize(51200), // 50MB max
                    Toggle::make('auto_enable')
                        ->label('Enable after import')
                        ->default(false)
                        ->helperText('Automatically enable the extension after importing'),
                ])
                ->action(function (array $data) {
                    /** @var ExtensionManager $manager */
                    $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);

                    // Get the uploaded file path
                    $zipPath = storage_path('app/private/' . $data['extension_zip']);

                    try {
                        $result = $manager->importExtension($zipPath, $data['auto_enable'] ?? false);

                        // Delete the uploaded zip file
                        if (File::exists($zipPath)) {
                            File::delete($zipPath);
                        }

                        if ($result['success']) {
                            Notification::make()
                                ->title($result['isUpdate'] ? 'Extension Updated' : 'Extension Imported')
                                ->body($result['message'])
                                ->success()
                                ->send();

                            // Trigger a rescan to pick up the new extension
                            $this->scanForNewExtensions();
                        } else {
                            Notification::make()
                                ->title('Import Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        // Clean up the zip file on error
                        if (File::exists($zipPath)) {
                            File::delete($zipPath);
                        }

                        Notification::make()
                            ->title('Import Error')
                            ->body('Failed to import extension: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('scan')
                ->label('Scan for Extensions')
                ->icon('tabler-refresh')
                ->color('info')
                ->action(function () {
                    $this->scanForNewExtensions();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->recordUrl(fn (Extension $record): string => ExtensionResource::getUrl('view', ['record' => $record], panel: 'admin'))
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->description(function (Extension $record) {
                        $desc = $record->description ?? 'No description';

                        return mb_strimwidth($desc, 0, 100, '...');
                    }),
                TextColumn::make('version')
                    ->label('Version')
                    ->sortable()
                    ->badge(),
                TextColumn::make('types')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => collect($state ?? ['plugin'])
                        ->map(fn ($type) => \App\Enums\ExtensionType::tryFrom($type)?->label() ?? ucfirst($type))
                        ->join(', '))
                    ->color(fn ($record) => $record->getTypeObjects()[0]?->color() ?? 'gray'),
                TextColumn::make('author')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unknown')
                    ->visibleFrom('md'),
                IconColumn::make('enabled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('tabler-circle-check')
                    ->falseIcon('tabler-circle-x')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->visibleFrom('lg')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn (Extension $record) => $record->enabled ? 'Disable' : 'Enable')
                    ->icon(fn (Extension $record) => $record->enabled ? 'tabler-square-x' : 'tabler-square-check')
                    ->color(fn (Extension $record) => $record->enabled ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Extension $record) => ($record->enabled ? 'Disable' : 'Enable') . ' Extension')
                    ->modalDescription(fn (Extension $record) => 'Are you sure you want to ' . ($record->enabled ? 'disable' : 'enable') . ' this extension?')
                    ->action(function (Extension $record) {
                        /** @var ExtensionManager $manager */
                        $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);

                        try {
                            if ($record->enabled) {
                                $manager->disable($record->identifier);
                                Notification::make()
                                    ->title('Extension disabled')
                                    ->body("Extension '{$record->name}' has been disabled.")
                                    ->success()
                                    ->send();
                            } else {
                                $manager->enable($record->identifier);
                                Notification::make()
                                    ->title('Extension enabled')
                                    ->body("Extension '{$record->name}' has been enabled.")
                                    ->success()
                                    ->send();
                            }
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
                    ->action(function (Extension $record) {
                        try {
                            $extensionPath = base_path("extensions/{$record->identifier}");

                            if (!File::isDirectory($extensionPath)) {
                                Notification::make()
                                    ->title('Extension not found')
                                    ->body('The extension directory does not exist.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Create a temporary zip file
                            $zipFileName = "{$record->identifier}-" . now()->format('Y-m-d-His') . '.zip';
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
                    ->action(function (Extension $record) {
                        /** @var ExtensionManager $manager */
                        $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);

                        try {
                            $manager->uninstall($record->identifier);
                            Notification::make()
                                ->title('Extension uninstalled')
                                ->body("Extension '{$record->name}' has been uninstalled.")
                                ->success()
                                ->send();
                            redirect(request()->header('Referer'));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateIcon('tabler-puzzle')
            ->emptyStateDescription('No extensions found in the extensions directory.')
            ->emptyStateHeading('No Extensions')
            ->toolbarActions([
                BulkAction::make('enable')
                    ->label('Enable Selected')
                    ->icon('tabler-square-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        /** @var ExtensionManager $manager */
                        $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);
                        $count = 0;

                        /** @var Extension $record */
                        foreach ($records as $record) {
                            if (!$record->enabled) {
                                try {
                                    $manager->enable($record->identifier);
                                    $count++;
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error enabling ' . $record->name)
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }
                        }

                        if ($count > 0) {
                            Notification::make()
                                ->title("Enabled {$count} extension(s)")
                                ->success()
                                ->send();
                            redirect(request()->header('Referer'));
                        }
                    }),
                BulkAction::make('disable')
                    ->label('Disable Selected')
                    ->icon('tabler-square-x')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        /** @var ExtensionManager $manager */
                        $manager = \Illuminate\Support\Facades\App::make(ExtensionManager::class);
                        $count = 0;

                        /** @var Extension $record */
                        foreach ($records as $record) {
                            if ($record->enabled) {
                                try {
                                    $manager->disable($record->identifier);
                                    $count++;
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error disabling ' . $record->name)
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }
                        }

                        if ($count > 0) {
                            Notification::make()
                                ->title("Disabled {$count} extension(s)")
                                ->success()
                                ->send();
                            redirect(request()->header('Referer'));
                        }
                    }),
            ])
            ->emptyStateIcon('tabler-puzzle')
            ->emptyStateDescription('No extensions found in the extensions directory.')
            ->emptyStateHeading('No Extensions')
            ->emptyStateActions([
                Action::make('scan')
                    ->label('Scan for Extensions')
                    ->icon('tabler-refresh')
                    ->action(function () {
                        $this->scanForNewExtensions();
                    }),
            ]);
    }

    protected function scanForNewExtensions(): void
    {
        $extensionPath = base_path('extensions');

        if (!File::isDirectory($extensionPath)) {
            Notification::make()
                ->title('Extensions directory not found')
                ->body('The extensions directory does not exist.')
                ->warning()
                ->send();

            return;
        }

        $directories = File::directories($extensionPath);
        $found = 0;

        foreach ($directories as $dir) {
            $metadataFile = $dir . '/extension.json';

            if (!File::exists($metadataFile)) {
                continue;
            }

            $metadata = json_decode(File::get($metadataFile), true);

            if (!$metadata || !isset($metadata['id'])) {
                continue;
            }

            $existing = Extension::where('identifier', $metadata['id'])->first();

            if (!$existing) {
                Extension::create([
                    'identifier' => $metadata['id'],
                    'name' => $metadata['name'] ?? $metadata['id'],
                    'description' => $metadata['description'] ?? null,
                    'version' => $metadata['version'] ?? '1.0.0',
                    'author' => $metadata['author'] ?? null,
                    'types' => $metadata['types'] ?? ['plugin'],
                    'enabled' => false,
                ]);
                $found++;
            } else {
                // Update metadata if it's missing or changed
                $existing->update([
                    'author' => $metadata['author'] ?? $existing->author,
                    'description' => $metadata['description'] ?? $existing->description,
                    'types' => $metadata['types'] ?? $existing->types ?? ['plugin'],
                ]);
            }
        }

        if ($found > 0) {
            Notification::make()
                ->title('Extensions scanned')
                ->body("Found {$found} new extension(s).")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No new extensions')
                ->body('No new extensions were found.')
                ->info()
                ->send();
        }
    }
}
