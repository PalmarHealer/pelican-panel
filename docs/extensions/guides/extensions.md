# Extension Development Guide

This comprehensive guide will help you create powerful functional extensions (plugins) for Pelican Panel. These extensions can add custom pages, resources, widgets, permissions, and integrate with the panel's core functionality.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Extension Structure](#extension-structure)
3. [Auto-Discovery System](#auto-discovery-system)
4. [Extension Controller](#extension-controller)
5. [Permissions System](#permissions-system)
6. [Working with Servers](#working-with-servers)
7. [Egg-Based Filtering](#egg-based-filtering)
8. [Advanced Features](#advanced-features)
9. [Best Practices](#best-practices)
10. [Complete Examples](#complete-examples)

## Getting Started

### Prerequisites

- Basic PHP knowledge
- Familiarity with Laravel framework
- Understanding of Filament PHP (helpful but not required)
- Access to Pelican Panel installation

### Your First Extension

Let's create a simple extension that adds a custom page to the admin panel.

**1. Create the extension directory:**

```bash
cd /var/www/pelican
mkdir -p extensions/my-first-extension/admin/Pages
cd extensions/my-first-extension
```

**2. Create `extension.json`:**

```json
{
    "id": "my-first-extension",
    "name": "My First Extension",
    "description": "A simple example extension",
    "version": "1.0.0",
    "author": "Your Name",
    "author_email": "your.email@example.com",
    "types": ["plugin"],
    "controller": "ExtensionController"
}
```

**3. Create `ExtensionController.php`:**

```php
<?php

namespace Extensions\MyFirstExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;
use Filament\View\PanelsRenderHook;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Register a simple footer message
        $registry->renderHook(
            PanelsRenderHook::FOOTER,
            fn () => '<div style="text-align: center; padding: 1rem;">Powered by My First Extension</div>'
        );
    }

    public function boot(): void
    {
        // Boot logic here
    }

    public function disable(): void
    {
        // Cleanup logic here
    }
}
```

**4. Enable the extension:**

```bash
composer dump-autoload
```

Navigate to `/admin/extensions`, click "Scan for Extensions", find your extension, and click "Enable". Refresh the page to see your footer message!

## Extension Structure

### Directory Layout

Full directory structure (all optional except `extension.json` and `ExtensionController.php`):

```
extensions/my-extension/
├── extension.json              # Extension metadata (REQUIRED)
├── ExtensionController.php     # Main controller (REQUIRED)
├── README.md                   # Documentation (recommended)
├── migrations/                 # Database migrations
│   └── 2024_01_01_000000_create_example_table.php
├── admin/                      # Admin panel components
│   ├── Pages/                  # Auto-discovered admin pages
│   ├── Resources/              # Auto-discovered admin resources
│   └── Widgets/                # Auto-discovered admin widgets
├── app/                        # App panel components
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── server/                     # Server panel components
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── views/                      # Blade templates
│   ├── admin/pages/
│   ├── app/pages/
│   ├── server/pages/
│   └── components/
├── public/                     # Public assets
│   ├── css/
│   ├── js/
│   └── images/
├── config/                     # Configuration files
│   └── my-extension.php
├── Services/                   # Business logic classes
│   └── MyService.php
├── Models/                     # Eloquent models
│   └── MyModel.php
└── lang/                       # Translation files
    ├── en/
    └── de/
```

### Extension Lifecycle

#### 1. Discovery Phase

When Pelican boots or when you click "Scan for Extensions":

1. Scans `/extensions/` directory
2. Reads `extension.json` from each folder
3. Checks if extension exists in database
4. If new: creates database record (disabled by default)

#### 2. Enable Phase

When an extension is enabled:

1. Runs migrations from `/migrations/` directory
2. Creates symlinks for auto-discovery:
   - `admin/Pages/` → `app/Filament/Admin/Pages/Extensions/my-extension/`
   - `admin/Resources/` → `app/Filament/Admin/Resources/Extensions/my-extension/`
   - `admin/Widgets/` → `app/Filament/Admin/Widgets/Extensions/my-extension/`
   - (same for `app/` and `server/` directories)
3. Publishes assets: `/public/` → `public/extensions/my-extension/`
4. Creates view symlink: `/views/` → `resources/views/extensions/my-extension/`
5. Loads config files from `/config/`
6. Loads `ExtensionController`
7. Calls `register()` method
8. Calls `boot()` method
9. Marks as enabled in database

#### 3. Runtime Phase

On every request (when extension is enabled):

1. ExtensionManager discovers and registers all enabled extensions
2. Panel providers retrieve navigation and user menu items
3. Filament's auto-discovery finds pages/resources/widgets via symlinks
4. Custom registrations (hooks, permissions) are applied
5. Extension components are rendered

#### 4. Disable Phase

When an extension is disabled:

1. Calls `disable()` method on controller
2. Removes all symlinks
3. Unpublishes assets and views
4. Marks as disabled in database

## Auto-Discovery System

Pelican uses a **symlink-based auto-discovery system** for Filament components.

### What Gets Auto-Discovered

- ✅ Admin Pages (in `admin/Pages/`)
- ✅ Admin Resources (in `admin/Resources/`)
- ✅ Admin Widgets (in `admin/Widgets/`)
- ✅ App Pages (in `app/Pages/`)
- ✅ App Resources (in `app/Resources/`)
- ✅ App Widgets (in `app/Widgets/`)
- ✅ Server Pages (in `server/Pages/`)
- ✅ Server Resources (in `server/Resources/`)
- ✅ Server Widgets (in `server/Widgets/`)

### Namespace Requirements

Your classes must use the correct PSR-4 namespace:

**For Admin Panel:**
```php
// File: admin/Pages/ExamplePage.php
namespace App\Filament\Admin\Pages\Extensions\MyExtension;

use Filament\Pages\Page;

class ExamplePage extends Page
{
    protected static ?string $slug = 'extensions/example-page';
    protected static ?string $navigationIcon = 'tabler-sparkles';
    protected string $view = 'extensions.my-extension.admin.pages.example-page';
    // ...
}
```

**For Server Panel:**
```php
// File: server/Pages/ExamplePage.php
namespace App\Filament\Server\Pages\Extensions\MyExtension;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class ExamplePage extends Page
{
    protected static ?string $slug = 'extensions/example-page';
    protected static ?string $navigationIcon = 'tabler-code';
    protected string $view = 'extensions.my-extension.server.pages.example-page';

    public static function canAccess(): bool
    {
        $server = Filament::getTenant();
        return user()?->can('your_feature.read', $server) ?? false;
    }
}
```

### Directory Name → Namespace Mapping

The extension manager automatically converts kebab-case directory names to StudlyCase:

- Directory: `my-awesome-extension`
- Namespace: `MyAwesomeExtension`

### What Does NOT Get Auto-Discovered

These must be manually registered in `register()`:

- ❌ Navigation items
- ❌ User menu items
- ❌ Render hooks
- ❌ Permissions

## Extension Controller

The `ExtensionController.php` is the heart of your extension.

### Interface Requirements

```php
namespace App\Extensions\Contracts;

interface ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void;
    public function boot(): void;
    public function disable(): void;
}
```

### Method Purposes

| Method | When Called | Purpose |
|--------|-------------|---------|
| `register()` | During extension loading | Register components that can't be auto-discovered |
| `boot()` | After all extensions registered | Set up event listeners, middleware, etc. |
| `disable()` | When extension is disabled | Cleanup logic (optional) |

### Available Registry Methods

- `permissions()` - Register admin/role permissions
- `serverPermissions()` - Register server-level subuser permissions
- `serverPageRestriction()` - Restrict pages to specific egg tags
- `navigationItem()` - Add navigation sidebar items
- `userMenuItem()` - Add user dropdown menu items
- `renderHook()` - Inject HTML/CSS/JS at various points

### Example Controller

```php
<?php

namespace Extensions\MyExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Register admin permissions
        $registry->permissions([
            'myFeature' => ['viewList', 'view', 'create', 'update', 'delete'],
        ]);

        // Register server permissions
        $registry->serverPermissions('my-extension', [
            'name' => 'my_feature',
            'icon' => 'tabler-star',
            'permissions' => ['read', 'write'],
            'descriptions' => [
                'desc' => 'Access to my extension features',
                'read' => 'View my extension data',
                'write' => 'Modify my extension settings',
            ],
        ]);

        // Register navigation item
        $registry->navigationItem(
            'my-extension-link',
            'My Extension',
            [
                'url' => '/admin/my-extension',
                'icon' => 'tabler-star',
                'group' => 'Extensions',
                'panels' => ['admin' => true],
            ]
        );

        // Inject custom CSS
        $registry->renderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn () => '<style>.my-class { color: blue; }</style>'
        );
    }

    public function boot(): void
    {
        // Listen to server created event
        Event::listen(
            \App\Events\Server\Created::class,
            function ($event) {
                Log::info('Server created: ' . $event->server->name);
            }
        );
    }

    public function disable(): void
    {
        Log::info('My Extension has been disabled');
    }
}
```

## Permissions System

Pelican has a **dual permission system**: Admin/Role permissions and Server Panel (subuser) permissions.

### 1. Admin/Role Permissions

For panel-wide access control.

**Registration:**

```php
$registry->permissions([
    'yourModel' => ['viewList', 'view', 'create', 'update', 'delete'],
]);
```

**Available Permission Prefixes:**

- `viewList` - Can view list of resources
- `view` - Can view individual resource
- `create` - Can create new resource
- `update` - Can edit resource
- `delete` - Can delete resource

**Checking Permissions:**

```php
// In your code
if (user()->can('viewList yourModel')) {
    // User has permission
}

// In Filament pages
public static function canAccess(): bool
{
    return user()?->can('viewList yourModel') ?? false;
}

// In Filament components
->visible(fn() => user()->can('view yourModel'))
```

**How They Work:**

1. Permissions are created in the database
2. Assigned to roles via Role management UI
3. Users inherit permissions from their roles
4. Root admin bypasses all checks

### 2. Server Panel (Subuser) Permissions

For server-specific access control.

**Registration:**

```php
$registry->serverPermissions('my-extension', [
    'name' => 'my_feature',              // Category name
    'icon' => 'tabler-code',             // Tabler icon
    'permissions' => ['read', 'write', 'execute'],
    'descriptions' => [
        'desc' => 'Overall description of this permission category',
        'read' => 'Description of the read permission',
        'write' => 'Description of the write permission',
        'execute' => 'Description of the execute permission',
    ],
    'egg_tags' => ['vanilla', 'java'],  // Optional: only for specific server types
]);
```

**Permission Key Format:** `{category}.{permission}` (e.g., `my_feature.read`)

**Checking Permissions:**

```php
use Filament\Facades\Filament;

// In server panel pages
$server = Filament::getTenant();  // Current server
$user = user();

if ($user->can('my_feature.read', $server)) {
    // User has permission on this specific server
}

// In page access control
public static function canAccess(): bool
{
    return user()?->can('my_feature.read', Filament::getTenant()) ?? false;
}

// In visibility checks
->visible(fn() => user()?->can('my_feature.write', Filament::getTenant()) ?? false)
```

**How They Work:**

1. Extension registers permission category
2. Server owner grants/revokes permissions to subusers via UI
3. Permissions are server-specific
4. Server owner always has all permissions

### Using the HasExtensionPermissions Trait

For server panel pages, use this trait to easily access extension permissions:

```php
use App\Filament\Server\Pages\Concerns\HasExtensionPermissions;
use Filament\Pages\Page;

class YourPage extends Page
{
    use HasExtensionPermissions;

    public array $userPermissions = [];

    public function mount(): void
    {
        // Get all permissions with granted status
        $this->userPermissions = $this->getExtensionPermissions('my-extension');
    }
}
```

**Trait Methods:**
- `getExtensionPermissions(string $extensionId): array` - Get all permissions with granted status
- `hasExtensionPermission(string $extensionId, string $permission): bool` - Check specific permission

## Working with Servers

### Getting Current Server

In server panel pages, get the current server using Filament::getTenant():

```php
use Filament\Facades\Filament;
use App\Models\Server;

$server = Filament::getTenant(); // Returns Server model instance
```

### Communicating with Wings

Use the `Http::daemon()` macro to interact with the server daemon:

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
$server->id                // Internal ID
$server->uuid              // Full UUID
$server->uuid_short        // Short UUID
$server->name              // Server name
$server->description       // Description

// Relationships
$server->user              // Owner (User model)
$server->node              // Node (Node model)
$server->egg               // Egg (Egg model)
$server->allocation        // Primary allocation

// Configuration
$server->memory            // Memory limit (MB)
$server->disk              // Disk limit (MB)
$server->cpu               // CPU limit (%)

// Status
$server->status            // Current status
$server->isInstalled()     // Installation complete?
$server->isSuspended()     // Is suspended?

// Collections
$server->databases         // Databases collection
$server->backups           // Backups collection
$server->subusers          // Subusers collection
```

### Server Events

Listen to server events in your `boot()` method:

```php
use Illuminate\Support\Facades\Event;
use App\Events\Server\Created;
use App\Events\Server\Deleted;

public function boot(): void
{
    Event::listen(Created::class, function ($event) {
        $server = $event->server;
        // Initialize extension data for new server
    });

    Event::listen(Deleted::class, function ($event) {
        $server = $event->server;
        // Cleanup extension data
    });
}
```

**Available Events:**

- `App\Events\Server\Created` - Server was created
- `App\Events\Server\Deleted` - Server was deleted
- `App\Events\Server\Installed` - Server installation completed
- `App\Events\Server\Suspended` - Server was suspended
- `App\Events\Server\Unsuspended` - Server was unsuspended

## Egg-Based Filtering

Restrict extension features to specific server types (eggs) by using egg tags.

### Using the RestrictedByEggTags Trait

In server panel pages:

```php
use App\Filament\Server\Pages\Concerns\RestrictedByEggTags;
use Filament\Pages\Page;

class YourServerPage extends Page
{
    use RestrictedByEggTags;

    // Page only accessible for servers with these egg tags
    protected static array $eggTags = ['minecraft', 'java'];

    public static function canAccess(): bool
    {
        return parent::canAccess()
            && static::checkEggRestrictions()
            && (user()?->can('control.console', Filament::getTenant()) ?? false);
    }
}
```

### Registering Page Restrictions

In your `ExtensionController`:

```php
// Restrict a specific page to certain egg tags
$registry->serverPageRestriction(
    'my-extension',
    \App\Filament\Server\Pages\Extensions\MyExtension\MyPage::class,
    ['minecraft', 'java']
);
```

### Egg Tags in Navigation Items

Restrict navigation items to specific server types:

```php
$registry->navigationItem(
    'server-feature',
    'Server Feature',
    [
        'url' => '/server/feature',
        'icon' => 'tabler-code',
        'egg_tags' => ['minecraft', 'java'], // Only show for these server types
        'panels' => ['server' => true],
    ]
);
```

### Manual Egg Tag Check

Check egg tags manually:

```php
$server = Filament::getTenant();
$hasTag = $server->egg->tags()
    ->whereIn('name', ['minecraft', 'java'])
    ->exists();

if ($hasTag) {
    // Server has one of the required tags
}
```

## Advanced Features

### Database Migrations

Create migrations in `/migrations/` directory:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('my_extension_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('custom_field');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('my_extension_data');
    }
};
```

Migrations are automatically run when the extension is enabled.

### Render Hooks

Inject custom HTML, CSS, or JavaScript at various points:

```php
use Filament\View\PanelsRenderHook;

// Inject HTML
$registry->renderHook(
    PanelsRenderHook::FOOTER,
    fn () => view('extensions.my-extension.footer')
);

// Inject CSS
$registry->renderHook(
    PanelsRenderHook::STYLES_AFTER,
    fn () => '<style>.my-class { color: red; }</style>'
);

// Inject JavaScript
$registry->renderHook(
    PanelsRenderHook::SCRIPTS_AFTER,
    fn () => '<script>console.log("Extension loaded");</script>'
);
```

**Available Hooks:**

- `PanelsRenderHook::PAGE_START` - Top of page content
- `PanelsRenderHook::PAGE_END` - Bottom of page content
- `PanelsRenderHook::HEADER_START` - Start of header
- `PanelsRenderHook::HEADER_END` - End of header
- `PanelsRenderHook::FOOTER` - Footer area
- `PanelsRenderHook::SCRIPTS_BEFORE` - Before JS scripts
- `PanelsRenderHook::SCRIPTS_AFTER` - After JS scripts
- `PanelsRenderHook::STYLES_BEFORE` - Before CSS styles
- `PanelsRenderHook::STYLES_AFTER` - After CSS styles
- `PanelsRenderHook::CONTENT_START` - Start of main content
- `PanelsRenderHook::CONTENT_END` - End of main content
- `PanelsRenderHook::HEAD_END` - End of HTML head

### Navigation and User Menu Items

**Navigation Items (sidebar):**

```php
$registry->navigationItem(
    'unique-item-id',
    'Display Label',
    [
        'url' => '/admin/path',
        'icon' => 'tabler-icon-name',
        'sort' => 100,
        'group' => 'Group Name',
        'visible' => fn() => user()?->isAdmin(),
        'panels' => [
            'admin' => true,
            'server' => false,
        ],
    ]
);
```

**User Menu Items (dropdown):**

```php
$registry->userMenuItem(
    'unique-menu-id',
    'Menu Label',
    [
        'url' => '/admin/settings',
        'icon' => 'tabler-settings',
        'visible' => fn() => user()?->can('manage settings'),
        'panels' => [
            'admin' => true,
            'server' => true,
            'app' => true,
        ],
    ]
);
```

### Configuration Files

Place config files in `/config/`:

```php
// config/my-extension.php
return [
    'setting' => env('MY_EXTENSION_SETTING', 'default'),
    'api_key' => env('MY_EXTENSION_API_KEY'),
];

// Access in code
$value = config('my-extension.setting');
```

Config files are automatically loaded when the extension is enabled.

### Assets and Views

**Assets:**

Place in `/public/` directory:
```
public/
├── css/
│   └── styles.css
├── js/
│   └── script.js
└── images/
    └── logo.png
```

Access via: `asset('extensions/my-extension/css/styles.css')`

**Views:**

Place in `/views/` directory:
```php
// views/custom-page.blade.php
<div>
    <h1>{{ $title }}</h1>
</div>

// Usage
view('extensions.my-extension.custom-page', ['title' => 'Hello'])
```

## Best Practices

### 1. Naming Conventions

- **Extension ID:** kebab-case (`my-extension`)
- **Namespaces:** StudlyCase (`MyExtension`)
- **Permission categories:** snake_case (`my_feature`)
- **Database tables:** Prefix with extension name (`my_extension_data`)

### 2. Always Check Permissions

```php
// ✅ GOOD
->visible(fn() => user()?->can('permission.read', Filament::getTenant()) ?? false)

// ❌ BAD (no permission check)
->visible(true)
```

### 3. Handle Errors Gracefully

```php
try {
    // Your code
} catch (\Exception $e) {
    Log::error('Extension error: ' . $e->getMessage());
    Notification::make()
        ->title('Error')
        ->danger()
        ->send();
}
```

### 4. Use Events for Loose Coupling

```php
Event::listen(Server\Created::class, function ($event) {
    // Initialize your extension data for new server
});
```

### 5. Clean Up on Disable

```php
public function disable(): void
{
    Cache::forget('my-extension:*');
    Log::info('My Extension disabled');
}
```

### 6. Document Your Extension

Create a detailed README.md explaining:
- What the extension does
- How to configure it
- Required permissions
- Dependencies
- Usage examples

### 7. Version Your Extension

Follow semantic versioning (MAJOR.MINOR.PATCH):
- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes

## Complete Examples

### Minimal Extension

```php
// ExtensionController.php
<?php

namespace Extensions\MinimalExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        $registry->serverPermissions('minimal-extension', [
            'name' => 'minimal_feature',
            'icon' => 'tabler-star',
            'permissions' => ['use'],
            'descriptions' => [
                'desc' => 'Access to minimal extension features',
                'use' => 'Allows using the minimal extension',
            ],
        ]);

        $registry->navigationItem(
            'minimal-link',
            'Minimal Extension',
            [
                'url' => '/admin/minimal',
                'icon' => 'tabler-star',
                'panels' => ['admin' => true],
            ]
        );
    }

    public function boot(): void {}

    public function disable(): void {}
}
```

```json
// extension.json
{
    "id": "minimal-extension",
    "name": "Minimal Extension",
    "version": "1.0.0",
    "description": "A minimal example extension",
    "author": "Your Name",
    "author_email": "you@example.com",
    "types": ["plugin"],
    "controller": "ExtensionController"
}
```

### Full-Featured Extension

See the `/extensions/example-extension/` directory for a comprehensive example demonstrating all extension features.

## Troubleshooting

### Extension Not Appearing?

1. Verify `extension.json` is valid JSON
2. Ensure `id` field is set
3. Click "Scan for Extensions" in admin panel
4. Check file permissions: `sudo chown -R www-data:www-data extensions/`

### Extension Not Loading?

1. Check database `extensions` table for `enabled = 1`
2. Run `composer dump-autoload`
3. Clear caches: `php artisan config:clear && php artisan cache:clear`
4. Check Laravel logs: `storage/logs/laravel-*.log`

### Changes Not Visible?

1. Clear OPcache: `sudo systemctl restart php8.4-fpm`
2. Clear browser cache (Ctrl+F5)
3. Check assets were published: `ls -la public/extensions/my-extension/`

### Permission Errors?

1. Verify permissions are registered in `register()` method
2. Check user has the role with required permissions
3. Ensure `canAccess()` method checks correct permissions

## Next Steps

- Review the [example-extension](../../../extensions/example-extension/) for a complete working example
- Check out the [API Reference](../api-reference.md) for detailed method documentation
- Explore [Theme Development](themes.md) to add custom styling
- Learn about [Language Packs](language-packs.md) for translations

## Need Help?

- Check example extensions in `/extensions/`
- Review Filament documentation: https://filamentphp.com/docs
- Review Laravel documentation: https://laravel.com/docs
- Join the Pelican Panel community for support
