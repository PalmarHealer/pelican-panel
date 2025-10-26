# Extension API Reference

Complete API reference for the Pelican Panel Extension System.

## Table of Contents

1. [ExtensionRegistry](#extensionregistry)
2. [ExtensionInterface](#extensioninterface)
3. [Permissions](#permissions)
4. [Navigation](#navigation)
5. [Render Hooks](#render-hooks)
6. [Server Interaction](#server-interaction)
7. [Events](#events)
8. [Helper Functions](#helper-functions)

## ExtensionRegistry

The `ExtensionRegistry` class is the main interface for registering extension components.

### Methods

#### `permissions(array $permissions): void`

Register admin/role-based permissions.

**Parameters:**
- `$permissions` (array): Associative array of model => permissions

**Example:**
```php
$registry->permissions([
    'yourModel' => ['viewList', 'view', 'create', 'update', 'delete'],
    'anotherModel' => ['viewList', 'view'],
]);
```

**Permission Prefixes:**
- `viewList` - View list of resources
- `view` - View single resource
- `create` - Create new resource
- `update` - Update existing resource
- `delete` - Delete resource

---

#### `serverPermissions(string $extensionId, array $permissionData): void`

Register server-level subuser permissions.

**Parameters:**
- `$extensionId` (string): Your extension identifier
- `$permissionData` (array): Permission configuration

**Permission Data Structure:**
```php
[
    'name' => 'category_name',           // Permission category name
    'icon' => 'tabler-icon-name',        // Tabler icon
    'permissions' => ['read', 'write'],  // Available actions
    'descriptions' => [
        'desc' => 'Category description',
        'read' => 'Read action description',
        'write' => 'Write action description',
    ],
    'egg_tags' => ['minecraft', 'java'], // Optional: restrict to server types
]
```

**Example:**
```php
$registry->serverPermissions('my-extension', [
    'name' => 'custom_feature',
    'icon' => 'tabler-code',
    'permissions' => ['read', 'write', 'execute'],
    'descriptions' => [
        'desc' => 'Controls access to custom feature',
        'read' => 'View custom feature data',
        'write' => 'Modify custom feature settings',
        'execute' => 'Execute custom feature actions',
    ],
    'egg_tags' => ['vanilla', 'java'],
]);
```

**Checking Permissions:**
```php
use Filament\Facades\Filament;

if (user()?->can('custom_feature.read', Filament::getTenant())) {
    // User has permission
}
```

---

#### `navigationItem(string $id, string|\Closure $label, array $config): void`

Register a navigation item in sidebar.

**Parameters:**
- `$id` (string): Unique identifier for the nav item
- `$label` (string|Closure): Display label (or closure returning label)
- `$config` (array): Configuration options

**Configuration Options:**
```php
[
    'url' => '/admin/your-page',         // URL (required)
    'icon' => 'tabler-icon-name',        // Tabler icon (required)
    'group' => 'Group Name',             // Navigation group (optional)
    'sort' => 100,                       // Sort order (optional)
    'visible' => fn() => true,           // Visibility condition (optional)
    'panels' => [                        // Which panels to show in
        'admin' => true,
        'server' => false,
        'app' => false,
    ],
]
```

**Example:**
```php
$registry->navigationItem(
    'my-extension-link',
    'My Extension',
    [
        'url' => '/admin/my-extension',
        'icon' => 'tabler-sparkles',
        'group' => 'Extensions',
        'sort' => 100,
        'visible' => fn() => user()?->can('viewList myExtension'),
        'panels' => [
            'admin' => true,
            'server' => false,
        ],
    ]
);
```

**Dynamic Label:**
```php
$registry->navigationItem(
    'server-status',
    fn() => 'Status: ' . ($server->isRunning() ? 'Online' : 'Offline'),
    [
        'url' => '/server/status',
        'icon' => 'tabler-circle',
        'panels' => ['server' => true],
    ]
);
```

---

#### `userMenuItem(string $id, string|\Closure $label, array $config): void`

Register an item in the user menu dropdown.

**Parameters:**
- `$id` (string): Unique identifier
- `$label` (string|Closure): Display label
- `$config` (array): Configuration options

**Configuration Options:**
```php
[
    'url' => '/admin/settings',          // URL (required)
    'icon' => 'tabler-icon-name',        // Tabler icon (optional)
    'visible' => fn() => true,           // Visibility condition (optional)
    'panels' => [                        // Which panels to show in
        'admin' => true,
        'server' => false,
        'app' => false,
    ],
]
```

**Example:**
```php
$registry->userMenuItem(
    'extension-settings',
    'Extension Settings',
    [
        'url' => '/admin/extensions',
        'icon' => 'tabler-settings',
        'visible' => fn() => user()?->isAdmin(),
        'panels' => [
            'admin' => true,
            'server' => false,
            'app' => false,
        ],
    ]
);
```

---

#### `renderHook(string $hook, \Closure $callback): void`

Register a render hook to inject content at various UI points.

**Parameters:**
- `$hook` (string): Hook name (use `PanelsRenderHook` constants)
- `$callback` (Closure): Function returning HTML string or view

**Available Hooks:**
```php
use Filament\View\PanelsRenderHook;

PanelsRenderHook::PAGE_START         // Top of page content
PanelsRenderHook::PAGE_END           // Bottom of page content
PanelsRenderHook::CONTENT_START      // Start of main content
PanelsRenderHook::CONTENT_END        // End of main content
PanelsRenderHook::HEADER_START       // Start of header
PanelsRenderHook::HEADER_END         // End of header
PanelsRenderHook::FOOTER             // Footer area
PanelsRenderHook::STYLES_BEFORE      // Before CSS styles
PanelsRenderHook::STYLES_AFTER       // After CSS styles
PanelsRenderHook::SCRIPTS_BEFORE     // Before JS scripts
PanelsRenderHook::SCRIPTS_AFTER      // After JS scripts
PanelsRenderHook::HEAD_END           // End of HTML head
```

**Examples:**
```php
// Simple HTML
$registry->renderHook(
    PanelsRenderHook::FOOTER,
    fn () => '<div>Footer message</div>'
);

// Blade view
$registry->renderHook(
    PanelsRenderHook::FOOTER,
    fn () => view('extensions.my-extension.footer')
);

// Inject CSS
$registry->renderHook(
    PanelsRenderHook::STYLES_AFTER,
    fn () => '<style>.custom-class { color: red; }</style>'
);

// Inject JavaScript
$registry->renderHook(
    PanelsRenderHook::SCRIPTS_AFTER,
    fn () => '<script>console.log("Extension loaded");</script>'
);

// Include external asset
$registry->renderHook(
    PanelsRenderHook::HEAD_END,
    fn () => '<link rel="stylesheet" href="' . asset('extensions/my-extension/style.css') . '">'
);
```

---

#### `profileTab(string $id, \Closure $schema, array $options): void`

Add a custom tab to the user profile page.

**Parameters:**
- `$id` (string): Unique tab identifier
- `$schema` (Closure): Function returning Filament form schema
- `$options` (array): Tab configuration

**Options:**
```php
[
    'label' => 'Tab Label',              // Display label (required)
    'icon' => 'tabler-icon-name',        // Tabler icon (optional)
]
```

**Example:**
```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

$registry->profileTab(
    'my-extension-prefs',
    fn() => [
        TextInput::make('my_extension_setting')
            ->label('Setting')
            ->required(),
        Toggle::make('my_extension_enabled')
            ->label('Enable Feature')
            ->default(true),
    ],
    [
        'label' => 'My Extension',
        'icon' => 'tabler-sparkles',
    ]
);
```

---

#### `consoleWidget(string $class, string $position, array $options): void`

Register a widget to display in the server console.

**Parameters:**
- `$class` (string): Widget class name
- `$position` (string): Widget position (use `ConsoleWidgetPosition` constants)
- `$options` (array): Configuration options

**Positions:**
```php
use App\Enums\ConsoleWidgetPosition;

ConsoleWidgetPosition::Top          // Above console
ConsoleWidgetPosition::Bottom       // Below console
```

**Options:**
```php
[
    'egg_tags' => ['minecraft', 'java'], // Optional: restrict to server types
    'eggs' => [1, 2, 3],                 // Optional: restrict to specific egg IDs
]
```

**Example:**
```php
$registry->consoleWidget(
    \Extensions\MyExtension\Widgets\ServerStatsWidget::class,
    ConsoleWidgetPosition::Top,
    [
        'egg_tags' => ['minecraft'],
    ]
);
```

---

## ExtensionInterface

All extensions must implement the `ExtensionInterface`.

```php
<?php

namespace Extensions\YourExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    /**
     * Register extension components.
     * Called during extension loading.
     *
     * @param ExtensionRegistry $registry
     * @return void
     */
    public function register(ExtensionRegistry $registry): void
    {
        // Register permissions, pages, hooks, etc.
    }

    /**
     * Boot the extension.
     * Called after all extensions are registered.
     *
     * @return void
     */
    public function boot(): void
    {
        // Event listeners, middleware registration, etc.
    }

    /**
     * Cleanup when extension is disabled.
     *
     * @return void
     */
    public function disable(): void
    {
        // Cleanup logic
    }
}
```

### Lifecycle Methods

#### `register(ExtensionRegistry $registry): void`

Called when the extension is loaded. Use this to:
- Register permissions
- Register navigation items
- Register render hooks
- Register profile tabs
- Register console widgets

**Example:**
```php
public function register(ExtensionRegistry $registry): void
{
    $registry->permissions([
        'yourModel' => ['viewList', 'view', 'create', 'update', 'delete'],
    ]);

    $registry->renderHook(
        PanelsRenderHook::FOOTER,
        fn () => view('extensions.your-extension.footer')
    );
}
```

#### `boot(): void`

Called after all extensions are registered. Use this to:
- Register event listeners
- Register middleware
- Perform initialization tasks

**Example:**
```php
use Illuminate\Support\Facades\Event;
use App\Events\Server\Created;

public function boot(): void
{
    Event::listen(Created::class, function ($event) {
        // Handle server creation
        $server = $event->server;
        Log::info("Server created: {$server->name}");
    });
}
```

#### `disable(): void`

Called when the extension is disabled. Use this to:
- Clear caches
- Cleanup temporary data
- Log disable action

**Example:**
```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

public function disable(): void
{
    // Clear extension caches
    Cache::forget('your-extension-*');

    // Log disable
    Log::info('Your Extension has been disabled');
}
```

---

## Permissions

### Admin Permissions

Admin permissions control access to admin panel features.

**Checking Permissions:**
```php
// In Filament pages/resources
public static function canAccess(): bool
{
    return user()?->can('viewList yourModel') ?? false;
}

// In controllers/services
if (auth()->user()->can('create yourModel')) {
    // User can create
}

// Check multiple permissions
if (auth()->user()->canAny(['viewList yourModel', 'view yourModel'])) {
    // User has at least one permission
}

// Check all permissions
if (auth()->user()->canAll(['create yourModel', 'update yourModel'])) {
    // User has all permissions
}
```

**Available Prefixes:**
- `viewList {model}` - View list of resources
- `view {model}` - View single resource details
- `create {model}` - Create new resource
- `update {model}` - Update existing resource
- `delete {model}` - Delete resource

### Server Permissions

Server permissions control subuser access within specific servers.

**Checking Permissions:**
```php
use Filament\Facades\Filament;

// In server panel pages
$server = Filament::getTenant();

if (user()?->can('your_category.action', $server)) {
    // User has permission for this server
}
```

**Displaying Permissions in Pages:**

Use the `HasExtensionPermissions` trait:

```php
use App\Filament\Server\Pages\Concerns\HasExtensionPermissions;
use Filament\Pages\Page;

class YourPage extends Page
{
    use HasExtensionPermissions;

    public array $userPermissions = [];

    public function mount(): void
    {
        // Get permissions with granted status
        $this->userPermissions = $this->getExtensionPermissions('your-extension-id');
    }
}
```

**Trait Methods:**
- `getExtensionPermissions(string $extensionId): array` - Get all permissions with granted status
- `hasExtensionPermission(string $extensionId, string $permission): bool` - Check specific permission
- `getExtensionPermissionsFlat(string $extensionId): array` - Get flat array of permissions

---

## Navigation

### Adding Navigation Items

Navigation items appear in the sidebar.

**Basic Example:**
```php
$registry->navigationItem(
    'my-link',
    'My Page',
    [
        'url' => '/admin/my-page',
        'icon' => 'tabler-star',
        'group' => 'My Group',
        'sort' => 100,
    ]
);
```

**Panel-Specific:**
```php
$registry->navigationItem(
    'server-feature',
    'Server Feature',
    [
        'url' => '/server/feature',
        'icon' => 'tabler-code',
        'panels' => [
            'admin' => false,
            'server' => true,  // Only show in server panel
            'app' => false,
        ],
    ]
);
```

**Conditional Visibility:**
```php
$registry->navigationItem(
    'admin-only',
    'Admin Feature',
    [
        'url' => '/admin/feature',
        'icon' => 'tabler-lock',
        'visible' => fn() => user()?->isAdmin() ?? false,
        'panels' => ['admin' => true],
    ]
);
```

**Permission-Based:**
```php
use Filament\Facades\Filament;

$registry->navigationItem(
    'protected-feature',
    'Protected Feature',
    [
        'url' => '/server/protected',
        'icon' => 'tabler-shield',
        'visible' => fn() => user()?->can('your_feature.read', Filament::getTenant()) ?? false,
        'panels' => ['server' => true],
    ]
);
```

### Adding User Menu Items

User menu items appear in the dropdown menu (top right).

**Example:**
```php
$registry->userMenuItem(
    'my-settings',
    'My Settings',
    [
        'url' => '/admin/my-settings',
        'icon' => 'tabler-settings',
        'visible' => fn() => true,
        'panels' => ['admin' => true],
    ]
);
```

---

## Render Hooks

### Injecting Content

**Simple HTML:**
```php
$registry->renderHook(
    PanelsRenderHook::PAGE_START,
    fn () => '<div class="alert alert-info">Important notice</div>'
);
```

**Blade View:**
```php
$registry->renderHook(
    PanelsRenderHook::FOOTER,
    fn () => view('extensions.my-extension.footer', [
        'data' => 'value',
    ])
);
```

**Conditional Content:**
```php
$registry->renderHook(
    PanelsRenderHook::PAGE_START,
    function () {
        if (user()?->isAdmin()) {
            return view('extensions.my-extension.admin-notice');
        }
        return '';
    }
);
```

### Panel-Specific Hooks

Check current panel before rendering:

```php
use Filament\Facades\Filament;

$registry->renderHook(
    PanelsRenderHook::FOOTER,
    function () {
        $panel = Filament::getCurrentPanel()->getId();

        if ($panel === 'admin') {
            return view('extensions.my-extension.admin-footer');
        }

        if ($panel === 'server') {
            return view('extensions.my-extension.server-footer');
        }

        return '';
    }
);
```

---

## Server Interaction

### Getting Current Server

In server panel pages:

```php
use Filament\Facades\Filament;
use App\Models\Server;

$server = Filament::getTenant(); // Returns Server model instance
```

### Communicating with Wings

Use the `Http::daemon()` macro to interact with the Wings daemon:

**Get Server Status:**
```php
use Illuminate\Support\Facades\Http;

$server = Filament::getTenant();

$response = Http::daemon($server)
    ->get("/api/servers/{$server->uuid}")
    ->json();

$status = $response['state'] ?? 'unknown';
```

**Execute Command:**
```php
Http::daemon($server)
    ->post("/api/servers/{$server->uuid}/commands", [
        'commands' => ['say Hello from extension!']
    ]);
```

**Get Server Files:**
```php
$files = Http::daemon($server)
    ->get("/api/servers/{$server->uuid}/files/list", [
        'directory' => '/',
    ])
    ->json();
```

**Upload File:**
```php
Http::daemon($server)
    ->attach('files', file_get_contents($localPath), 'filename.txt')
    ->post("/api/servers/{$server->uuid}/files/upload", [
        'directory' => '/config',
    ]);
```

**Error Handling:**
```php
try {
    $response = Http::daemon($server)
        ->get("/api/servers/{$server->uuid}")
        ->throw()  // Throw exception on HTTP errors
        ->json();
} catch (\Illuminate\Http\Client\RequestException $e) {
    Log::error('Wings request failed: ' . $e->getMessage());
    Notification::make()
        ->title('Error')
        ->danger()
        ->body('Failed to communicate with server daemon')
        ->send();
}
```

### Server Properties

```php
$server = Filament::getTenant();

// Basic info
$server->id                          // Internal ID
$server->uuid                        // Full UUID
$server->uuid_short                  // Short UUID (used in URLs)
$server->name                        // Server name
$server->description                 // Description

// Relationships
$server->user                        // Owner (User model)
$server->node                        // Node (Node model)
$server->egg                         // Egg (Egg model)
$server->allocation                  // Primary allocation

// Configuration
$server->memory                      // Memory limit (MB)
$server->disk                        // Disk limit (MB)
$server->cpu                         // CPU limit (%)
$server->swap                        // Swap limit (MB)
$server->io                          // IO weight

// Status
$server->status                      // Current status
$server->isInstalled()              // Installation complete?
$server->isSuspended()              // Is suspended?

// Collections
$server->databases                   // Databases collection
$server->backups                     // Backups collection
$server->subusers                    // Subusers collection
$server->schedules                   // Schedules collection
```

### Egg-Based Filtering

Restrict features to specific server types:

**Using Trait:**
```php
use App\Filament\Server\Pages\Concerns\RestrictedByEggTags;

class YourPage extends Page
{
    use RestrictedByEggTags;

    protected static array $eggTags = ['minecraft', 'java'];

    // Page automatically restricted to servers with these tags
}
```

**Manual Check:**
```php
public static function canAccess(): bool
{
    $server = Filament::getTenant();

    // Check egg tags
    $hasTag = $server->egg->tags()
        ->whereIn('name', ['minecraft', 'java'])
        ->exists();

    return $hasTag && (user()?->can('control.console', $server) ?? false);
}
```

---

## Events

### Available Events

**Server Events:**
```php
use App\Events\Server\Created;
use App\Events\Server\Deleted;
use App\Events\Server\Installed;
use App\Events\Server\Suspended;
use App\Events\Server\Unsuspended;
```

**User Events:**
```php
use App\Events\User\Created;
use App\Events\User\Deleted;
```

**Activity Events:**
```php
use App\Events\ActivityLogged;
```

### Listening to Events

**In boot() method:**
```php
use Illuminate\Support\Facades\Event;
use App\Events\Server\Created;

public function boot(): void
{
    Event::listen(Created::class, function ($event) {
        $server = $event->server;
        Log::info("New server created: {$server->name}");

        // Initialize extension data for this server
        // Send notifications, etc.
    });
}
```

**Multiple Events:**
```php
use App\Events\Server\Created;
use App\Events\Server\Deleted;

public function boot(): void
{
    Event::listen([Created::class, Deleted::class], function ($event) {
        $server = $event->server;

        if ($event instanceof Created) {
            // Handle creation
        } elseif ($event instanceof Deleted) {
            // Handle deletion
        }
    });
}
```

**Event Subscriber:**
```php
use Illuminate\Events\Dispatcher;

public function boot(): void
{
    Event::subscribe(YourEventSubscriber::class);
}
```

```php
class YourEventSubscriber
{
    public function handleServerCreated($event): void
    {
        // Handle server created
    }

    public function handleServerDeleted($event): void
    {
        // Handle server deleted
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            \App\Events\Server\Created::class,
            [YourEventSubscriber::class, 'handleServerCreated']
        );

        $events->listen(
            \App\Events\Server\Deleted::class,
            [YourEventSubscriber::class, 'handleServerDeleted']
        );
    }
}
```

---

## Helper Functions

### Global Helpers

**Get Current User:**
```php
$user = user(); // Returns current authenticated user or null
```

**Convert Bytes:**
```php
echo convert_bytes_to_readable(1024); // "1 KB"
echo convert_bytes_to_readable(1048576); // "1 MB"
```

**Asset Helper:**
```php
// Extension assets
$url = asset('extensions/my-extension/style.css');

// Versioned assets (cache busting)
$url = asset('extensions/my-extension/style.css?v=1.0.0');
```

**Config Helper:**
```php
// Get extension config
$value = config('my-extension.setting');

// Set config at runtime
config(['my-extension.setting' => 'value']);
```

**Cache Helpers:**
```php
use Illuminate\Support\Facades\Cache;

// Store data
Cache::put('my-extension:key', 'value', now()->addHours(1));

// Retrieve data
$value = Cache::get('my-extension:key');

// Remember (get or store)
$value = Cache::remember('my-extension:key', now()->addHours(1), function () {
    return 'computed value';
});

// Forget data
Cache::forget('my-extension:key');
```

**Logging:**
```php
use Illuminate\Support\Facades\Log;

Log::info('Extension message');
Log::error('Extension error', ['context' => $data]);
Log::debug('Debug info', ['server' => $server->id]);
```

**Notifications:**
```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Success')
    ->success()
    ->body('Operation completed successfully')
    ->send();

Notification::make()
    ->title('Error')
    ->danger()
    ->body('Something went wrong')
    ->send();

Notification::make()
    ->title('Warning')
    ->warning()
    ->body('Please review this')
    ->persistent() // Don't auto-dismiss
    ->send();
```

---

## Best Practices

### Error Handling

Always wrap external calls in try-catch:

```php
try {
    $response = Http::daemon($server)->get('/api/endpoint')->json();
} catch (\Exception $e) {
    Log::error('Extension error: ' . $e->getMessage());
    Notification::make()
        ->title('Error')
        ->danger()
        ->send();
    return;
}
```

### Permission Checks

Always verify permissions:

```php
// Before displaying content
if (!user()?->can('viewList yourModel')) {
    abort(403);
}

// In page access
public static function canAccess(): bool
{
    return user()?->can('viewList yourModel') ?? false;
}
```

### Cache Usage

Use namespaced cache keys:

```php
// Good
Cache::put('my-extension:server:' . $server->id . ':data', $data);

// Bad
Cache::put('data', $data); // Could conflict with other extensions
```

### Database Queries

Use Eloquent relationships and eager loading:

```php
// Good
$servers = Server::with(['user', 'node', 'egg'])->get();

// Bad (N+1 query problem)
$servers = Server::all();
foreach ($servers as $server) {
    echo $server->user->name; // Triggers query for each server
}
```

---

## Next Steps

- Review [Extension Development Guide](README.md) for comprehensive tutorials
- Check [Creating Themes](creating-themes.md) for theme development
- Explore [Creating Language Packs](creating-language-packs.md) for translations
- Examine example extensions in `extensions/example-extension/`
