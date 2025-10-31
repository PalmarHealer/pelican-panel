<?php

namespace App\Providers;

use App\Checks\CacheCheck;
use App\Checks\DatabaseCheck;
use App\Checks\DebugModeCheck;
use App\Checks\EnvironmentCheck;
use App\Checks\NodeVersionsCheck;
use App\Checks\PanelVersionCheck;
use App\Checks\ScheduleCheck;
use App\Checks\UsedDiskSpaceCheck;
use App\Models\Allocation;
use App\Models\ApiKey;
use App\Models\Backup;
use App\Models\Database;
use App\Models\Egg;
use App\Models\EggVariable;
use App\Models\Node;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Server;
use App\Models\Task;
use App\Models\User;
use App\Models\UserSSHKey;
use App\Services\Helpers\SoftwareVersionService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Filament\Support\Facades\FilamentView;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(
        Application $app,
        SoftwareVersionService $versionService,
        Repository $config,
    ): void {
        // If the APP_URL value is set with https:// make sure we force it here. Theoretically
        // this should just work with the proxy logic, but there are a lot of cases where it
        // doesn't, and it triggers a lot of support requests, so lets just head it off here.
        URL::forceHttps(Str::startsWith(config('app.url') ?? '', 'https://'));

        if ($app->runningInConsole() && empty(config('app.key'))) {
            $config->set('app.key', '');
        }

        Relation::enforceMorphMap([
            'allocation' => Allocation::class,
            'api_key' => ApiKey::class,
            'backup' => Backup::class,
            'database' => Database::class,
            'egg' => Egg::class,
            'egg_variable' => EggVariable::class,
            'schedule' => Schedule::class,
            'server' => Server::class,
            'ssh_key' => UserSSHKey::class,
            'task' => Task::class,
            'user' => User::class,
            'node' => Node::class,
        ]);

        Http::macro(
            'daemon',
            fn (Node $node, array $headers = []) => Http::acceptJson()
                ->asJson()
                ->withToken($node->daemon_token)
                ->withHeaders($headers)
                ->withOptions(['verify' => (bool) $app->environment('production')])
                ->timeout(config('panel.guzzle.timeout'))
                ->connectTimeout(config('panel.guzzle.connect_timeout'))
                ->baseUrl($node->getConnectionAddress())
        );

        Sanctum::usePersonalAccessTokenModel(ApiKey::class);

        Gate::define('viewApiDocs', fn () => true);

        $bearerTokens = fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer'));
        Scramble::registerApi('application', ['api_path' => 'api/application', 'info' => ['version' => '1.0']])->afterOpenApiGenerated($bearerTokens);
        Scramble::registerApi('client', ['api_path' => 'api/client', 'info' => ['version' => '1.0']])->afterOpenApiGenerated($bearerTokens);

        // Don't run any health checks during tests
        if (!$app->runningUnitTests()) {
            Health::checks([
                DebugModeCheck::new()->if($app->isProduction()),
                EnvironmentCheck::new(),
                CacheCheck::new(),
                DatabaseCheck::new(),
                ScheduleCheck::new(),
                UsedDiskSpaceCheck::new(),
                PanelVersionCheck::new(),
                NodeVersionsCheck::new(),
            ]);
        }

        Gate::before(function (User $user, $ability) {
            return $user->isRootAdmin() ? true : null;
        });

        AboutCommand::add('Pelican', [
            'Panel Version' => $versionService->currentPanelVersion(),
            'Latest Version' => $versionService->latestPanelVersion(),
            'Up-to-Date' => $versionService->isLatestPanel() ? '<fg=green;options=bold>Yes</>' : '<fg=red;options=bold>No</>',
        ]);

        AboutCommand::add('Drivers', 'Backups', config('backups.default'));

        AboutCommand::add('Environment', 'Installation Directory', base_path());

        // === EXTENSION SYSTEM (SINGLE INTEGRATION POINT) ===
        if (!$app->runningInConsole() || $app->runningUnitTests()) {
            /** @var \App\Extensions\ExtensionManager $extensionManager */
            $extensionManager = $this->app->make(\App\Extensions\ExtensionManager::class);
            $extensionManager->discover();
            $extensionManager->registerAll();
            $extensionManager->bootAll();

            // Apply registrations
            $this->applyExtensionRegistrations($extensionManager);
        }
    }

    /**
     * Apply extension registrations to the application.
     */
    protected function applyExtensionRegistrations(\App\Extensions\ExtensionManager $manager): void
    {
        $registry = $manager->getRegistry();

        // Apply admin/role permissions
        foreach ($registry->getPermissions() as $model => $permissions) {
            Role::registerCustomPermissions([$model => $permissions]);
        }

        // Apply server panel (subuser) permissions
        foreach ($registry->getServerPermissions() as $extensionId => $permissionData) {
            \App\Models\Permission::registerExtensionPermissions($extensionId, $permissionData);
        }

        // Apply render hooks
        foreach ($registry->getRenderHooks() as $hook => $callbacks) {
            foreach ($callbacks as $callback) {
                FilamentView::registerRenderHook($hook, $callback['callback']);
            }
        }
    }

    /**
     * Register application service providers.
     */
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();

        // Register ExtensionManager as singleton so all instances share the same data
        $this->app->singleton(\App\Extensions\ExtensionManager::class);

        // Register extension autoloaders early (before panel providers run)
        // This allows Filament's discoverPages() to find extension classes
        /** @var \App\Extensions\ExtensionManager $extensionManager */
        $extensionManager = $this->app->make(\App\Extensions\ExtensionManager::class);
        $extensionManager->registerAutoloaders();
    }
}
