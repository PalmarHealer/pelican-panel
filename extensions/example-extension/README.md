# Example Extension

This is a comprehensive example extension for Pelican Panel that demonstrates all available features and registration methods.

## Important Notes

### Asset Compilation

If your extension includes custom CSS or JavaScript with Tailwind classes or other build-time features, you need to run the build process:

```bash
# For development (with hot reload)
npm run dev

# For production (optimized builds)
npm run build
```

This is especially important for:
- Custom styles using Tailwind utility classes
- JavaScript that needs transpilation
- Any assets that require build-time processing

**Note:** Asset building does NOT happen automatically when enabling an extension.

## Table of Contents

- [Directory Structure](#directory-structure)
- [Extension Lifecycle](#extension-lifecycle)
- [Extension Controller](#extension-controller)
- [Auto-Discovery System](#auto-discovery-system)
- [Manual Registration](#manual-registration)
- [Permissions System](#permissions-system)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

---

## Directory Structure

```
example-extension/
├── extension.json              # Extension metadata (required)
├── ExtensionController.php     # Main controller (required)
├── README.md                   # This file
├── admin/                      # Admin panel components
│   ├── Pages/                  # Auto-discovered admin pages
│   │   └── ExampleAdminPage.php
│   ├── Resources/              # Auto-discovered admin resources (optional)
│   └── Widgets/                # Auto-discovered admin widgets (optional)
├── app/                        # App panel components
│   ├── Pages/                  # Auto-discovered app pages
│   │   └── ExampleAppPage.php
│   ├── Resources/              # Auto-discovered app resources (optional)
│   └── Widgets/                # Auto-discovered app widgets (optional)
├── server/                     # Server panel components
│   ├── Pages/                  # Auto-discovered server pages
│   │   └── ExampleServerPage.php
│   ├── Resources/              # Auto-discovered server resources (optional)
│   └── Widgets/                # Auto-discovered server widgets (optional)
├── views/                      # Blade view templates
│   ├── footer-message.blade.php
│   ├── page-notice.blade.php
│   ├── admin/pages/            # Page-specific views
│   ├── app/pages/
│   └── server/pages/
├── migrations/                 # Database migrations (optional)
├── public/                     # Public assets - CSS, JS, images (optional)
├── config/                     # Configuration files (optional)
├── Services/                   # Business logic classes (optional)
└── lang/                       # Translation files (optional)
```

### What This Example Demonstrates

This example extension shows:
- ✅ Auto-discovered Pages (admin, app, server panels)
- ✅ Navigation items (admin and server panels)
- ✅ User menu items (all panels)
- ✅ Render hooks (footer, styles, page notices)
- ✅ Admin/Role permissions
- ✅ Server panel (subuser) permissions
- ✅ Blade view templates

### Required Files

Only two files are absolutely required:

1. **`extension.json`** - Extension metadata
2. **`ExtensionController.php`** - Main controller implementing `ExtensionInterface`

All other directories and files are optional.

---

## Extension Lifecycle

### 1. Discovery Phase

When Pelican boots (or when you click "Scan for Extensions"):

```
1. Scan /extensions/ directory
2. Read extension.json from each folder
3. Check if extension exists in database
4. If new: create database record (disabled by default)
```

### 2. Enable Phase

When an extension is enabled via the admin panel:

```
1. Run migrations from /migrations/ directory
2. Create symlinks:
   - admin/Pages/     → app/Filament/Admin/Pages/Extensions/example-extension/
   - admin/Resources/ → app/Filament/Admin/Resources/Extensions/example-extension/
   - admin/Widgets/   → app/Filament/Admin/Widgets/Extensions/example-extension/
   - (same for app/ and server/ directories)
3. Publish assets from /public/ to public/extensions/example-extension/
4. Create view symlink from /views/ to resources/views/extensions/example-extension/
5. Load config files from /config/
6. Load ExtensionController
7. Call register() method
8. Call boot() method
9. Mark as enabled in database
```

### 3. Runtime Phase

On every request (when extension is enabled):

```
1. ExtensionManager discovers and registers all enabled extensions
2. Panel providers call getNavigationItemsForPanel() and getUserMenuItemsForPanel()
3. Filament's auto-discovery finds pages/resources/widgets via symlinks
4. Custom registrations (hooks, permissions) are applied
5. Extension components are rendered
```

### 4. Disable Phase

When an extension is disabled:

```
1. Call disable() method on controller
2. Remove all symlinks
3. Unpublish assets and views
4. Mark as disabled in database
```

### 5. Uninstall Phase

When an extension is uninstalled:

```
1. Disable the extension (if enabled)
2. Rollback migrations
3. Delete extension folder completely
4. Remove database record
```

---

## Extension Controller

The `ExtensionController.php` is the heart of your extension. It must implement `ExtensionInterface`.

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

### Basic Template

```php
<?php

namespace Extensions\YourExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Register components here
    }

    public function boot(): void
    {
        // Set up event listeners, middleware, etc.
    }

    public function disable(): void
    {
        // Optional cleanup
    }
}
```

---

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

```php
// File: admin/Pages/ExamplePage.php
namespace App\Filament\Admin\Pages\Extensions\ExampleExtension;

use Filament\Pages\Page;

class ExamplePage extends Page
{
    protected static ?string $slug = 'extensions/example-page';
    // ...
}
```

### Directory Name → Namespace Mapping

The extension manager automatically converts kebab-case directory names to StudlyCase for namespaces:

- Directory: `example-extension`
- Namespace: `ExampleExtension`

So if your extension ID is `my-awesome-extension`, your namespace would be `MyAwesomeExtension`.

### What Does NOT Get Auto-Discovered

These must be manually registered in `register()`:

- ❌ Navigation items
- ❌ User menu items
- ❌ Render hooks
- ❌ Permissions (admin/role and server panel)

---

## Manual Registration

Use the `ExtensionRegistry` in your `register()` method to register components.

**Available Registry Methods:**
- `permissions()` - Admin/role-based permissions (✅ demonstrated in example)
- `serverPermissions()` - Server panel subuser permissions (✅ demonstrated in example)
- `navigationItem()` - Navigation sidebar items (✅ demonstrated in example)
- `userMenuItem()` - User dropdown menu items (✅ demonstrated in example)
- `renderHook()` - Custom HTML/views injection (✅ demonstrated in example)

### Navigation Items

Register navigation items for admin and/or server panels (app panel has navigation disabled).

```php
$registry->navigationItem(
    'unique-item-id',           // Unique identifier
    'Display Label',            // Label (or callable returning label)
    [
        'url' => '/admin/path',                    // URL (or callable)
        'icon' => 'tabler-icon-name',              // Tabler icon name
        'sort' => 100,                             // Sort order (higher = lower)
        'group' => 'Group Name',                   // Navigation group (admin only)
        'visible' => fn() => user()?->isAdmin(),   // Visibility condition (optional)
        'panels' => [
            'admin' => true,   // Show in admin panel
            'server' => false, // Don't show in server panel
        ],
    ]
);
```

**Dynamic Labels/URLs:**

```php
$registry->navigationItem(
    'server-specific-item',
    fn() => 'Server: ' . Filament::getTenant()?->name,  // Dynamic label
    [
        'url' => fn() => MyPage::getUrl(),               // Dynamic URL
        'panels' => ['server' => true],
    ]
);
```

### User Menu Items

Register items in the user dropdown menu.

```php
$registry->userMenuItem(
    'unique-menu-id',
    'Menu Label',
    [
        'url' => '/admin/settings',
        'icon' => 'tabler-settings',
        'visible' => fn() => user()?->can('manage settings'),  // Optional
        'panels' => [
            'admin' => true,
            'server' => true,
            'app' => true,     // User menu works in all panels
        ],
    ]
);
```

### Render Hooks

Inject custom HTML/views at specific points in the UI.

```php
use Filament\View\PanelsRenderHook;

$registry->renderHook(
    PanelsRenderHook::FOOTER,
    fn () => view('extensions.my-extension.footer')
);

$registry->renderHook(
    PanelsRenderHook::STYLES_AFTER,
    fn () => '<style>.my-class { color: red; }</style>'
);

$registry->renderHook(
    PanelsRenderHook::PAGE_START,
    fn () => '<div class="alert">Custom message</div>'
);
```

**Available Hooks:**

- `PanelsRenderHook::PAGE_START`
- `PanelsRenderHook::PAGE_END`
- `PanelsRenderHook::HEADER_START`
- `PanelsRenderHook::HEADER_END`
- `PanelsRenderHook::FOOTER`
- `PanelsRenderHook::SCRIPTS_BEFORE`
- `PanelsRenderHook::SCRIPTS_AFTER`
- `PanelsRenderHook::STYLES_BEFORE`
- `PanelsRenderHook::STYLES_AFTER`
- `PanelsRenderHook::CONTENT_START`
- `PanelsRenderHook::CONTENT_END`


---

## Permissions System

Pelican has a **dual permission system**: Admin/Role permissions and Server Panel (subuser) permissions.

### 1. Admin/Role Permissions

For panel-wide access control (who can manage extensions, view logs, etc.).

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

// In Filament components
->visible(fn() => user()->can('view yourModel'))
```

**How They Work:**

1. Permissions are created in the database
2. Assigned to roles via Role management UI
3. Users inherit permissions from their roles
4. Root admin bypasses all checks

### 2. Server Panel (Subuser) Permissions

For server-specific access control (who can access files, console, etc. on a specific server).

**Registration:**

```php
$registry->serverPermissions('extension-id', [
    'name' => 'permission_category',              // Category name (used in permission keys)
    'icon' => 'tabler-icon-name',                 // Icon for permission group
    'permissions' => ['read', 'write', 'execute'], // Available permissions
    'descriptions' => [
        'desc' => 'Overall description of this permission category.',
        'read' => 'Description of the read permission.',
        'write' => 'Description of the write permission.',
        'execute' => 'Description of the execute permission.',
    ],
]);
```

**Permission Key Format:**

```
{category}.{permission}
```

Example: `permission_category.read`

**Checking Permissions:**

```php
use Filament\Facades\Filament;

// In server panel pages/resources
$server = Filament::getTenant();  // Current server
$user = user();

if ($user->can('permission_category.read', $server)) {
    // User has permission on this specific server
}

// In visibility checks
->visible(fn() => user()?->can('permission_category.write', Filament::getTenant()) ?? false)
```

**How They Work:**

1. Extension registers permission category
2. Server owner can grant/revoke permissions to subusers via UI
3. Permissions are server-specific (user may have different permissions on different servers)
4. Server owner always has all permissions

**Best Practices:**

- Use descriptive category names
- Provide clear descriptions for each permission
- Choose appropriate icons
- Keep permission names simple and consistent
- Test permission checks thoroughly

### Filtering by Permissions in Server Pages

You can hide entire pages based on permissions:

```php
namespace App\Filament\Server\Pages\Extensions\YourExtension;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class YourPage extends Page
{
    public static function canAccess(): bool
    {
        $server = Filament::getTenant();
        return user()?->can('your_category.read', $server) ?? false;
    }
}
```

---

## Advanced Features

### Event Listeners

Listen to Pelican events in your `boot()` method:

```php
use Illuminate\Support\Facades\Event;
use App\Events\Server\Created;
use App\Events\Server\Deleted;

public function boot(): void
{
    Event::listen(Created::class, function ($event) {
        $server = $event->server;
        // Do something when server is created
    });

    Event::listen(Deleted::class, function ($event) {
        // Cleanup when server is deleted
    });
}
```

**Available Events:**

- `App\Events\Server\Created`
- `App\Events\Server\Deleted`
- `App\Events\Server\Installed`
- `App\Events\ActivityLogged`
- And many more in `app/Events/`

### Database Migrations

Place migration files in `/migrations/` directory:

```php
// migrations/2024_01_01_000000_create_example_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('example_extension_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('custom_field');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('example_extension_data');
    }
};
```

**Migration Lifecycle:**

- Run automatically when extension is enabled
- Tracked in extension database record
- Rolled back when extension is uninstalled

### Views and Assets

**Views:**

Place Blade templates in `/views/` directory:

```php
// views/custom-page.blade.php
<div>
    <h1>{{ $title }}</h1>
    <p>{{ $content }}</p>
</div>

// Usage in render hook or controller
view('extensions.example-extension.custom-page', ['title' => 'Hello'])
```

**Assets:**

Place CSS/JS/images in `/public/` directory:

```
public/
├── css/
│   └── styles.css
├── js/
│   └── script.js
└── images/
    └── logo.png
```

Access via: `/extensions/example-extension/css/styles.css`

### Configuration Files

Place config files in `/config/` directory:

```php
// config/example.php
return [
    'setting' => 'value',
    'api_key' => env('EXAMPLE_API_KEY'),
];

// Access in your code
config('example-extension.example.setting')
```

### Services and Business Logic

Create service classes in `/Services/` directory:

```php
// Services/ExampleService.php
namespace Extensions\ExampleExtension\Services;

class ExampleService
{
    public function doSomething(): void
    {
        // Business logic
    }
}

// Usage
app(ExampleService::class)->doSomething();
```

### Translations

Place language files in `/lang/` directory:

```php
// lang/en/messages.php
return [
    'welcome' => 'Welcome to Example Extension',
    'error' => 'Something went wrong',
];

// Usage
trans('example-extension::messages.welcome')
```

---

## Best Practices

### 1. Naming Conventions

- **Extension ID:** Use kebab-case (`my-extension`)
- **Namespaces:** Use StudlyCase (`MyExtension`)
- **Permission categories:** Use snake_case (`my_feature`)
- **Database tables:** Prefix with extension name (`example_extension_data`)

### 2. Permission Checking

Always check permissions before displaying sensitive data or allowing actions:

```php
// ✅ GOOD
->visible(fn() => user()?->can('permission.read', Filament::getTenant()) ?? false)

// ❌ BAD (no permission check)
->visible(true)
```

### 3. Error Handling

Wrap risky operations in try-catch blocks:

```php
try {
    // Risky operation
} catch (\Exception $e) {
    \Log::error('Extension error: ' . $e->getMessage());
    Notification::make()
        ->title('Error')
        ->body($e->getMessage())
        ->danger()
        ->send();
}
```

### 4. Database Queries

Use Eloquent models and relationships:

```php
// ✅ GOOD
$server = Server::with('allocations')->find($id);

// ❌ BAD (N+1 problem)
foreach (Server::all() as $server) {
    $allocations = $server->allocations; // Separate query each time
}
```

### 5. Assets

Minimize and version your assets:

```html
<link rel="stylesheet" href="/extensions/my-extension/css/styles.min.css?v=1.0.0">
```

### 6. Cleanup

Always clean up in your `disable()` method:

```php
public function disable(): void
{
    // Remove cached data
    Cache::forget('extension-data');

    // Log the event
    Log::info('Extension disabled');
}
```

### 7. Testing

Test your extension thoroughly:

- Enable/disable multiple times
- Test with different user roles
- Test with different server types
- Test permission checks
- Test on both admin and server panels

### 8. Documentation

Document your extension:

- Create a detailed README.md
- Comment complex logic
- Provide usage examples
- List required dependencies

---

## Complete Example

Here's a complete, minimal extension:

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
                'desc' => 'Access to minimal extension features.',
                'use' => 'Allows using the minimal extension.',
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

    public function boot(): void
    {
        // Nothing needed
    }

    public function disable(): void
    {
        // Nothing needed
    }
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
    "controller": "ExtensionController.php"
}
```

That's it! Place these two files in `/extensions/minimal-extension/` and scan for extensions.

---

## Need Help?

- Check the Pelican Panel documentation
- Look at this example extension for reference
- Review the ExtensionRegistry and ExtensionManager source code
- Join the Pelican Panel community for support
