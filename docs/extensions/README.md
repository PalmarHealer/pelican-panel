# Extension Development Guide

Welcome to the Pelican Panel Extension Development Guide! This comprehensive guide will help you create powerful extensions for Pelican Panel.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Extension Types](#extension-types)
3. [Quick Start](#quick-start)
4. [Extension Structure](#extension-structure)
5. [Core Concepts](#core-concepts)
6. [Advanced Topics](#advanced-topics)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

## Getting Started

### What Can Extensions Do?

Extensions can:

- Add custom pages to Admin, App, and Server panels
- Create custom Filament resources and widgets
- Register custom permissions (admin and server-level)
- Add navigation items and user menu items
- Inject custom HTML/CSS/JS via render hooks
- Listen to system events
- Interact with servers via Wings daemon
- Add custom themes and styling
- Provide language translations
- Run database migrations
- Publish assets (CSS, JS, images)

### Prerequisites

- Basic PHP knowledge
- Familiarity with Laravel framework
- Understanding of Filament PHP (optional but helpful)
- Composer installed
- Access to Pelican Panel installation

## Extension Types

Pelican Panel supports three types of extensions:

### 1. Functional Extensions

Full-featured extensions that can add pages, resources, widgets, and custom logic.

**Use cases:**
- Server management tools
- Custom dashboards
- Integration with third-party services
- Advanced server automation

### 2. Themes

Visual customizations using CSS and render hooks.

**Use cases:**
- Custom color schemes
- Layout modifications
- Branding customizations

See: [Creating Themes](creating-themes.md)

### 3. Language Packs

Translation files for new languages or overrides for existing translations.

**Use cases:**
- Adding new language support
- Customizing existing translations
- Regional dialect variations

See: [Creating Language Packs](creating-language-packs.md)

## Quick Start

### Creating Your First Extension

1. **Create the extension directory:**

```bash
cd /var/www/pelican
mkdir -p extensions/my-first-extension
cd extensions/my-first-extension
```

2. **Create `extension.json`:**

```json
{
    "id": "my-first-extension",
    "name": "My First Extension",
    "description": "A simple example extension",
    "version": "1.0.0",
    "author": "Your Name",
    "author_email": "your.email@example.com",
    "types": ["extension"],
    "controller": "ExtensionController"
}
```

3. **Create `ExtensionController.php`:**

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

4. **Register in Composer autoloader:**

Edit `/var/www/pelican/composer.json` and add to the `autoload.psr-4` section (if not already present):

```json
{
    "autoload": {
        "psr-4": {
            "Extensions\\": "extensions/"
        }
    }
}
```

Then run:

```bash
composer dump-autoload
```

5. **Enable the extension:**

- Navigate to `/admin/extensions` in your panel
- Click "Scan for Extensions"
- Find your extension and click "Enable"
- Refresh the page to see your footer message!

## Extension Structure

### Directory Layout

```
extensions/my-extension/
├── extension.json              # Metadata (required)
├── ExtensionController.php     # Main controller (required)
├── README.md                   # Documentation (recommended)
├── migrations/                 # Database migrations
│   └── 2024_01_01_000000_create_example_table.php
├── admin/                      # Admin panel components
│   ├── Pages/                  # Admin pages
│   ├── Resources/              # Admin resources
│   └── Widgets/                # Admin widgets
├── app/                        # App panel components
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── server/                     # Server panel components
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── Services/                   # Business logic services
├── Models/                     # Eloquent models
├── lang/                       # Translations
│   ├── en/
│   │   └── messages.php
│   └── overrides/
│       └── en/
│           └── profile.php
├── views/                      # Blade templates
│   ├── pages/
│   └── components/
├── public/                     # Public assets
│   ├── css/
│   ├── js/
│   └── images/
└── config/                     # Configuration files
    └── my-extension.php
```

### Required Files

#### extension.json

```json
{
    "id": "unique-extension-id",
    "name": "Human Readable Name",
    "description": "Brief description of what this extension does",
    "version": "1.0.0",
    "author": "Author Name",
    "author_email": "email@example.com",
    "types": ["extension"],
    "controller": "ExtensionController"
}
```

**Field descriptions:**

- `id` - Unique identifier (kebab-case, no spaces)
- `name` - Display name shown in admin panel
- `description` - Brief description of functionality
- `version` - Semantic version number
- `author` - Extension author name
- `author_email` - Contact email
- `types` - Array of types: `["extension"]`, `["theme"]`, `["language-pack"]`, or combinations
- `controller` - Main controller class name (usually `ExtensionController`)

#### ExtensionController.php

```php
<?php

namespace Extensions\YourExtensionName;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    /**
     * Register extension components.
     * Called during extension loading.
     */
    public function register(ExtensionRegistry $registry): void
    {
        // Register permissions, pages, hooks, etc.
    }

    /**
     * Boot the extension.
     * Called after all extensions are registered.
     */
    public function boot(): void
    {
        // Event listeners, middleware registration, etc.
    }

    /**
     * Cleanup when extension is disabled.
     */
    public function disable(): void
    {
        // Cleanup logic
    }
}
```

## Core Concepts

### Extension Registry

The `ExtensionRegistry` is your main interface for registering components. It's passed to your `register()` method.

**Available methods:**

- `permissions()` - Register admin/role permissions
- `serverPermissions()` - Register server-level subuser permissions
- `navigationItem()` - Add navigation items
- `userMenuItem()` - Add user menu items
- `renderHook()` - Inject HTML/CSS/JS at various points
- `profileTab()` - Add tabs to user profile page
- `consoleWidget()` - Add widgets to server console

See: [API Reference](api-reference.md)

### Permissions

#### Admin Permissions

Admin permissions control access to admin panel features.

```php
public function register(ExtensionRegistry $registry): void
{
    $registry->permissions([
        'yourModel' => ['viewList', 'view', 'create', 'update', 'delete']
    ]);
}
```

Then in your pages/resources:

```php
public static function canAccess(): bool
{
    return user()?->can('viewList yourModel') ?? false;
}
```

#### Server Permissions

Server permissions control what subusers can do within specific servers.

```php
public function register(ExtensionRegistry $registry): void
{
    $registry->serverPermissions('your-extension-id', [
        'name' => 'your_feature',
        'icon' => 'tabler-code',
        'permissions' => ['read', 'write', 'execute'],
        'descriptions' => [
            'desc' => 'Controls access to your feature',
            'read' => 'View feature data',
            'write' => 'Modify feature settings',
            'execute' => 'Execute feature actions',
        ],
        'egg_tags' => ['vanilla', 'java'], // Optional: restrict to specific server types
    ]);
}
```

Check permissions in server panel pages:

```php
use Filament\Facades\Filament;

if (user()?->can('your_feature.read', Filament::getTenant())) {
    // User has permission
}
```

### Working with Servers

#### Getting Current Server

In server panel pages, get the current server:

```php
use Filament\Facades\Filament;
use App\Models\Server;

$server = Filament::getTenant(); // Returns Server model
```

#### Communicating with Wings

Use the Wings HTTP client to interact with the server daemon:

```php
use Illuminate\Support\Facades\Http;

$server = Filament::getTenant();

// Example: Get server details from Wings
$response = Http::daemon($server)
    ->get('/api/servers/' . $server->uuid)
    ->json();

// Example: Execute command
Http::daemon($server)
    ->post('/api/servers/' . $server->uuid . '/commands', [
        'commands' => ['say Hello from extension!']
    ]);
```

The `Http::daemon()` macro automatically handles authentication and base URL configuration.

#### Server Events

Listen to server events:

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
        $server = $event->server;
        // Cleanup when server is deleted
    });
}
```

**Available events:**

- `App\Events\Server\Created` - Server was created
- `App\Events\Server\Deleted` - Server was deleted
- `App\Events\Server\Installed` - Server installation completed
- `App\Events\Server\Suspended` - Server was suspended
- `App\Events\Server\Unsuspended` - Server was unsuspended

### Egg-Based Filtering

Restrict extension features to specific server types (eggs) by using egg tags:

```php
// In server panel page
use App\Filament\Server\Pages\Concerns\RestrictedByEggTags;
use Filament\Pages\Page;

class YourServerPage extends Page
{
    use RestrictedByEggTags;

    protected static array $eggTags = ['minecraft', 'java'];

    // Page only accessible for servers with minecraft or java egg tags
}
```

Or check in `canAccess()`:

```php
public static function canAccess(): bool
{
    $server = Filament::getTenant();
    $hasTag = $server->egg->tags()->whereIn('name', ['minecraft', 'java'])->exists();

    return $hasTag && (user()?->can('control.console', $server) ?? false);
}
```

### Render Hooks

Inject HTML, CSS, or JavaScript at various points in the UI:

```php
use Filament\View\PanelsRenderHook;

$registry->renderHook(
    PanelsRenderHook::FOOTER,
    fn () => view('extensions.my-extension.footer')
);

// Inject CSS
$registry->renderHook(
    PanelsRenderHook::STYLES_AFTER,
    fn () => '<style>.my-custom-class { color: red; }</style>'
);

// Inject JavaScript
$registry->renderHook(
    PanelsRenderHook::SCRIPTS_AFTER,
    fn () => '<script>console.log("Extension loaded");</script>'
);
```

**Available hooks:**

- `PanelsRenderHook::PAGE_START` - Top of page content
- `PanelsRenderHook::PAGE_END` - Bottom of page content
- `PanelsRenderHook::CONTENT_START` - Start of main content
- `PanelsRenderHook::CONTENT_END` - End of main content
- `PanelsRenderHook::HEADER_START` - Start of header
- `PanelsRenderHook::HEADER_END` - End of header
- `PanelsRenderHook::FOOTER` - Footer area
- `PanelsRenderHook::STYLES_BEFORE` - Before CSS styles
- `PanelsRenderHook::STYLES_AFTER` - After CSS styles
- `PanelsRenderHook::SCRIPTS_BEFORE` - Before JS scripts
- `PanelsRenderHook::SCRIPTS_AFTER` - After JS scripts
- `PanelsRenderHook::HEAD_END` - End of HTML head

## Advanced Topics

### Creating Filament Pages

Extensions can add pages to any panel (Admin, App, or Server).

**Example server panel page:**

```php
<?php

namespace App\Filament\Server\Pages\Extensions\YourExtension;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class YourServerPage extends Page
{
    protected static string $view = 'extensions.your-extension.server.pages.your-page';

    protected static ?string $navigationIcon = 'tabler-code';

    protected static ?string $navigationLabel = 'Your Feature';

    protected static ?int $navigationSort = 50;

    public static function canAccess(): bool
    {
        // Check permission
        return user()?->can('your_feature.read', Filament::getTenant()) ?? false;
    }

    public function mount(): void
    {
        // Check permission or abort
        abort_unless(
            user()?->can('your_feature.read', Filament::getTenant()) ?? false,
            403
        );
    }

    // Your page logic here
}
```

Place in: `extensions/your-extension/server/Pages/YourServerPage.php`

### Database Migrations

Create migrations in `extensions/your-extension/migrations/`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('your_extension_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('some_field');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('your_extension_data');
    }
};
```

Migrations are automatically run when the extension is enabled.

### Publishing Assets

Place assets in `extensions/your-extension/public/`:

```
public/
├── css/
│   └── style.css
├── js/
│   └── script.js
└── images/
    └── logo.png
```

Assets are automatically copied to `public/extensions/your-extension/` when enabled.

Reference in templates:

```blade
<link rel="stylesheet" href="{{ asset('extensions/your-extension/css/style.css') }}">
<script src="{{ asset('extensions/your-extension/js/script.js') }}"></script>
<img src="{{ asset('extensions/your-extension/images/logo.png') }}" alt="Logo">
```

### Configuration Files

Create config files in `extensions/your-extension/config/`:

```php
<?php

return [
    'option1' => env('YOUR_EXTENSION_OPTION1', 'default'),
    'option2' => true,
];
```

Access in code:

```php
$value = config('your-extension.option1');
```

Config files are automatically loaded when the extension is enabled.

## Best Practices

### 1. Namespace Your Extension

Always use proper PSR-4 namespacing:

```php
namespace Extensions\YourExtensionName;
```

### 2. Check Permissions

Always verify permissions before displaying content or executing actions:

```php
// Admin permissions
if (user()?->can('viewList yourModel')) {
    // Show content
}

// Server permissions
if (user()?->can('your_feature.execute', Filament::getTenant())) {
    // Execute action
}
```

### 3. Handle Errors Gracefully

```php
try {
    // Your code
} catch (\Exception $e) {
    Log::error('Your Extension Error: ' . $e->getMessage());
    Notification::make()
        ->title('Error')
        ->danger()
        ->body('Something went wrong.')
        ->send();
}
```

### 4. Use Events for Loose Coupling

Instead of directly modifying core functionality, listen to events:

```php
Event::listen(Server\Created::class, function ($event) {
    // Initialize your extension data for new server
});
```

### 5. Clean Up on Disable

Always clean up in the `disable()` method:

```php
public function disable(): void
{
    // Remove cache entries
    Cache::forget('your-extension-*');

    // Log cleanup
    Log::info('Your Extension disabled and cleaned up');
}
```

### 6. Document Your Extension

Create a `README.md` in your extension directory explaining:

- What the extension does
- How to configure it
- What permissions it requires
- Any special setup steps

### 7. Version Your Extension

Follow semantic versioning (MAJOR.MINOR.PATCH):

- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes

## Troubleshooting

### Extension Not Appearing

1. Verify `extension.json` is valid JSON
2. Ensure `id` field is set
3. Click "Scan for Extensions" in admin panel
4. Check file permissions: `sudo chown -R www-data:www-data extensions/`

### Extension Not Loading

1. Check database `extensions` table for `enabled = 1`
2. Run `composer dump-autoload`
3. Clear caches: `php artisan config:clear && php artisan cache:clear`
4. Check Laravel logs: `storage/logs/laravel-*.log`

### Changes Not Visible

1. Clear OPcache: `sudo systemctl restart php8.4-fpm`
2. Clear browser cache (Ctrl+F5)
3. Check if assets were published: `ls -la public/extensions/your-extension/`

### Permission Errors

1. Verify permissions are registered in `register()` method
2. Check user has the role with required permissions
3. Ensure `canAccess()` method is checking correct permissions

### Wings Communication Failing

1. Verify server is running and Wings is accessible
2. Check Wings URL and token configuration in node settings
3. Test connection: `Http::daemon($server)->get('/api/system')->json()`

## Next Steps

- [Creating Themes](creating-themes.md) - Learn how to create custom themes
- [Creating Language Packs](creating-language-packs.md) - Add translation support
- [API Reference](api-reference.md) - Complete API documentation
- [Examples](examples.md) - Real-world extension examples

## Need Help?

- Check example extensions in `extensions/example-extension/`
- Review existing extensions for patterns and ideas
- Consult Filament documentation: https://filamentphp.com/docs
- Review Laravel documentation: https://laravel.com/docs
