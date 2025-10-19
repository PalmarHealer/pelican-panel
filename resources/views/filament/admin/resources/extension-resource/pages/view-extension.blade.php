<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $metadata = $data['metadata'];
        $registrations = $data['registrations'];
    @endphp

    <div class="space-y-6">
        {{-- General Information --}}
        <x-filament::section>
            <x-slot name="heading">
                General Information
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Extension ID</div>
                    <div class="mt-1 font-mono">{{ $record->identifier }}</div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</div>
                    <div class="mt-1">{{ $record->version }}</div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</div>
                    <div class="mt-1">{{ $record->name }}</div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</div>
                    <div class="mt-1">
                        <x-filament::badge :color="$record->enabled ? 'success' : 'danger'">
                            {{ $record->enabled ? 'Enabled' : 'Disabled' }}
                        </x-filament::badge>
                    </div>
                </div>

                @if(isset($metadata['description']))
                <div class="col-span-full">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</div>
                    <div class="mt-1">{{ $metadata['description'] }}</div>
                </div>
                @endif

                @if(isset($metadata['author']))
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Author</div>
                    <div class="mt-1">{{ $metadata['author'] }}</div>
                </div>
                @endif

                @if(isset($metadata['author_email']))
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Author Email</div>
                    <div class="mt-1">{{ $metadata['author_email'] }}</div>
                </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Filament Components --}}
        <x-filament::section>
            <x-slot name="heading">
                Filament Components
            </x-slot>
            <x-slot name="description">
                Pages, Resources, and Widgets discovered via symlinks
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach([
                    'Admin Pages' => ['data' => $registrations['adminPages'] ?? [], 'icon' => 'tabler-file', 'color' => 'info'],
                    'Admin Resources' => ['data' => $registrations['adminResources'] ?? [], 'icon' => 'tabler-database', 'color' => 'success'],
                    'Admin Widgets' => ['data' => $registrations['adminWidgets'] ?? [], 'icon' => 'tabler-layout-dashboard', 'color' => 'warning'],
                    'App Pages' => ['data' => $registrations['appPages'] ?? [], 'icon' => 'tabler-file', 'color' => 'info'],
                    'App Resources' => ['data' => $registrations['appResources'] ?? [], 'icon' => 'tabler-database', 'color' => 'success'],
                    'App Widgets' => ['data' => $registrations['appWidgets'] ?? [], 'icon' => 'tabler-layout-dashboard', 'color' => 'warning'],
                    'Server Pages' => ['data' => $registrations['serverPages'] ?? [], 'icon' => 'tabler-file', 'color' => 'info'],
                    'Server Resources' => ['data' => $registrations['serverResources'] ?? [], 'icon' => 'tabler-database', 'color' => 'success'],
                    'Server Widgets' => ['data' => $registrations['serverWidgets'] ?? [], 'icon' => 'tabler-layout-dashboard', 'color' => 'warning'],
                ] as $label => $config)
                    @if(count($config['data']) > 0)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <x-filament::icon
                                :icon="$config['icon']"
                                class="w-5 h-5 text-{{ $config['color'] }}-500"
                            />
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</div>
                            <x-filament::badge :color="$config['color']" size="sm">
                                {{ count($config['data']) }}
                            </x-filament::badge>
                        </div>
                        <ul class="space-y-1 text-xs">
                            @foreach($config['data'] as $item)
                                <li class="flex items-center gap-1 text-gray-600 dark:text-gray-400 font-mono">
                                    <span class="text-gray-400">•</span>
                                    {{ is_array($item) ? $item['name'] : class_basename($item) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                @endforeach
            </div>

            @if(
                empty($registrations['adminPages']) && empty($registrations['adminResources']) && empty($registrations['adminWidgets']) &&
                empty($registrations['appPages']) && empty($registrations['appResources']) && empty($registrations['appWidgets']) &&
                empty($registrations['serverPages']) && empty($registrations['serverResources']) && empty($registrations['serverWidgets'])
            )
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                No Filament components found
            </div>
            @endif
        </x-filament::section>

        {{-- Navigation & Menu Items --}}
        @if($record->enabled)
        <x-filament::section>
            <x-slot name="heading">
                Navigation & Menu Items
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Navigation Items --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-sitemap" class="w-5 h-5 text-primary-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Navigation Items</div>
                        <x-filament::badge color="primary" size="sm">
                            {{ count($registrations['navigationItems']) }}
                        </x-filament::badge>
                    </div>
                    @if(count($registrations['navigationItems']) > 0)
                        <div class="space-y-2">
                            @foreach($registrations['navigationItems'] as $item)
                                <div class="flex items-start gap-2 p-2 rounded border border-gray-200 dark:border-gray-700">
                                    <x-filament::icon :icon="$item['icon']" class="w-4 h-4 mt-0.5 text-gray-400"/>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['label'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item['id'] }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">Panels: {{ $item['panels'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">No navigation items registered</div>
                    @endif
                </div>

                {{-- User Menu Items --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-menu-2" class="w-5 h-5 text-success-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">User Menu Items</div>
                        <x-filament::badge color="success" size="sm">
                            {{ count($registrations['userMenuItems']) }}
                        </x-filament::badge>
                    </div>
                    @if(count($registrations['userMenuItems']) > 0)
                        <div class="space-y-2">
                            @foreach($registrations['userMenuItems'] as $item)
                                <div class="flex items-start gap-2 p-2 rounded border border-gray-200 dark:border-gray-700">
                                    <x-filament::icon :icon="$item['icon']" class="w-4 h-4 mt-0.5 text-gray-400"/>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['label'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item['id'] }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">Panels: {{ $item['panels'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">No user menu items registered</div>
                    @endif
                </div>
            </div>
        </x-filament::section>
        @endif

        {{-- Render Hooks --}}
        @if($record->enabled && count($registrations['renderHooks']) > 0)
        <x-filament::section collapsible>
            <x-slot name="heading">
                Render Hooks
            </x-slot>
            <x-slot name="description">
                Custom render hooks registered by this extension
            </x-slot>

            <div class="space-y-2">
                @foreach($registrations['renderHooks'] as $hook)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="tabler-hook" class="w-4 h-4 text-warning-500"/>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $hook['hook'] }}</span>
                        </div>
                        <x-filament::badge color="warning" size="sm">
                            {{ $hook['count'] }} callback{{ $hook['count'] > 1 ? 's' : '' }}
                        </x-filament::badge>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        @endif

        {{-- Permissions --}}
        @if($record->enabled)
        <x-filament::section collapsible>
            <x-slot name="heading">
                Permissions
            </x-slot>
            <x-slot name="description">
                Custom permissions registered by this extension
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Admin/Role Permissions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-shield" class="w-5 h-5 text-danger-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Admin/Role Permissions</div>
                        <x-filament::badge color="danger" size="sm">
                            {{ count($registrations['permissions']) }}
                        </x-filament::badge>
                    </div>
                    @if(count($registrations['permissions']) > 0)
                        <div class="space-y-2">
                            @foreach($registrations['permissions'] as $perm)
                                <div class="p-2 rounded border border-gray-200 dark:border-gray-700">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $perm['model'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ implode(', ', $perm['permissions']) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">No admin permissions registered</div>
                    @endif
                </div>

                {{-- Server (Subuser) Permissions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-user-shield" class="w-5 h-5 text-info-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Server Panel Permissions</div>
                        <x-filament::badge color="info" size="sm">
                            {{ count($registrations['serverPermissions']) }}
                        </x-filament::badge>
                    </div>
                    @if(count($registrations['serverPermissions']) > 0)
                        <div class="space-y-2">
                            @foreach($registrations['serverPermissions'] as $perm)
                                <div class="p-3 rounded border border-gray-200 dark:border-gray-700">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $perm['category'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $perm['description'] }}</div>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @foreach($perm['permissions'] as $p)
                                            <x-filament::badge color="gray" size="xs">{{ $p }}</x-filament::badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">No server permissions registered</div>
                    @endif
                </div>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
