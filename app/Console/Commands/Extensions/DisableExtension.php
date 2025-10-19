<?php

namespace App\Console\Commands\Extensions;

use App\Extensions\ExtensionManager;
use Illuminate\Console\Command;

class DisableExtension extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extensions:disable {extension : The extension ID to disable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable an extension';

    /**
     * Execute the console command.
     */
    public function handle(ExtensionManager $manager): int
    {
        $extensionId = $this->argument('extension');

        $this->info("Disabling extension: {$extensionId}");

        try {
            $manager->disable($extensionId);
            $this->info("Extension '{$extensionId}' has been disabled successfully.");
            $this->newLine();
            $this->warn('Note: You may need to clear cache or restart services for changes to take effect.');
            $this->comment('Run: php artisan cache:clear && php artisan config:clear');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to disable extension: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
