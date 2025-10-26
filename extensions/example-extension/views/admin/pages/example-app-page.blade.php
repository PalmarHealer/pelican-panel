<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                App Page Example
            </x-slot>

            <x-slot name="description">
                This is an example app panel page registered by an extension.
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p>
                    This page demonstrates how extensions can register custom pages in the app panel.
                </p>

                <h3>Features:</h3>
                <ul>
                    <li>Accessible to all authenticated users (no special permissions required)</li>
                    <li>Can be accessed directly via URL: <code>/app/example-app-page</code></li>
                    <li>Full access to Filament components</li>
                    <li>Can interact with user's servers and data</li>
                </ul>

                <h3>User Info:</h3>
                <ul>
                    <li><strong>Username:</strong> {{ user()->username }}</li>
                    <li><strong>Email:</strong> {{ user()->email }}</li>
                    <li><strong>Server Count:</strong> {{ user()->servers()->count() }}</li>
                </ul>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Quick Actions
            </x-slot>

            <div class="flex gap-3">
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\App\Resources\Servers\Pages\ListServers::getUrl() }}"
                    icon="tabler-brand-docker"
                >
                    View Servers
                </x-filament::button>

                @if(user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                    <x-filament::button
                        tag="a"
                        href="{{ \Filament\Facades\Filament::getPanel('admin')->getUrl() }}"
                        icon="tabler-arrow-forward"
                        color="gray"
                    >
                        Go to Admin
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
