<x-filament-panels::page>
    @php
        $server = \Filament\Facades\Filament::getTenant();
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Server Feature Example
            </x-slot>

            <x-slot name="description">
                This is an example server panel page registered by an extension with egg tag filtering support.
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p>
                    This page demonstrates how extensions can register custom pages in the server panel with:
                </p>

                <h3>Features:</h3>
                <ul>
                    <li>Tenant-aware (automatically knows which server you're managing)</li>
                    <li>Permission-based access control</li>
                    <li>Egg tag filtering (can show/hide based on server type)</li>
                    <li>Full access to server data and Filament components</li>
                </ul>

                <h3>Current Server Info:</h3>
                <ul>
                    <li><strong>Name:</strong> {{ $server->name }}</li>
                    <li><strong>UUID:</strong> <code>{{ $server->uuid_short }}</code></li>
                    <li><strong>Status:</strong> {{ ucfirst($server->status) }}</li>
                    <li><strong>Egg:</strong> {{ $server->egg->name }}</li>
                    <li><strong>Node:</strong> {{ $server->node->name }}</li>
                    @if($server->egg->tags)
                        <li><strong>Tags:</strong> {{ implode(', ', $server->egg->tags) }}</li>
                    @endif
                </ul>

                <h3>Your Access Level:</h3>
                <ul>
                    @if($server->isOwnedBy(user()))
                        <li class="text-success-600"><strong>Owner</strong> - Full access to this server</li>
                    @else
                        <li><strong>Subuser</strong> - Limited access based on permissions</li>
                        <li>Permissions:
                            <ul>
                                @foreach($server->subusers()->where('user_id', user()->id)->first()?->permissions ?? [] as $perm)
                                    <li><code>{{ $perm }}</code></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Example Actions
            </x-slot>

            <div class="flex gap-3">
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Server\Pages\Console::getUrl(['tenant' => $server]) }}"
                    icon="tabler-terminal"
                >
                    Open Console
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Server\Pages\Files::getUrl(['tenant' => $server]) }}"
                    icon="tabler-folder"
                    color="gray"
                >
                    File Manager
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
