<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Egg Tag Restriction Notice --}}
        <x-filament::section
            icon="tabler-egg"
            icon-color="warning"
        >
            <x-slot name="heading">
                Egg Tag Restriction Demo
            </x-slot>

            <x-slot name="description">
                This page demonstrates the egg tag filtering feature - it only appears for vanilla and Java servers.
            </x-slot>

            <div class="space-y-4">
                <div class="p-4 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-900/50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <x-filament::icon icon="tabler-info-circle" class="h-5 w-5 text-warning-600 dark:text-warning-400 mt-0.5 shrink-0" />
                        <div class="flex-1">
                            <h4 class="font-semibold text-warning-900 dark:text-warning-100 mb-2">How Egg Tag Restrictions Work</h4>
                            <p class="text-sm text-warning-800 dark:text-warning-200 mb-3">
                                This page can <strong>only</strong> be accessed from servers with the <code class="px-1.5 py-0.5 bg-warning-100 dark:bg-warning-900 rounded">vanilla</code> or <code class="px-1.5 py-0.5 bg-warning-100 dark:bg-warning-900 rounded">java</code> egg tags.
                                If you're viewing this page, it means your current server has at least one of these tags!
                            </p>

                            <div class="bg-white dark:bg-gray-900 p-3 rounded border border-warning-200 dark:border-warning-800">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2"><strong>Current Server:</strong></p>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ Filament\Facades\Filament::getTenant()?->name }}</span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        Egg: {{ Filament\Facades\Filament::getTenant()?->egg?->name ?? 'Unknown' }}
                                    </span>
                                </div>
                                @if(Filament\Facades\Filament::getTenant()?->egg?->tags)
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">Tags:</span>
                                        @foreach(Filament\Facades\Filament::getTenant()->egg->tags as $tag)
                                            <x-filament::badge
                                                color="{{ in_array($tag, ['vanilla', 'java']) ? 'success' : 'gray' }}"
                                                size="xs"
                                            >
                                                {{ $tag }}
                                            </x-filament::badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="prose dark:prose-invert max-w-none text-sm">
                    <h4>Implementation Details:</h4>
                    <ul>
                        <li><strong>extension.json:</strong> Defines egg restrictions with <code>"egg_restrictions": { "server/Pages/ExampleServerPage": ["vanilla", "java"] }</code></li>
                        <li><strong>RestrictedByEggTags Trait:</strong> Provides <code>checkEggRestrictions()</code> method to validate server compatibility</li>
                        <li><strong>canAccess() Method:</strong> Calls <code>static::checkEggRestrictions()</code> to automatically block access for incompatible servers</li>
                        <li><strong>shouldRegisterNavigation():</strong> Trait automatically overrides this to hide from navigation when egg doesn't match</li>
                        <li><strong>Permission Filtering:</strong> Extension permissions also support <code>'egg_tags'</code> to hide permissions for incompatible servers</li>
                    </ul>

                    <h4 class="mt-4">Benefits:</h4>
                    <ul>
                        <li>✅ Clean UI - only show relevant features for each server type</li>
                        <li>✅ Automatic enforcement - pages return 404/403 for incompatible servers</li>
                        <li>✅ Navigation hiding - pages don't appear in sidebar for incompatible servers</li>
                        <li>✅ Zero boilerplate - just add trait and call one method in canAccess()</li>
                        <li>✅ Flexible - supports multiple tags with OR logic (any matching tag allows access)</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Example Server Feature
            </x-slot>

            <x-slot name="description">
                This is an example server panel page with custom permission management.
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p>
                    This page demonstrates how extensions can register custom server panel pages with dynamic permission checks.
                </p>

                <h3>Features:</h3>
                <ul>
                    <li><strong>Egg-based filtering:</strong> This page only appears for vanilla/Java servers</li>
                    <li><strong>Permission-based access:</strong> Requires 'example_feature.read' permission</li>
                    <li><strong>Dynamic permission display:</strong> Shows what user can/cannot do</li>
                    <li><strong>Full Filament integration:</strong> Access to all Filament components and server context</li>
                </ul>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Your Permissions
            </x-slot>

            <x-slot name="description">
                These are the extension permissions you have for this server.
            </x-slot>

            <div class="space-y-4">
                @forelse($userPermissions as $category)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            @if(isset($category['icon']))
                                <x-filament::icon
                                    :icon="$category['icon']"
                                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                                />
                            @endif
                            <h4 class="text-lg font-semibold">{{ ucfirst(str_replace('_', ' ', $category['category'])) }}</h4>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $category['description'] }}</p>

                        <div class="space-y-2">
                            @foreach($category['permissions'] as $action => $permission)
                                <div class="flex items-center gap-2">
                                    @if($permission['granted'])
                                        <x-filament::icon
                                            icon="tabler-circle-check"
                                            class="h-5 w-5 text-success-500"
                                        />
                                    @else
                                        <x-filament::icon
                                            icon="tabler-circle-x"
                                            class="h-5 w-5 text-danger-500"
                                        />
                                    @endif

                                    <div class="flex-1">
                                        <code class="text-sm">{{ $permission['key'] }}</code>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $permission['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-filament::icon
                            icon="tabler-lock"
                            class="h-12 w-12 mx-auto mb-2 opacity-50"
                        />
                        <p>No extension permissions found.</p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Extension Info
            </x-slot>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <strong>Extension ID:</strong> example-extension
                </div>
                <div>
                    <strong>Panel:</strong> Server
                </div>
                <div>
                    <strong>Version:</strong> 1.0.0
                </div>
                <div>
                    <strong>Status:</strong> <span class="text-success-600">Active</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
