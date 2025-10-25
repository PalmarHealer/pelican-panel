<?php

namespace Extensions\ExampleExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;
use Extensions\ExampleExtension\Filament\Pages\ExampleAdminPage;
use Extensions\ExampleExtension\Filament\Pages\ExampleAppPage;
use Extensions\ExampleExtension\Filament\Pages\ExampleServerPage;
use Filament\Facades\Filament;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        $registry->permissions([
            'exampleExtension' => ['viewList', 'view', 'create', 'update', 'delete'],
        ]);

        // Register server permissions with egg tag restrictions
        // These permissions will only appear for servers with vanilla or java egg tags
        $registry->serverPermissions('example-extension', [
            'name' => 'example_feature',
            'icon' => 'tabler-accessible',
            'permissions' => ['read', 'write', 'execute'],
            'descriptions' => [
                'desc' => 'Permissions that control access to example extension features. Only available for vanilla/java servers.',
                'read' => 'Allows viewing example extension data and features.',
                'write' => 'Allows modifying example extension settings and data.',
                'execute' => 'Allows executing example extension actions and commands.',
            ],
            'egg_tags' => ['vanilla', 'java'], // Only show for vanilla/java servers
        ]);

        $registry->renderHook(
            PanelsRenderHook::FOOTER,
            fn () => view('extensions.example-extension.footer-message')
        );

        $registry->renderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn () => '<style>.example-extension-active { border-left: 3px solid #10b981; }</style>'
        );

        $registry->renderHook(
            PanelsRenderHook::PAGE_START,
            fn () => view('extensions.example-extension.page-notice')
        );

        $registry->navigationItem(
            'example-extension-link',
            'Example Extension',
            [
                'url' => '/admin/extensions',
                'icon' => 'tabler-sparkles',
                'group' => 'Extensions',
                'sort' => 101,
                'panels' => [
                    'admin' => true,
                    'server' => false,
                ],
            ]
        );

        $registry->navigationItem(
            'example-server-feature',
            fn () => 'Example Custom Nav',
            [
                'url' => '/',
                'icon' => 'tabler-sparkles',
                'sort' => 50,
                'visible' => fn () => user()?->can('example_feature.read', Filament::getTenant()) ?? false,
                'panels' => [
                    'admin' => false,
                    'server' => true,
                ],
            ]
        );

        $registry->userMenuItem(
            'example-extension-settings',
            'Extension Settings',
            [
                'url' => '/admin/extensions',
                'icon' => 'tabler-settings',
                'visible' => fn () => user()?->isAdmin() ?? false,
                'panels' => [
                    'admin' => true,
                    'server' => false,
                    'app' => false,
                ],
            ]
        );
    }

    public function boot(): void
    {
        Log::info('Example Extension has been booted');

        Event::listen(
            \App\Events\Server\Created::class,
            function ($event) {
                Log::info('Example Extension: Server created - ' . $event->server->name);
            }
        );
    }

    public function disable(): void
    {
        Log::info('Example Extension has been disabled');
    }
}
