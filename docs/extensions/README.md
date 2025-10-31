# Pelican Panel Extension System

Welcome to the Pelican Panel Extension System documentation! This system allows you to extend and customize Pelican Panel with plugins, themes, and language packs.

## Quick Links

- **[Extension Development Guide](guides/extensions.md)** - Create functional extensions with pages, resources, widgets, and custom logic
- **[Theme Development Guide](guides/themes.md)** - Create custom themes to style your panel
- **[Language Pack Development Guide](guides/language-packs.md)** - Add new languages or customize translations
- **[API Reference](api-reference.md)** - Complete API documentation

## What Are Extensions?

Extensions are modular add-ons that enhance Pelican Panel's functionality and appearance. They are stored in the `/extensions/` directory and can be enabled/disabled through the admin panel.

### Extension Types

| Type | Purpose | Examples |
|------|---------|----------|
| **Plugin** | Add functionality, pages, resources, widgets | Server management tools, custom dashboards, integrations |
| **Theme** | Customize appearance with CSS/JS | Dark themes, color schemes, custom styling |
| **Language Pack** | Add or override translations | New languages, custom terminology, branding |

Extensions can combine multiple types (e.g., a plugin that includes its own theme and translations).

## Features

Extensions can:

- ✅ Add custom pages to Admin, App, and Server panels
- ✅ Create Filament resources and widgets
- ✅ Register custom permissions (admin and server-level)
- ✅ Add navigation items and user menu items
- ✅ Inject custom HTML/CSS/JS via render hooks
- ✅ Listen to system events
- ✅ Interact with servers via Wings daemon
- ✅ Run database migrations
- ✅ Publish public assets (CSS, JS, images)
- ✅ Restrict features by server type (egg tags)
- ✅ Provide reusable translations for other extensions

## Quick Start

### 1. Choose Your Extension Type

**Creating a Plugin?** → [Extension Development Guide](guides/extensions.md)
- Best for adding new functionality, pages, and features

**Creating a Theme?** → [Theme Development Guide](guides/themes.md)
- Best for visual customizations and styling

**Creating a Language Pack?** → [Language Pack Development Guide](guides/language-packs.md)
- Best for translations and custom terminology

### 2. Basic Structure

Every extension needs at minimum:

```
extensions/my-extension/
├── extension.json              # Metadata (required)
└── ExtensionController.php     # Main controller (required)
```

**extension.json:**
```json
{
    "id": "my-extension",
    "name": "My Extension",
    "description": "Brief description",
    "version": "1.0.0",
    "author": "Your Name",
    "author_email": "your.email@example.com",
    "types": ["plugin"],
    "controller": "ExtensionController"
}
```

**ExtensionController.php:**
```php
<?php

namespace Extensions\MyExtension;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Register your components here
    }

    public function boot(): void
    {
        // Set up event listeners, middleware, etc.
    }

    public function disable(): void
    {
        // Optional cleanup when disabled
    }
}
```

### 3. Enable Your Extension

1. Run `composer dump-autoload`  to register your extension's namespace
2. Navigate to `/admin/extensions` in your panel
3. Click "Scan for Extensions"
4. Find your extension and click "Enable"

## Example Extensions

Pelican includes several example extensions to help you get started:

### example-extension
Located in `/extensions/example-extension/`

A comprehensive plugin demonstrating:
- Admin, App, and Server panel pages
- Navigation items and user menu items
- Admin and server-level permissions
- Render hooks for custom HTML/CSS
- Event listeners
- Egg tag restrictions
- Permission checking with traits

[View Example Extension](../../extensions/example-extension/)

### dark-theme
Located in `/extensions/dark-theme/`

A theme extension demonstrating:
- CSS injection via render hooks
- Custom color schemes
- Filament component styling
- Public asset publishing

[View Dark Theme](../../extensions/dark-theme/)

### example-langpack
Located in `/extensions/example-langpack/`

A language pack demonstrating:
- Creating new languages (Pirate English!)
- Overriding existing translations
- Providing custom reusable labels
- Translation namespacing

[View Example Language Pack](../../extensions/example-langpack/)

## Extension Lifecycle

### Discovery
- Pelican scans `/extensions/` directory
- Reads `extension.json` from each folder
- Creates database records for new extensions (disabled by default)

### Enable
When you enable an extension:
1. Database migrations are run (from `/migrations/` directory)
2. Symlinks are created for auto-discovery:
   - `admin/Pages/` → `app/Filament/Admin/Pages/Extensions/{extension}/`
   - `server/Pages/` → `app/Filament/Server/Pages/Extensions/{extension}/`
   - (same for Resources and Widgets)
3. Assets are published: `/public/` → `public/extensions/{extension}/`
4. Views are symlinked: `/views/` → `resources/views/extensions/{extension}/`
5. Config files are loaded from `/config/`
6. `register()` method is called
7. `boot()` method is called

### Runtime
On every request (when enabled):
- ExtensionManager loads all enabled extensions
- Navigation and user menu items are registered
- Render hooks inject custom content
- Filament auto-discovers pages/resources/widgets via symlinks

### Disable
When you disable an extension:
1. `disable()` method is called
2. All symlinks are removed
3. Assets and views are unpublished
4. Extension is marked as disabled in database

## Directory Structure

Full extension directory structure (all optional except marked):

```
extensions/my-extension/
├── extension.json              # Metadata (REQUIRED)
├── ExtensionController.php     # Main controller (REQUIRED)
├── README.md                   # Documentation (recommended)
├── migrations/                 # Database migrations
│   └── 2024_01_01_000000_create_table.php
├── admin/                      # Admin panel components (auto-discovered)
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── app/                        # App panel components (auto-discovered)
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── server/                     # Server panel components (auto-discovered)
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── views/                      # Blade templates
│   ├── admin/
│   ├── app/
│   └── server/
├── public/                     # Public assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
├── config/                     # Configuration files
│   └── my-extension.php
├── lang/                       # Translation files
│   ├── en/
│   ├── de/
│   └── overrides/
└── Services/                   # Business logic classes
    └── MyService.php
```

## Best Practices

### 1. Naming Conventions
- **Extension ID:** kebab-case (`my-extension`)
- **Namespaces:** StudlyCase (`MyExtension`)
- **Permission categories:** snake_case (`my_feature`)
- **Database tables:** Prefix with extension ID (`my_extension_data`)

### 2. Always Check Permissions
```php
// Before displaying content
if (!user()?->can('viewList yourModel')) {
    abort(403);
}

// In server panel pages
if (!user()?->can('your_feature.read', Filament::getTenant())) {
    abort(403);
}
```

### 3. Handle Errors Gracefully
```php
try {
    // Risky operation
} catch (\Exception $e) {
    Log::error('Extension error: ' . $e->getMessage());
    Notification::make()
        ->title('Error')
        ->danger()
        ->send();
}
```

### 4. Clean Up on Disable
```php
public function disable(): void
{
    Cache::forget('my-extension:*');
    Log::info('My Extension disabled');
}
```

### 5. Document Your Extension
Create a detailed README.md explaining:
- What the extension does
- How to configure it
- Required permissions
- Dependencies
- Usage examples

## Troubleshooting

### Extension Not Appearing?
1. Verify `extension.json` is valid JSON
2. Ensure `id` field is set
3. Click "Scan for Extensions" in admin panel
4. Check file permissions: `sudo chown -R www-data:www-data extensions/`

### Extension Not Loading?
1. Run `composer dump-autoload`
2. Clear caches: `php artisan config:clear && php artisan cache:clear`
3. Check Laravel logs: `storage/logs/laravel-*.log`
4. Verify extension is enabled in database

### Changes Not Visible?
1. Clear OPcache: `sudo systemctl restart php8.4-fpm`
2. Clear browser cache (Ctrl+F5)
3. Check assets were published: `ls -la public/extensions/my-extension/`

## Getting Help

- Check the example extensions in `/extensions/`
- Review the detailed guides in `/docs/extensions/guides/`
- Consult the [API Reference](api-reference.md)
- Join the Pelican Panel community

## Next Steps

Ready to start building? Choose your path:

1. **[Create a Plugin](guides/extensions.md)** - Add functionality and features
2. **[Create a Theme](guides/themes.md)** - Customize the panel's appearance
3. **[Create a Language Pack](guides/language-packs.md)** - Add translations

Happy extending! 🚀
