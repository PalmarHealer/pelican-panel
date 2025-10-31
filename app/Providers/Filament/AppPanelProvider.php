<?php

namespace App\Providers\Filament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Panel;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Discover extensions
        /** @var \App\Extensions\ExtensionManager $extensionManager */
        $extensionManager = \Illuminate\Support\Facades\App::make(\App\Extensions\ExtensionManager::class);
        $extensionManager->discover();
        $extensionManager->registerAll();

        $panel = parent::panel($panel)
            ->id('app')
            ->default()
            ->breadcrumbs(false)
            ->navigation(false)
            ->topbar(true)
            ->userMenuItems(array_merge([
                'to_admin' => Action::make('to_admin')
                    ->label(trans('profile.admin'))
                    ->url(fn () => Filament::getPanel('admin')->getUrl())
                    ->icon('tabler-arrow-forward')
                    ->visible(fn () => user()?->canAccessPanel(Filament::getPanel('admin'))),
            ], $extensionManager->getUserMenuItemsForPanel('app')))
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->plugins([
                FilamentLogViewer::make()
                    ->authorize(false),
            ]);

        // Register extension components
        $extensionManager->registerPanelComponents($panel, 'app');

        return $panel;
    }
}
