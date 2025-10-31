<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $metadata = $data['metadata'];
        $registrations = $data['registrations'];
        $languageInfo = $data['languageInfo'] ?? [];
        $themeInfo = $data['themeInfo'] ?? [];
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

        {{-- Language Pack Information --}}
        @if(!empty($languageInfo))
        <x-filament::section collapsible>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="tabler-language" class="w-5 h-5"/>
                    Language Pack Details
                </div>
            </x-slot>
            <x-slot name="description">
                Languages, overrides, and custom translations provided by this extension
            </x-slot>

            <div class="space-y-6">
                {{-- New Languages --}}
                @if(!empty($languageInfo['new_languages']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-world-plus" class="w-5 h-5 text-success-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">New Languages Added</div>
                        <x-filament::badge color="success" size="sm">
                            {{ count($languageInfo['new_languages']) }}
                        </x-filament::badge>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($languageInfo['new_languages'] as $lang)
                            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $lang['name'] }}</div>
                                    <x-filament::badge color="gray" size="xs">{{ $lang['code'] }}</x-filament::badge>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $lang['file_count'] }} translation file{{ $lang['file_count'] !== 1 ? 's' : '' }}
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($lang['files'] as $file)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ $file }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Language Overrides --}}
                @if(!empty($languageInfo['overrides']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-file-text" class="w-5 h-5 text-warning-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Language Overrides</div>
                        <x-filament::badge color="warning" size="sm">
                            {{ count($languageInfo['overrides']) }}
                        </x-filament::badge>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($languageInfo['overrides'] as $override)
                            <div class="p-3 rounded-lg border border-warning-200 dark:border-warning-900/50 bg-warning-50 dark:bg-warning-900/20">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $override['locale_name'] }}</div>
                                    <x-filament::badge color="warning" size="xs">{{ $override['locale'] }}</x-filament::badge>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Overriding {{ $override['count'] }} file{{ $override['count'] !== 1 ? 's' : '' }}
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($override['files'] as $file)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-warning-200 dark:bg-warning-800 text-warning-900 dark:text-warning-100">
                                            {{ $file }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Custom Namespaces --}}
                @if(!empty($languageInfo['custom_namespaces']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-code" class="w-5 h-5 text-info-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Custom Translation Namespaces</div>
                        <x-filament::badge color="info" size="sm">
                            {{ count($languageInfo['custom_namespaces']) }}
                        </x-filament::badge>
                    </div>
                    <div class="space-y-3">
                        @foreach($languageInfo['custom_namespaces'] as $namespace)
                            <div class="p-3 rounded-lg border border-info-200 dark:border-info-900/50 bg-info-50 dark:bg-info-900/20">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $namespace['locale_name'] }}</div>
                                    <x-filament::badge color="info" size="xs">{{ $namespace['locale'] }}</x-filament::badge>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    Namespace: <code class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">{{ $namespace['namespace'] }}</code>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($namespace['files'] as $file)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-info-200 dark:bg-info-800 text-info-900 dark:text-info-100">
                                            {{ $file }}
                                        </span>
                                    @endforeach
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Usage: <code class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">trans('{{ $namespace['namespace'] }}::file.key')</code>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Active Overrides from Database --}}
                @if(!empty($languageInfo['active_overrides']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-database" class="w-5 h-5 text-primary-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Active Override Tracking</div>
                        <x-filament::badge color="primary" size="sm">
                            {{ count($languageInfo['active_overrides']) }}
                        </x-filament::badge>
                    </div>
                    <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            Files currently overridden by this extension:
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @foreach($languageInfo['active_overrides'] as $override)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-primary-100 dark:bg-primary-900/30 text-primary-900 dark:text-primary-100">
                                    {{ $override }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-filament::section>
        @endif

        {{-- Theme Information --}}
        @if(!empty($themeInfo))
        <x-filament::section collapsible>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="tabler-palette" class="w-5 h-5"/>
                    Theme Assets
                </div>
            </x-slot>
            <x-slot name="description">
                CSS, JavaScript, and other assets provided by this theme extension
            </x-slot>

            <div class="space-y-6">
                {{-- CSS Files --}}
                @if(!empty($themeInfo['css_files']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-file-type-css" class="w-5 h-5 text-blue-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">CSS Files</div>
                        <x-filament::badge color="info" size="sm">
                            {{ count($themeInfo['css_files']) }}
                        </x-filament::badge>
                    </div>
                    <div class="space-y-2">
                        @foreach($themeInfo['css_files'] as $css)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <div class="flex-1">
                                    <div class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $css['path'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Size: {{ $css['size'] }}
                                    </div>
                                </div>
                                <a href="{{ $css['url'] }}" target="_blank" class="ml-4 shrink-0">
                                    <x-filament::icon icon="tabler-external-link" class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"/>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- JavaScript Files --}}
                @if(!empty($themeInfo['js_files']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-file-type-js" class="w-5 h-5 text-yellow-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">JavaScript Files</div>
                        <x-filament::badge color="warning" size="sm">
                            {{ count($themeInfo['js_files']) }}
                        </x-filament::badge>
                    </div>
                    <div class="space-y-2">
                        @foreach($themeInfo['js_files'] as $js)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <div class="flex-1">
                                    <div class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $js['path'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Size: {{ $js['size'] }}
                                    </div>
                                </div>
                                <a href="{{ $js['url'] }}" target="_blank" class="ml-4 shrink-0">
                                    <x-filament::icon icon="tabler-external-link" class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"/>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Other Assets --}}
                @if(!empty($themeInfo['assets']))
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="tabler-file" class="w-5 h-5 text-gray-500"/>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Other Assets</div>
                        <x-filament::badge color="gray" size="sm">
                            {{ count($themeInfo['assets']) }}
                        </x-filament::badge>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach($themeInfo['assets'] as $asset)
                            <div class="flex items-center justify-between p-2 rounded border border-gray-200 dark:border-gray-700 text-xs">
                                <div class="flex-1 min-w-0">
                                    <div class="font-mono text-gray-900 dark:text-gray-100 truncate">{{ $asset['path'] }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">{{ $asset['size'] }}</div>
                                </div>
                                <x-filament::badge color="gray" size="xs" class="ml-2 shrink-0">
                                    {{ $asset['type'] }}
                                </x-filament::badge>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-filament::section>
        @endif

        {{-- Filament Components --}}
        <x-filament::section>
            <x-slot name="heading">
                Filament Components
            </x-slot>
            <x-slot name="description">
                Pages and Resources discovered via symlinks
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
                                        @if(!empty($item['egg_tags']))
                                            <div class="flex items-center gap-1 mt-1">
                                                <span class="text-xs text-gray-600 dark:text-gray-400">Egg tags:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($item['egg_tags'] as $tag)
                                                        <x-filament::badge color="warning" size="xs">{{ $tag }}</x-filament::badge>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
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

        {{-- Server Page Restrictions --}}
        @if($record->enabled && count($registrations['serverPageRestrictions'] ?? []) > 0)
        <x-filament::section collapsible>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="tabler-egg" class="w-5 h-5"/>
                    Server Page Restrictions
                </div>
            </x-slot>
            <x-slot name="description">
                Egg tag restrictions for server panel pages
            </x-slot>

            <div class="space-y-3">
                @foreach($registrations['serverPageRestrictions'] as $restriction)
                    <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $restriction['page_name'] }}</div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-mono">
                            {{ $restriction['page_class'] }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Allowed for egg tags:</span>
                            <div class="flex flex-wrap gap-1">
                                @foreach($restriction['egg_tags'] as $tag)
                                    <x-filament::badge color="primary" size="xs">{{ $tag }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
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
