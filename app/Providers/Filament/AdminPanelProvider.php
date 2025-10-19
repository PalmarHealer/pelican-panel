<?php

namespace App\Providers\Filament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use App\Extensions\ExtensionManager;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Discover extensions
        $extensionManager = app(ExtensionManager::class);
        $extensionManager->discover();
        $extensionManager->registerAll();

        $panel = parent::panel($panel)
            ->id('admin')
            ->path('admin')
            ->homeUrl('/')
            ->breadcrumbs(false)
            ->sidebarCollapsibleOnDesktop(fn () => !$panel->hasTopNavigation())
            ->userMenuItems(array_merge([
                'exit_admin' => Action::make('exit_admin')
                    ->label(fn () => trans('profile.exit_admin'))
                    ->url(fn () => Filament::getPanel('app')->getUrl())
                    ->icon('tabler-arrow-back'),
            ], $extensionManager->getUserMenuItemsForPanel('admin')))
            ->navigationItems($extensionManager->getNavigationItemsForPanel('admin'))
            ->navigationGroups([
                NavigationGroup::make(fn () => trans('admin/dashboard.server'))
                    ->collapsible(false),
                NavigationGroup::make(fn () => trans('admin/dashboard.user'))
                    ->collapsible(false),
                NavigationGroup::make(fn () => trans('admin/dashboard.advanced')),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->plugins([
                FilamentLogViewer::make()
                    ->authorize(fn () => user()->can('view panelLog'))
                    ->navigationGroup(fn () => trans('admin/dashboard.advanced'))
                    ->navigationIcon('tabler-file-info'),
            ]);

        // Register extension components
        $extensionManager->registerPanelComponents($panel, 'admin');

        return $panel;
    }
}
