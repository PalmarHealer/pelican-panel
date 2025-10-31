# Theme Development Guide

This guide will help you create custom themes for Pelican Panel using the extension system. Themes allow you to customize the visual appearance of your panel with CSS and JavaScript.

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Theme Structure](#theme-structure)
4. [Styling with CSS](#styling-with-css)
5. [Advanced Techniques](#advanced-techniques)
6. [Best Practices](#best-practices)
7. [Theme Examples](#theme-examples)
8. [Troubleshooting](#troubleshooting)

## Overview

Themes in Pelican Panel are special extensions that modify the visual appearance of the panel.

### How Themes Work

1. **CSS File Publishing** - Your theme's `public/` directory is copied to `public/extensions/{theme-id}/` when enabled
2. **CSS Injection** - The theme uses render hooks to inject CSS into the `<head>` of all panels
3. **Dynamic Styling** - Themes can inject inline styles or JavaScript for runtime customization

### What Themes Can Do

- Custom color schemes
- Font modifications
- Layout adjustments
- Dark/light mode variants
- Custom component styling
- Brand-specific designs
- CSS animations and transitions
- Dynamic theme switching with JavaScript

### What Themes Cannot Do

- Modify core functionality (use plugins for that)
- Change page structure
- Override security features

## Quick Start

### Creating Your First Theme

**1. Create directory structure:**

```bash
mkdir -p extensions/my-theme/public
cd extensions/my-theme
```

**2. Create `extension.json`:**

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

**3. Create `ExtensionController.php`:**

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
        // Link to the CSS file published in public/extensions/my-theme/
        // Add version parameter for cache busting
        $cssUrl = asset('extensions/my-theme/style.css?v=1.0.0');

        return <<<HTML
        <!-- My Custom Theme -->
        <link rel="stylesheet" href="{$cssUrl}">
        HTML;
    }
}
```

**4. Create `public/style.css`:**

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

**5. Enable the theme:**

```bash
composer dump-autoload
```

Navigate to `/admin/extensions`, scan for extensions, and enable your theme. Refresh to see your changes!

## Theme Structure

### Recommended Directory Layout

```
extensions/my-theme/
├── extension.json              # Theme metadata (REQUIRED)
├── ExtensionController.php     # Theme controller (REQUIRED)
├── README.md                   # Documentation (recommended)
└── public/                     # Public assets (REQUIRED for themes)
    ├── style.css              # Main stylesheet
    ├── admin.css              # Admin panel specific (optional)
    ├── app.css                # App panel specific (optional)
    ├── server.css             # Server panel specific (optional)
    ├── dark-mode.css          # Dark mode variant (optional)
    ├── fonts/                 # Custom fonts (optional)
    │   ├── CustomFont-Regular.woff2
    │   └── CustomFont-Bold.woff2
    └── images/                # Theme images (optional)
        └── logo.png
```

### Asset Publishing

When your theme is enabled:
```
extensions/my-theme/public/style.css
↓ (copied on enable)
public/extensions/my-theme/style.css
↓ (accessible via)
https://your-panel.com/extensions/my-theme/style.css
```

## Styling with CSS

### Understanding Filament's CSS Structure

Filament uses Tailwind CSS with custom properties (CSS variables) for theming.

#### Key CSS Variables

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

### Targeting Filament Components

Filament components have specific class prefixes:

```css
/* Sections/Cards */
.fi-section { /* ... */ }
.fi-card { /* ... */ }

/* Buttons */
.fi-btn { /* ... */ }
.fi-btn-primary { /* ... */ }

/* Tables */
.fi-table { /* ... */ }
.fi-table-row { /* ... */ }

/* Forms */
.fi-input { /* ... */ }
.fi-select { /* ... */ }

/* Sidebar */
.fi-sidebar { /* ... */ }
.fi-sidebar-item { /* ... */ }

/* Modals */
.fi-modal { /* ... */ }

/* Notifications */
.fi-no { /* ... */ }

/* Badges */
.fi-badge { /* ... */ }
```

### Common Customization Targets

#### Navigation Sidebar

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

#### Top Bar

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

#### Tables

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

#### Forms

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

#### Buttons

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

#### Cards/Sections

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

Create dark mode styles:

```css
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
use Filament\Facades\Filament;

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

### Dynamic CSS Variables

Generate CSS variables dynamically:

```php
protected function injectThemeStyles(): string
{
    $primaryColor = config('theme.primary_color', '#3b82f6');

    return <<<HTML
    <style>
        :root {
            --theme-primary: {$primaryColor};
        }
    </style>
    <link rel="stylesheet" href="/extensions/my-theme/style.css">
    HTML;
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

Avoid overly specific selectors:

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

Maintain good contrast ratios:

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

Always include version numbers for cache busting:

```php
$cssUrl = asset('extensions/my-theme/style.css?v=' . $this->getVersion());
```

```php
protected function getVersion(): string
{
    $manifest = json_decode(
        file_get_contents(base_path('extensions/my-theme/extension.json')),
        true
    );

    return $manifest['version'] ?? '1.0.0';
}
```

### 7. Minimize File Size

- Minify CSS for production
- Remove unused styles
- Combine multiple files when possible

### 8. Respect User Preferences

```css
/* Support reduced motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

### 9. Mobile Responsive

```css
/* Adjust for mobile screens */
@media (max-width: 768px) {
    .fi-sidebar {
        width: 100%;
    }
}
```

### 10. Performance

- Use CSS variables instead of repeating values
- Avoid expensive selectors (`*`, deep nesting)
- Use `will-change` sparingly

```css
/* Good - uses variables */
.fi-btn {
    color: var(--primary-500);
}

/* Bad - repeats value */
.fi-btn {
    color: #3b82f6;
}
```

## Theme Examples

### Minimalist Light Theme

```css
:root {
    --primary-500: #000000;
    --gray-50: #ffffff;
    --gray-900: #f5f5f5;
}

.fi-section {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 0; /* Square edges */
}

.fi-btn-primary {
    background: black;
    color: white;
    border-radius: 0;
}
```

### Neon/Cyberpunk Theme

```css
:root {
    --primary-500: #ff00ff;
    --secondary-500: #00ffff;
}

.fi-section {
    background: #0a0a0a;
    border: 2px solid #ff00ff;
    box-shadow: 0 0 20px rgba(255, 0, 255, 0.5);
}

.fi-btn-primary {
    background: linear-gradient(45deg, #ff00ff, #00ffff);
    animation: neon-pulse 2s ease infinite;
}

@keyframes neon-pulse {
    0%, 100% { box-shadow: 0 0 20px rgba(255, 0, 255, 0.8); }
    50% { box-shadow: 0 0 40px rgba(255, 0, 255, 1); }
}
```

### Nature/Earth Theme

```css
:root {
    --primary-500: #22c55e; /* Green */
    --gray-900: #1a2e1a; /* Dark forest */
}

.fi-section {
    background: linear-gradient(135deg, #2d4a2d 0%, #1a2e1a 100%);
    border: 1px solid #3a5a3a;
}

.fi-sidebar {
    background: linear-gradient(180deg, #2d4a2d, #1a2e1a);
}
```

### Dark Purple Theme

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

## Troubleshooting

### Styles Not Loading?

1. Clear browser cache (Ctrl+Shift+R)
2. Check extension is enabled in admin panel
3. Verify file exists: `public/extensions/my-theme/style.css`
4. Check browser console for 404 errors
5. Clear Laravel cache: `php artisan config:clear`

### Styles Not Applied?

1. Check CSS specificity (Filament styles might override yours)
2. Use `!important` sparingly when needed
3. Inspect element to see which styles are applied

```css
/* Increase specificity if needed */
.fi-panel .fi-section {
    background: your-color !important;
}
```

### Conflicts with Other Extensions?

Theme extensions load in order. If multiple themes are enabled:
- Later themes override earlier ones
- Use more specific selectors
- Consider disabling conflicting themes

### Testing Your Theme

1. **Enable Extension:** Admin → Extensions → Enable "Your Theme"
2. **Test All Panels:** Admin, App, and Server panels
3. **Test All Components:** Tables, forms, buttons, modals, notifications, sidebar
4. **Test Responsive:** Desktop, tablet, mobile
5. **Test Accessibility:** Keyboard navigation, screen readers, color contrast

## Multi-Type Extensions

Combine theme with other types:

```json
{
    "id": "complete-package",
    "types": ["plugin", "theme", "language-pack"]
}
```

This allows you to:
- Add functionality (plugin)
- Customize appearance (theme)
- Provide translations (language-pack)

All in one extension!

## Resources

- [Filament Documentation](https://filamentphp.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [CSS Custom Properties (MDN)](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)
- [Web Accessibility Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

## Example Theme

For a complete working example, see the `/extensions/dark-theme/` directory which demonstrates:
- CSS injection via render hooks
- Custom color schemes
- Filament component styling
- Public asset publishing

[View Dark Theme Example](../../../extensions/dark-theme/)

## Next Steps

- Review the [dark-theme](../../../extensions/dark-theme/) example
- Check out the [API Reference](../api-reference.md) for render hook details
- Explore [Extension Development](extensions.md) to add functionality alongside your theme
- Learn about [Language Packs](language-packs.md) for custom translations

Happy theming! 🎨
