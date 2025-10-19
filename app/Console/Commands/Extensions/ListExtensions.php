<?php

namespace App\Console\Commands\Extensions;

use App\Models\Extension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListExtensions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extensions:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all discovered extensions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $extensionPath = base_path('extensions');

        if (!File::isDirectory($extensionPath)) {
            $this->error('Extensions directory not found.');
            return self::FAILURE;
        }

        $directories = File::directories($extensionPath);

        if (empty($directories)) {
            $this->info('No extensions found.');
            return self::SUCCESS;
        }

        $extensions = [];

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
            $dbExtension = Extension::where('identifier', $extensionId)->first();

            $extensions[] = [
                'ID' => $extensionId,
                'Name' => $metadata['name'] ?? $extensionId,
                'Version' => $metadata['version'] ?? 'N/A',
                'Status' => $dbExtension && $dbExtension->enabled ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
                'Installed' => $dbExtension ? '<fg=green>Yes</>' : '<fg=yellow>No</>',
            ];
        }

        if (empty($extensions)) {
            $this->info('No valid extensions found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Version', 'Status', 'Installed'],
            $extensions
        );

        return self::SUCCESS;
    }
}
