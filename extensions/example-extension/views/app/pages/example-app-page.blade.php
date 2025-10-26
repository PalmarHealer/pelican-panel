<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Admin Page Example
            </x-slot>

            <x-slot name="description">
                This is an example admin panel page registered by an extension with permission protection.
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p>
                    This page demonstrates how extensions can register custom pages in the admin panel with permission management.
                </p>

                <h3>Features:</h3>
                <ul>
                    <li>Permission-based access control (requires 'viewList exampleExtension' permission)</li>
                    <li>Custom navigation group ('Advanced')</li>
                    <li>Custom icon and sort order</li>
                    <li>Full access to Filament components</li>
                </ul>

                <h3>Current User Permissions:</h3>
                <ul>
                    @foreach(user()->getAllPermissions() as $permission)
                        <li><code>{{ $permission->name }}</code></li>
                    @endforeach
                </ul>
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
                    <strong>Panel:</strong> Admin
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
