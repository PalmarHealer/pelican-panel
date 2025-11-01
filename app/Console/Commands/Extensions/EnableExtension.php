<?php

namespace App\Console\Commands\Extensions;

use App\Extensions\ExtensionManager;
use Illuminate\Console\Command;

class EnableExtension extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extensions:enable {extension : The extension ID to enable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable an extension';

    /**
     * Execute the console command.
     */
    public function handle(ExtensionManager $manager): int
    {
        $extensionId = $this->argument('extension');

        $this->info("Enabling extension: {$extensionId}");

        try {
            $manager->enable($extensionId);
            $this->info("Extension '{$extensionId}' has been enabled successfully.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to enable extension: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
