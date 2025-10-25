# Creating Themes

This guide will walk you through creating custom themes for Pelican Panel using the extension system.

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Theme Structure](#theme-structure)
4. [Styling with CSS](#styling-with-css)
5. [Advanced Techniques](#advanced-techniques)
6. [Best Practices](#best-practices)
7. [Examples](#examples)

## Overview

Themes in Pelican Panel are special extensions that modify the visual appearance of the panel. They work by:

- Injecting custom CSS via render hooks
- Optionally adding JavaScript for dynamic styling
- Using Filament's theming capabilities
- Supporting panel-specific styles (Admin, App, Server)

### What Themes Can Do

- Custom color schemes
- Font modifications
- Layout adjustments
- Dark/light mode variants
- Custom component styling
- Brand-specific designs

### What Themes Cannot Do

- Modify core functionality
- Change page structure (use functional extensions for that)
- Override security features

## Quick Start

### Creating a Basic Theme

1. **Create directory structure:**

```bash
mkdir -p extensions/my-theme/public
cd extensions/my-theme
```

2. **Create `extension.json`:**

```json
{
    "id": "my-theme",
    "name": "My Custom Theme",
    "description": "A beautiful custom theme for Pelican Panel",
    "version": "1.0.0",
    "author": "Your Name",
    "author_email": "your.email@example.com",
    "types": ["theme"],
    "controller": "ExtensionController"
}
```

3. **Create `ExtensionController.php`:**

```php
<?php

namespace Extensions\MyTheme;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;
use Filament\View\PanelsRenderHook;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Inject theme CSS in the HTML head
        $registry->renderHook(
            PanelsRenderHook::HEAD_END,
            fn () => $this->injectThemeStyles()
        );
    }

    public function boot(): void
    {
        // Boot logic if needed
    }

    public function disable(): void
    {
        // Cleanup when disabled
    }

    protected function injectThemeStyles(): string
    {
        $cssUrl = asset('extensions/my-theme/style.css?v=1.0.0');

        return <<<HTML
        <!-- My Custom Theme -->
        <link rel="stylesheet" href="{$cssUrl}">
        HTML;
    }
}
```

4. **Create `public/style.css`:**

```css
/* My Custom Theme */

/* Override primary color */
:root {
    --primary-50: #f0f9ff;
    --primary-100: #e0f2fe;
    --primary-200: #bae6fd;
    --primary-300: #7dd3fc;
    --primary-400: #38bdf8;
    --primary-500: #0ea5e9;
    --primary-600: #0284c7;
    --primary-700: #0369a1;
    --primary-800: #075985;
    --primary-900: #0c4a6e;
}

/* Custom panel header */
.fi-topbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Rounded cards */
.fi-section {
    border-radius: 12px;
}
```

5. **Enable the theme:**

```bash
composer dump-autoload
```

Then navigate to `/admin/extensions`, scan for extensions, and enable your theme.

## Theme Structure

### Recommended Directory Layout

```
extensions/my-theme/
├── extension.json              # Theme metadata
├── ExtensionController.php     # Controller that injects CSS
├── README.md                   # Theme documentation
└── public/                     # Public assets
    ├── style.css              # Main theme CSS
    ├── admin.css              # Admin panel specific (optional)
    ├── app.css                # App panel specific (optional)
    ├── server.css             # Server panel specific (optional)
    ├── dark-mode.css          # Dark mode variant (optional)
    ├── fonts/                 # Custom fonts (optional)
    └── images/                # Theme images (optional)
```

## Styling with CSS

### Understanding Filament's CSS Structure

Filament uses Tailwind CSS with custom properties for theming. Key CSS variables:

```css
:root {
    /* Primary color (main brand color) */
    --primary-50: ...;
    --primary-500: ...;
    --primary-900: ...;

    /* Danger color (errors, delete actions) */
    --danger-50: ...;
    --danger-500: ...;

    /* Success color (success messages, confirmations) */
    --success-50: ...;
    --success-500: ...;

    /* Warning color */
    --warning-50: ...;
    --warning-500: ...;

    /* Info color */
    --info-50: ...;
    --info-500: ...;

    /* Gray scale (backgrounds, borders, text) */
    --gray-50: ...;
    --gray-500: ...;
    --gray-900: ...;
}
```

### Panel-Specific Styling

Target specific panels using CSS selectors:

```css
/* Admin panel only */
[data-panel="admin"] {
    /* Your styles */
}

/* App panel only */
[data-panel="app"] {
    /* Your styles */
}

/* Server panel only */
[data-panel="server"] {
    /* Your styles */
}
```

### Common Customization Targets

#### 1. Navigation Sidebar

```css
/* Sidebar background */
.fi-sidebar {
    background: #1a1a2e;
}

/* Navigation items */
.fi-sidebar-item {
    color: #e0e0e0;
}

/* Active navigation item */
.fi-sidebar-item-active {
    background: #16213e;
    color: #fff;
}

/* Navigation icons */
.fi-sidebar-item-icon {
    color: #64ffda;
}
```

#### 2. Top Bar

```css
/* Top bar background */
.fi-topbar {
    background: linear-gradient(90deg, #1a1a2e, #16213e);
    border-bottom: 2px solid #64ffda;
}

/* User menu button */
.fi-user-menu-trigger {
    border-radius: 9999px;
}
```

#### 3. Tables

```css
/* Table header */
.fi-table-header {
    background: #f8f9fa;
}

/* Table rows */
.fi-table-row:hover {
    background: #f1f3f5;
}

/* Table cells */
.fi-table-cell {
    border-color: #e9ecef;
}
```

#### 4. Forms

```css
/* Form inputs */
.fi-input {
    border-radius: 8px;
    border-color: #cbd5e0;
}

.fi-input:focus {
    border-color: #4299e1;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

/* Labels */
.fi-form-label {
    font-weight: 600;
    color: #2d3748;
}
```

#### 5. Buttons

```css
/* Primary button */
.fi-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.fi-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Secondary button */
.fi-btn-secondary {
    border: 2px solid #667eea;
    color: #667eea;
}
```

#### 6. Cards/Sections

```css
/* Section container */
.fi-section {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Section header */
.fi-section-header {
    background: linear-gradient(90deg, #f7fafc, #edf2f7);
    border-bottom: 1px solid #e2e8f0;
}
```

### Dark Mode Support

Create a separate dark mode stylesheet:

```css
/* dark-mode.css */

/* Automatically apply when user selects dark mode */
.dark {
    /* Background colors */
    --gray-50: #18181b;
    --gray-100: #27272a;
    --gray-200: #3f3f46;
    --gray-300: #52525b;
    --gray-400: #71717a;
    --gray-500: #a1a1aa;
    --gray-600: #d4d4d8;
    --gray-700: #e4e4e7;
    --gray-800: #f4f4f5;
    --gray-900: #fafafa;
}

.dark .fi-sidebar {
    background: #0f0f0f;
    border-right-color: #27272a;
}

.dark .fi-topbar {
    background: #18181b;
    border-bottom-color: #27272a;
}

.dark .fi-section {
    background: #18181b;
    border-color: #27272a;
}
```

## Advanced Techniques

### Using Multiple CSS Files

Load different stylesheets based on panel or user preference:

```php
protected function injectThemeStyles(): string
{
    $panel = Filament::getCurrentPanel()->getId();
    $baseUrl = asset('extensions/my-theme');

    $html = "<!-- My Custom Theme -->\n";
    $html .= "<link rel=\"stylesheet\" href=\"{$baseUrl}/style.css?v=1.0.0\">\n";

    // Panel-specific styles
    if (file_exists(public_path("extensions/my-theme/{$panel}.css"))) {
        $html .= "<link rel=\"stylesheet\" href=\"{$baseUrl}/{$panel}.css?v=1.0.0\">\n";
    }

    return $html;
}
```

### Dynamic Theming with JavaScript

Add JavaScript for runtime theme switching:

```php
protected function injectThemeStyles(): string
{
    $cssUrl = asset('extensions/my-theme/style.css?v=1.0.0');
    $jsUrl = asset('extensions/my-theme/theme.js?v=1.0.0');

    return <<<HTML
    <link rel="stylesheet" href="{$cssUrl}">
    <script src="{$jsUrl}"></script>
    HTML;
}
```

```javascript
// public/theme.js
document.addEventListener('DOMContentLoaded', function() {
    // Add theme switcher
    const themeSwitcher = document.createElement('button');
    themeSwitcher.textContent = '🌙';
    themeSwitcher.onclick = function() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme',
            document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        );
    };

    // Restore saved theme
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
});
```

### Custom Fonts

Include custom fonts in your theme:

```css
/* style.css */
@font-face {
    font-family: 'CustomFont';
    src: url('/extensions/my-theme/fonts/CustomFont-Regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
}

@font-face {
    font-family: 'CustomFont';
    src: url('/extensions/my-theme/fonts/CustomFont-Bold.woff2') format('woff2');
    font-weight: 700;
    font-style: normal;
}

body {
    font-family: 'CustomFont', system-ui, -apple-system, sans-serif;
}
```

### CSS Animations

Add smooth transitions and animations:

```css
/* Smooth transitions */
* {
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

/* Fade in animation for cards */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fi-section {
    animation: fadeIn 0.3s ease;
}

/* Pulse animation for notifications */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.fi-notification {
    animation: pulse 2s ease-in-out infinite;
}
```

## Best Practices

### 1. Use CSS Variables

Don't hardcode colors - use CSS variables for easy customization:

```css
:root {
    --theme-primary: #667eea;
    --theme-secondary: #764ba2;
    --theme-accent: #64ffda;
}

.fi-btn-primary {
    background: var(--theme-primary);
}
```

### 2. Maintain Specificity Balance

Avoid overly specific selectors that are hard to override:

```css
/* ❌ Too specific */
.fi-sidebar .fi-sidebar-group .fi-sidebar-item.fi-sidebar-item-active {
    color: red;
}

/* ✅ Better */
.fi-sidebar-item-active {
    color: red;
}
```

### 3. Test Across Panels

Always test your theme in all three panels:

- Admin panel (`/admin`)
- App panel (`/app`)
- Server panel (`/server/{uuid}`)

### 4. Ensure Accessibility

Maintain good contrast ratios and accessibility:

```css
/* Ensure text is readable */
.fi-section {
    background: #ffffff;
    color: #1a202c; /* WCAG AA compliant contrast */
}

/* Maintain focus indicators */
.fi-input:focus {
    outline: 2px solid #4299e1;
    outline-offset: 2px;
}
```

### 5. Support Both Light and Dark Modes

Always provide dark mode support:

```css
/* Light mode */
:root {
    --bg-primary: #ffffff;
    --text-primary: #1a202c;
}

/* Dark mode */
.dark {
    --bg-primary: #1a202c;
    --text-primary: #f7fafc;
}

.fi-section {
    background: var(--bg-primary);
    color: var(--text-primary);
}
```

### 6. Version Your Assets

Always include version numbers in asset URLs for cache busting:

```php
$cssUrl = asset('extensions/my-theme/style.css?v=' . $this->getVersion());
```

```php
protected function getVersion(): string
{
    $manifest = json_decode(
        file_read_contents(base_path('extensions/my-theme/extension.json')),
        true
    );

    return $manifest['version'] ?? '1.0.0';
}
```

### 7. Minimize File Size

- Minify CSS for production
- Remove unused styles
- Combine multiple files when possible

## Examples

### Example 1: Dark Purple Theme

```css
/* Dark Purple Theme */
:root {
    /* Purple gradient primary */
    --primary-400: #9f7aea;
    --primary-500: #805ad5;
    --primary-600: #6b46c1;
}

.fi-topbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.fi-sidebar {
    background: #1a1a2e;
}

.fi-sidebar-item-active {
    background: linear-gradient(90deg, #667eea, #764ba2);
}
```

### Example 2: Minimal Clean Theme

```css
/* Minimal Clean Theme */
:root {
    --primary-500: #2563eb;
}

/* Remove all shadows */
.fi-section,
.fi-modal,
.fi-dropdown {
    box-shadow: none !important;
    border: 1px solid #e5e7eb;
}

/* Simplified buttons */
.fi-btn {
    border-radius: 6px;
    font-weight: 500;
}

/* Clean table design */
.fi-table {
    border: 1px solid #e5e7eb;
}

.fi-table-row {
    border-bottom: 1px solid #f3f4f6;
}
```

### Example 3: Cyberpunk Theme

```css
/* Cyberpunk Theme */
:root {
    --primary-500: #00ff9f;
    --danger-500: #ff0055;
}

body {
    background: #0a0e27;
    font-family: 'Courier New', monospace;
}

.fi-topbar {
    background: #0a0e27;
    border-bottom: 2px solid #00ff9f;
    box-shadow: 0 0 20px rgba(0, 255, 159, 0.3);
}

.fi-sidebar {
    background: #0d1117;
    border-right: 1px solid #00ff9f;
}

.fi-section {
    background: #161b22;
    border: 1px solid #00ff9f;
    box-shadow: 0 0 15px rgba(0, 255, 159, 0.1);
}

.fi-btn-primary {
    background: #00ff9f;
    color: #0a0e27;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 0 15px rgba(0, 255, 159, 0.5);
}
```

## Troubleshooting

### Styles Not Applying

1. Clear browser cache (Ctrl+F5)
2. Check asset URL is correct: view page source and verify CSS link
3. Verify file was published: `ls public/extensions/my-theme/`
4. Check browser console for 404 errors
5. Clear Laravel cache: `php artisan config:clear`

### Styles Overridden

Increase specificity or use `!important` (sparingly):

```css
/* If your style is being overridden */
.fi-section {
    background: #ffffff !important;
}
```

### Dark Mode Not Working

Ensure you're targeting the `.dark` class:

```css
.dark .fi-section {
    background: #1a202c;
}
```

## Next Steps

- Review the [example-extension](../../extensions/example-extension/) for reference
- Check out [Language Pack guide](creating-language-packs.md) for adding translations
- Explore [API Reference](api-reference.md) for advanced customizations

## Resources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Filament Documentation](https://filamentphp.com/docs/3.x/panels/themes)
- [MDN CSS Reference](https://developer.mozilla.org/en-US/docs/Web/CSS)
