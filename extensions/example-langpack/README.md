# 🏴‍☠️ Pirate Language Pack Extension

**Arrr matey!** This extension demonstrates all three capabilities of Pelican's language pack system using pirate-themed translations:

1. **Creating new languages** - Adds Pirate English (en-PIRATE) as a complete new language option
2. **Overriding existing translations** - Adds pirate flair to regular English translations
3. **Registering custom labels** - Provides reusable pirate-themed translation keys for other extensions

## Directory Structure

```
extensions/example-langpack/
├── extension.json              # Extension metadata (🏴‍☠️ Pirate themed!)
├── ExtensionController.php     # Extension controller
├── lang/
│   ├── en-PIRATE/              # NEW LANGUAGE: Complete Pirate English
│   │   ├── activity.php        # "Ye failed to board the ship, landlubber!"
│   │   └── auth.php            # "Board the Ship" instead of "Sign In"
│   ├── overrides/              # OVERRIDES: Add pirate flair to English
│   │   └── en/
│   │       └── activity.php    # "🏴‍☠️ Welcome aboard the ship, captain!"
│   └── en/                     # CUSTOM LABELS: Pirate-themed reusable labels
│       └── messages.php        # trans('example-langpack::messages.welcome')
└── README.md                   # This file (Arrr!)
```

---

## ⚓ Use Case 1: Creating a New Language (Pirate English!)

**Directory:** `lang/en-PIRATE/`

This creates **Pirate English** as a complete new language that users can select in Pelican!

### Example Translations:

```php
// extensions/example-langpack/lang/en-PIRATE/activity.php
return [
    'auth' => [
        'success' => 'Ahoy! Ye be aboard the ship now, matey!',
        'fail' => 'Ye failed to board the ship, landlubber!',
        'password-reset' => 'Yer secret code be reset, arr!',
    ],
    'user' => [
        'api-key' => [
            'create' => 'Forged a new treasure map <b>:identifier</b>',
            'delete' => 'Burned the treasure map <b>:identifier</b>',
        ],
    ],
    'server' => [
        'power' => [
            'start' => 'Raised the sails and set course!',
            'stop' => 'Dropped anchor and stopped the ship',
            'kill' => 'Scuttled the ship process!',
        ],
        'backup' => [
            'create' => 'Buried treasure at <b>:name</b>',
        ],
    ],
];
```

```php
// extensions/example-langpack/lang/en-PIRATE/auth.php
return [
    'sign_in' => 'Board the Ship',
    'sign_out' => 'Abandon Ship',
    'throttle' => 'Too many boardin\' attempts, matey! Try again in :seconds seconds.',
    '2fa_must_be_enabled' => 'Ye must enable the double-lock (two-factor auth) to use this here panel, arr!',
];
```

**Result:** Users can select "Pirate English" in their profile settings and sail the seven seas! 🏴‍☠️⛵

---

## 🔱 Use Case 2: Overriding Existing Translations

**Directory:** `lang/overrides/en/`

Add pirate flair to specific English strings without replacing everything!

### Example:

```php
// extensions/example-langpack/lang/overrides/en/activity.php
return [
    'auth' => [
        'fail' => '⚓ Failed to board - check yer credentials, matey!',
        'success' => '🏴‍☠️ Welcome aboard the ship, captain!',
        'password-reset' => '🔑 Yer password has been reset successfully',
    ],
    'server' => [
        'backup' => [
            'create' => '💰 Created backup treasure "<b>:name</b>"',
            'complete' => '✅ Backup completed successfully',
        ],
    ],
    // Only these specific keys are changed!
    // All other English translations remain normal
];
```

**How it works:**
- Original `lang/en/activity.php` is backed up automatically
- Your overrides are merged with the original
- Only specified keys change (e.g., login success message)
- Other messages remain standard English
- Original restored when extension is disabled

**Result:** Regular English with a touch of pirate personality! 🏴‍☠️

---

## 💎 Use Case 3: Custom Extension Labels

**Directory:** `lang/en/`

Create reusable pirate-themed translations that **any extension** can use!

### Example:

```php
// extensions/example-langpack/lang/en/messages.php
return [
    'welcome' => 'Ahoy! Welcome to the Pirate Language Pack, matey!',
    'greeting' => 'Shiver me timbers!',
    'farewell' => 'Fair winds and following seas!',

    'status' => [
        'enabled' => '🏴‍☠️ Language pack be active and sailin\'!',
        'disabled' => '⚓ Language pack be anchored (inactive)',
    ],

    'actions' => [
        'enable' => 'Raise the sails (enable)',
        'disable' => 'Drop anchor (disable)',
    ],

    'pirate' => [
        'phrases' => [
            'ahoy' => 'Ahoy there!',
            'aye' => 'Aye aye, captain!',
            'arr' => 'Arrr!',
            'yo_ho_ho' => 'Yo ho ho and a bottle of rum!',
        ],
    ],
];
```

### Usage in Blade Templates:

```blade
<!-- In any Blade template -->
<h1>{{ trans('example-langpack::messages.welcome') }}</h1>
<!-- Output: "Ahoy! Welcome to the Pirate Language Pack, matey!" -->

<p>{{ trans('example-langpack::messages.pirate.phrases.ahoy') }}</p>
<!-- Output: "Ahoy there!" -->

<button>{{ trans('example-langpack::messages.actions.enable') }}</button>
<!-- Output: "Raise the sails (enable)" -->
```

### Usage in PHP Code:

```php
// In any extension or controller
$greeting = trans('example-langpack::messages.greeting');
// Returns: "Shiver me timbers!"

$status = trans('example-langpack::messages.status.enabled');
// Returns: "🏴‍☠️ Language pack be active and sailin'!"

// Works with parameters too!
Notification::make()
    ->title(trans('example-langpack::messages.notifications.treasure_found', ['count' => 5]))
    ->send();
// Shows: "💰 Found 5 translation treasures!"
```

**Result:** Sharable pirate vocabulary for the entire extension ecosystem! 🏴‍☠️💰

---

## 🗺️ Creating Your Own Language Pack

### Step 1: Create Extension Structure

```bash
extensions/
└── my-langpack/
    ├── extension.json
    ├── ExtensionController.php
    └── lang/
        ├── fr/                     # New language (French)
        ├── es/                     # New language (Spanish)
        ├── overrides/en/           # Customize English
        └── en/custom.php           # Your custom labels
```

### Step 2: Configure extension.json

```json
{
    "id": "my-langpack",
    "name": "My Custom Language Pack",
    "description": "Adds French, Spanish, and custom translations",
    "version": "1.0.0",
    "author": "Your Name",
    "types": ["language-pack"],
    "controller": "ExtensionController"
}
```

### Step 3: Add Your Translations

**New Language (French):**
```php
// lang/fr/activity.php
return [
    'auth' => [
        'success' => 'Connecté avec succès',
        'fail' => 'Échec de la connexion',
    ],
];
```

**Override English (add branding):**
```php
// lang/overrides/en/activity.php
return [
    'auth' => [
        'success' => 'Welcome to ' . config('app.name') . '! 🎉',
    ],
];
```

**Custom Labels:**
```php
// lang/en/branding.php
return [
    'company_name' => 'My Gaming Company',
    'tagline' => 'The best game hosting ever!',
    'support_message' => 'Need help? Contact support!',
];

// Usage: trans('my-langpack::branding.company_name')
```

---

## 🎭 Real-World Use Cases

### 1. Gaming Community Language Pack
```php
// Add your community's slang and inside jokes
return [
    'welcome' => 'Welcome to the squad!',
    'goodbye' => 'GG WP! See you next game!',
    'status' => [
        'online' => '🟢 In the game',
        'offline' => '🔴 AFK',
    ],
];
```

### 2. Brand-Specific Terminology
```php
// Override technical terms with your brand names
return [
    'server' => [
        'power' => [
            'start' => 'Launching your Game Instance...',
            'stop' => 'Shutting down Game Instance',
        ],
    ],
];
```

### 3. Regional Language Support
```php
// Add complete regional languages
// lang/pt-BR/ - Brazilian Portuguese
// lang/es-MX/ - Mexican Spanish
// lang/zh-CN/ - Simplified Chinese
```

### 4. Child-Friendly Mode
```php
// Replace technical jargon with kid-friendly terms
return [
    'auth' => [
        'fail' => '❌ Oops! Wrong password, try again!',
        'success' => '✅ You\'re in! Let\'s play!',
    ],
];
```

---

## 🧪 Testing Your Language Pack

### 1. Enable the Extension

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
```

Go to **Admin Panel → Settings → Extensions** and enable your language pack.

### 2. Test New Languages

1. Go to your profile settings
2. Look for language selector
3. You should see your new language (e.g., "Pirate English")
4. Select it and browse the panel!

### 3. Test Overrides

1. Keep English as your language
2. Look at activity logs, auth pages, etc.
3. You should see your customized strings

### 4. Test Custom Labels

In any Blade template:
```blade
{{ trans('your-extension::file.key') }}
```

---

## 🎯 Best Practices

### 1. **Use Descriptive Namespaces**
```php
// Good
trans('my-brand::messages.welcome')

// Bad (conflicts with core)
trans('messages.welcome')
```

### 2. **Keep Overrides Minimal**
Only override what you need to change:
```php
// Good - override 2-3 important strings
return [
    'auth' => ['success' => 'Custom message'],
];

// Bad - override everything (use new language instead)
return [
    'auth' => [...100 keys...],
    'server' => [...200 keys...],
];
```

### 3. **Provide Complete New Languages**
If creating a new language, translate all important files:
- ✅ `activity.php` (most visible)
- ✅ `auth.php` (login/logout)
- ✅ `exceptions.php` (errors)
- ✅ `admin/*.php` (admin panel)
- ✅ `server/*.php` (server panel)

### 4. **Document Your Custom Labels**
```php
return [
    // Available to all extensions
    'common' => [
        'yes' => 'Aye!',        // Generic confirmation
        'no' => 'Nay!',         // Generic denial
    ],

    // Specific to pirate theme
    'pirate' => [
        'greeting' => 'Ahoy!',  // Pirate hello
    ],
];
```

### 5. **Use Parameters for Flexibility**
```php
// Good - flexible
'welcome' => 'Ahoy, :name! Ye have :count ships.',

// Usage
trans('pack::welcome', ['name' => 'Captain Jack', 'count' => 3])
// "Ahoy, Captain Jack! Ye have 3 ships."
```

---

## 🔧 Advanced Features

### Multi-Type Extensions

Combine language packs with plugins:

```json
{
    "types": ["plugin", "language-pack"]
}
```

This allows you to:
- Add admin pages/resources (plugin)
- Include translations for your UI (language pack)
- Provide labels for other extensions (custom namespace)

### Conditional Translations

```php
// In your extension controller
public function boot(): void
{
    if (config('app.env') === 'local') {
        // Load debug translations
        app('translator')->addNamespace('debug', ...);
    }
}
```

### Dynamic Translation Loading

```php
// Load translations based on user preferences
$locale = user()->preferences['pirate_mode'] ? 'en-PIRATE' : 'en';
app()->setLocale($locale);
```

---

## 🐛 Troubleshooting

### Translations Not Showing?

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# Verify extension is enabled
# Admin Panel → Settings → Extensions
```

### New Language Not Appearing?

1. Check directory name is valid locale code (e.g., `en-PIRATE`, `fr`, `es-ES`)
2. Directory must be directly under `lang/` (not in `overrides/`)
3. Ensure at least one translation file exists
4. Scan for extensions in admin panel

### Override Not Working?

1. File must be in `overrides/{locale}/` directory
2. File name must match exactly (e.g., `activity.php`)
3. Array structure must match original
4. Check backup file was created: `activity.php.backup-before-your-ext`

### Custom Labels Not Found?

```php
// Make sure to use the extension namespace
trans('example-langpack::messages.welcome')  // ✅ Correct
trans('messages.welcome')                     // ❌ Wrong (core namespace)
```

---

## 🏴‍☠️ Example: Using Pirate Labels in Your Extension

```php
// extensions/my-plugin/admin/Pages/Dashboard.php

class Dashboard extends Page
{
    public function getHeading(): string
    {
        // Use pirate pack's custom labels
        return trans('example-langpack::messages.welcome');
        // Output: "Ahoy! Welcome to the Pirate Language Pack, matey!"
    }

    protected function getActions(): array
    {
        return [
            Action::make('configure')
                ->label(trans('example-langpack::messages.actions.configure'))
                // Output: "Check the ship's map (configure)"
                ->icon('tabler-map'),
        ];
    }
}
```

```blade
<!-- In your extension's Blade views -->
<div class="pirate-panel">
    <h2>{{ trans('example-langpack::messages.pirate.phrases.ahoy') }}</h2>
    <!-- Output: "Ahoy there!" -->

    <p>{{ trans('example-langpack::messages.farewell') }}</p>
    <!-- Output: "Fair winds and following seas!" -->
</div>
```

---

## 📚 More Examples

### Complete Pirate Activity Log

When you enable this pack and select Pirate English:

- **Login Success:** "Ahoy! Ye be aboard the ship now, matey!"
- **Login Failure:** "Ye failed to board the ship, landlubber!"
- **Create API Key:** "Forged a new treasure map"
- **Start Server:** "Raised the sails and set course!"
- **Create Backup:** "Buried treasure at location"
- **Password Reset:** "Yer secret code be reset, arr!"

### Pirate English + Regular English

You can even keep English but add pirate flair with overrides:
- Most messages stay normal English
- Important messages get pirate style:
  - "🏴‍☠️ Welcome aboard the ship, captain!" (login)
  - "💰 Created backup treasure" (backups)
  - "⚓ Failed to board" (login fail)

---

## 🎉 Have Fun!

Language packs are a powerful way to:
- 🌍 Add international support
- 🎨 Customize your panel's personality
- 🤝 Share translations with other extensions
- 😄 Have fun with creative languages like Pirate English!

**Fair winds and following seas, matey! 🏴‍☠️⛵**

---

## Technical Notes

### Backup System
Overrides create backups automatically:
```
lang/en/activity.php
lang/en/activity.php.backup-before-example-langpack
```

### Merge Strategy
Uses `array_replace_recursive()` for nested merging.

### Namespace Registration
```php
app('translator')->addNamespace('example-langpack', 'extensions/example-langpack/lang');
```

Happens automatically during extension boot!
