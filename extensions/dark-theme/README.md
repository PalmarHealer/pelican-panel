# 🎨 Dark Theme Extension

A beautiful dark theme for Pelican Panel that demonstrates the full capabilities of the theme extension system.

## Directory Structure

```
extensions/dark-theme/
├── extension.json              # Extension metadata
├── ExtensionController.php     # Theme registration and CSS injection
├── public/                     # Assets (copied to public/extensions/dark-theme/)
│   └── dark.css               # Main theme stylesheet
└── README.md                   # This file
```

## How Theme Extensions Work

### 1. **CSS File Publishing**

When a theme extension is enabled, its `public/` directory is copied to `public/extensions/{extension-id}/`:

```
extensions/dark-theme/public/dark.css
↓ (copied on enable)
public/extensions/dark-theme/dark.css
↓ (accessible via)
https://your-panel.com/extensions/dark-theme/dark.css
```

### 2. **CSS Injection**

The theme uses a render hook to inject CSS into the `<head>` of all panels:

```php
// ExtensionController.php
$registry->renderHook(
    PanelsRenderHook::HEAD_END,
    fn() => $this->injectThemeStyles()
);
```

This adds:
```html
<link rel="stylesheet" href="/extensions/dark-theme/dark.css">
```

### 3. **Dynamic Styling**

Themes can also inject inline styles or JavaScript:

```php
protected function injectThemeStyles(): string
{
    return <<<HTML
    <link rel="stylesheet" href="{$cssPath}">
    <style>
        :root {
            --custom-primary: #3b82f6;
            --custom-bg: #0f172a;
        }
    </style>
    <script>
        // Optional: Dynamic theme switching logic
    </script>
    HTML;
}
```

---

## Creating Your Own Theme

### Step 1: Create Extension Structure

```bash
extensions/
└── my-theme/
    ├── extension.json
    ├── ExtensionController.php
    └── public/
        ├── theme.css          # Main stylesheet
        ├── fonts/             # Custom fonts (optional)
        └── images/            # Theme images (optional)
```

### Step 2: Configure extension.json

```json
{
    "id": "my-theme",
    "name": "My Custom Theme",
    "description": "A beautiful theme for Pelican",
    "version": "1.0.0",
    "author": "Your Name",
    "types": ["theme"],
    "controller": "ExtensionController"
}
```

### Step 3: Create ExtensionController

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
        // Inject CSS into panel head
        $registry->renderHook(
            PanelsRenderHook::HEAD_END,
            fn() => $this->injectStyles()
        );
    }

    public function boot(): void {}

    public function disable(): void {}

    protected function injectStyles(): string
    {
        $css = asset('extensions/my-theme/theme.css');
        return "<link rel=\"stylesheet\" href=\"{$css}\">";
    }
}
```

### Step 4: Create Your CSS

```css
/* public/theme.css */

/* Override Filament colors */
:root {
    --primary-500: #your-color;
    --gray-900: #your-bg;
}

/* Custom component styling */
.fi-section {
    background: your-gradient;
}

/* Add animations, effects, etc. */
```

---

## Theme Customization Guide

### Overriding Colors

Filament uses CSS custom properties (variables). Override them to change colors:

```css
:root {
    /* Primary colors */
    --primary-50: #eff6ff;
    --primary-500: #3b82f6;
    --primary-900: #1e3a8a;

    /* Background colors */
    --gray-50: #f9fafb;
    --gray-900: #111827;
    --gray-950: #030712;
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

### Dark Mode Specific

```css
.dark {
    --fi-body-bg: #your-dark-bg;
    --fi-text: #your-text-color;
}
```

### Adding Animations

```css
@keyframes your-animation {
    0% { /* ... */ }
    100% { /* ... */ }
}

.your-element {
    animation: your-animation 2s ease infinite;
}
```

---

## Advanced Theme Features

### 1. **Panel-Specific Themes**

Apply styles only to specific panels:

```php
protected function injectStyles(): string
{
    $panel = Filament::getCurrentPanel()->getId();

    if ($panel === 'admin') {
        $css = asset('extensions/my-theme/admin.css');
    } else {
        $css = asset('extensions/my-theme/user.css');
    }

    return "<link rel=\"stylesheet\" href=\"{$css}\">";
}
```

### 2. **Conditional Themes**

Load different themes based on conditions:

```php
protected function injectStyles(): string
{
    $user = user();
    $theme = $user?->preferences['theme'] ?? 'default';

    $css = asset("extensions/my-theme/{$theme}.css");
    return "<link rel=\"stylesheet\" href=\"{$css}\">";
}
```

### 3. **JavaScript Enhancement**

Themes can include JavaScript for dynamic behavior:

```php
protected function injectStyles(): string
{
    return <<<HTML
    <link rel="stylesheet" href="/extensions/my-theme/theme.css">
    <script>
        // Add theme switcher
        document.addEventListener('DOMContentLoaded', () => {
            const themeSwitcher = document.getElementById('theme-switcher');
            themeSwitcher?.addEventListener('click', () => {
                document.body.classList.toggle('light-mode');
            });
        });
    </script>
    HTML;
}
```

### 4. **Custom Fonts**

Include custom fonts in your theme:

```css
/* In your theme.css */
@font-face {
    font-family: 'MyCustomFont';
    src: url('/extensions/my-theme/fonts/custom.woff2') format('woff2');
}

body {
    font-family: 'MyCustomFont', sans-serif;
}
```

### 5. **Dynamic CSS Variables**

Generate CSS variables dynamically:

```php
protected function injectStyles(): string
{
    $primaryColor = config('theme.primary_color', '#3b82f6');

    return <<<HTML
    <style>
        :root {
            --theme-primary: {$primaryColor};
        }
    </style>
    <link rel="stylesheet" href="/extensions/my-theme/theme.css">
    HTML;
}
```

---

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

---

## Theme Development Tips

### 1. **Use Browser DevTools**

- Inspect Filament components to find class names
- Test CSS changes in real-time
- Check for CSS conflicts

### 2. **Preserve Accessibility**

```css
/* Ensure good contrast ratios */
.fi-text {
    color: #e5e7eb; /* Light text on dark background */
}

/* Don't remove focus indicators */
.fi-input:focus {
    outline: 2px solid var(--primary-500);
}
```

### 3. **Respect User Preferences**

```css
/* Support reduced motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

### 4. **Mobile Responsive**

```css
/* Adjust for mobile screens */
@media (max-width: 768px) {
    .fi-sidebar {
        width: 100%;
    }
}
```

### 5. **Performance**

- Minimize CSS file size
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

---

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

---

## Troubleshooting

### CSS Not Loading?

1. Clear browser cache (Ctrl+Shift+R)
2. Check extension is enabled: Admin → Settings → Extensions
3. Verify file exists: `public/extensions/your-theme/theme.css`
4. Check browser console for 404 errors

### Styles Not Applied?

1. Check CSS specificity (Filament styles might be more specific)
2. Use `!important` sparingly when needed
3. Inspect element to see which styles are applied

```css
/* Increase specificity */
.fi-panel .fi-section {
    background: your-color !important;
}
```

### Conflicts with Other Extensions?

Theme extensions load in order. If multiple themes are enabled:
- Later themes override earlier ones
- Use more specific selectors
- Consider disabling conflicting themes

---

## Testing Your Theme

1. **Enable Extension:**
   ```bash
   Admin → Settings → Extensions → Enable "Dark Theme"
   ```

2. **Test All Panels:**
   - Admin Panel
   - App Panel
   - Server Panel

3. **Test All Components:**
   - Tables
   - Forms
   - Buttons
   - Modals
   - Notifications
   - Sidebar navigation

4. **Test Responsive:**
   - Desktop (1920px+)
   - Tablet (768px-1024px)
   - Mobile (< 768px)

5. **Test Accessibility:**
   - Keyboard navigation
   - Screen reader compatibility
   - Color contrast

---

## Resources

- [Filament Documentation](https://filamentphp.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)
- [Web Accessibility](https://www.w3.org/WAI/WCAG21/quickref/)

---

## Contributing

Have a cool theme? Share it with the community!

1. Document your theme features
2. Include screenshots
3. Test on multiple browsers
4. Follow accessibility guidelines

---

## License

This theme is part of the Pelican Panel extension system and follows the same license as Pelican Panel.
