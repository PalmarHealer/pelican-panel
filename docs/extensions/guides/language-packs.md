# Creating Language Packs

This guide explains how to create language packs for Pelican Panel, allowing you to add new languages or customize existing translations.

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Language Pack Structure](#language-pack-structure)
4. [Translation Files](#translation-files)
5. [Advanced Features](#advanced-features)
6. [Best Practices](#best-practices)
7. [Examples](#examples)

## Overview

Language packs in Pelican Panel allow you to:

- Add support for new languages
- Override existing translations
- Customize text for your brand/organization
- Provide regional dialect variations
- Translate extension-specific content

### How Language Packs Work

Language packs are special extensions that:

1. Provide translation files in the `lang/` directory
2. Can add entirely new languages (e.g., `lang/de/`)
3. Can override existing translations (e.g., `lang/overrides/en/`)
4. Are automatically loaded by the ExtensionManager
5. Don't require special registration code

## Quick Start

### Creating a Basic Language Pack

1. **Create directory structure:**

```bash
mkdir -p extensions/my-langpack/lang/de
cd extensions/my-langpack
```

2. **Create `extension.json`:**

```json
{
    "id": "my-langpack",
    "name": "German Language Pack",
    "description": "German translations for Pelican Panel",
    "version": "1.0.0",
    "author": "Your Name",
    "author_email": "your.email@example.com",
    "types": ["language-pack"],
    "controller": "ExtensionController"
}
```

3. **Create `ExtensionController.php`:**

```php
<?php

namespace Extensions\MyLangpack;

use App\Extensions\Contracts\ExtensionInterface;
use App\Extensions\ExtensionRegistry;

class ExtensionController implements ExtensionInterface
{
    public function register(ExtensionRegistry $registry): void
    {
        // Language pack extensions don't need to register anything
        // Translations are automatically loaded by ExtensionManager
    }

    public function boot(): void
    {
        // Optional: Listen to events, add middleware, etc.
    }

    public function disable(): void
    {
        // Optional: Cleanup when disabled
    }
}
```

4. **Create translation file `lang/de/profile.php`:**

```php
<?php

return [
    'account' => 'Konto',
    'email' => 'E-Mail',
    'password' => 'Passwort',
    'username' => 'Benutzername',
    'timezone' => 'Zeitzone',
    'language' => 'Sprache',
    // Add more translations...
];
```

5. **Enable the language pack:**

```bash
composer dump-autoload
```

Navigate to `/admin/extensions`, scan for extensions, and enable your language pack.

6. **Users can now select the new language** in their profile settings.

## Language Pack Structure

### Directory Layout

```
extensions/my-langpack/
├── extension.json              # Metadata
├── ExtensionController.php     # Controller
├── README.md                   # Documentation
└── lang/                       # Translations
    ├── de/                     # New language (German)
    │   ├── profile.php
    │   ├── activity.php
    │   ├── server.php
    │   └── validation.php
    └── overrides/              # Override existing translations
        ├── en/                 # Override English
        │   ├── profile.php
        │   └── activity.php
        └── de-DE/              # Override German (if it exists)
            └── profile.php
```

### Adding a New Language

Place translation files in `lang/{locale}/`:

```
lang/
├── de/                         # German
├── fr/                         # French
├── es/                         # Spanish
├── ja/                         # Japanese
└── pt-BR/                      # Brazilian Portuguese
```

Each language directory should mirror the structure of the English translations.

### Overriding Existing Translations

Place overrides in `lang/overrides/{locale}/`:

```
lang/overrides/
├── en/                         # Override English
│   ├── profile.php
│   └── server.php
└── de-DE/                      # Override German
    └── profile.php
```

**Important:** Overrides only need to include the keys you want to change, not the entire file.

## Translation Files

### File Structure

Translation files are PHP arrays with key-value pairs:

```php
<?php

return [
    'key' => 'Translation value',
    'nested' => [
        'key' => 'Nested translation',
    ],
    'with_params' => 'Hello :name, you have :count messages',
];
```

### Core Translation Files

Pelican Panel uses these translation files:

#### `profile.php` - User Profile

```php
<?php

return [
    // Account tab
    'account' => 'Account',
    'username' => 'Username',
    'email' => 'Email',
    'password' => 'Password',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_password' => 'Confirm Password',
    'timezone' => 'Timezone',
    'language' => 'Language',

    // OAuth tab
    'oauth' => 'OAuth Connections',
    'link_discord' => 'Link Discord',
    'unlink_discord' => 'Unlink Discord',

    // 2FA tab
    'two_factor' => 'Two-Factor Authentication',
    'enable_2fa' => 'Enable 2FA',
    'disable_2fa' => 'Disable 2FA',

    // API Keys tab
    'api_keys' => 'API Keys',
    'create_api_key' => 'Create API Key',

    // SSH Keys tab
    'ssh_keys' => 'SSH Keys',

    // Activity tab
    'activity' => 'Activity Log',

    // Messages
    'profile_updated' => 'Profile updated successfully',
    'password_changed' => 'Password changed successfully',
];
```

#### `activity.php` - Activity Log

```php
<?php

return [
    'activity_log' => 'Activity Log',
    'event' => 'Event',
    'timestamp' => 'Timestamp',
    'ip_address' => 'IP Address',
    'user_agent' => 'User Agent',

    // Activity events
    'auth:login' => 'User logged in',
    'auth:logout' => 'User logged out',
    'auth:failed' => 'Login attempt failed',
    'server:created' => 'Server created',
    'server:deleted' => 'Server deleted',
    // Add more events...
];
```

#### `server.php` - Server Management

```php
<?php

return [
    'console' => 'Console',
    'files' => 'Files',
    'databases' => 'Databases',
    'backups' => 'Backups',
    'schedules' => 'Schedules',
    'users' => 'Users',
    'settings' => 'Settings',

    // Console
    'start' => 'Start',
    'stop' => 'Stop',
    'restart' => 'Restart',
    'kill' => 'Kill',

    // Files
    'upload' => 'Upload',
    'download' => 'Download',
    'delete' => 'Delete',
    'rename' => 'Rename',
    'new_folder' => 'New Folder',
    'new_file' => 'New File',

    // Messages
    'server_started' => 'Server started successfully',
    'server_stopped' => 'Server stopped successfully',
    'file_uploaded' => 'File uploaded successfully',
];
```

#### `validation.php` - Validation Messages

```php
<?php

return [
    'required' => 'The :attribute field is required',
    'email' => 'The :attribute must be a valid email address',
    'min' => [
        'string' => 'The :attribute must be at least :min characters',
    ],
    'max' => [
        'string' => 'The :attribute must not be greater than :max characters',
    ],
    'unique' => 'The :attribute has already been taken',
    'confirmed' => 'The :attribute confirmation does not match',
];
```

### Accessing Translations

In code, access translations using the `trans()` or `__()` helper:

```php
// Simple translation
echo __('profile.account'); // "Account"

// With parameters
echo __('profile.with_params', ['name' => 'John', 'count' => 5]);
// "Hello John, you have 5 messages"

// Pluralization
echo trans_choice('server.servers', 1); // "server"
echo trans_choice('server.servers', 5); // "servers"
```

In Blade templates:

```blade
{{ __('profile.account') }}

{{ __('profile.with_params', ['name' => $user->name, 'count' => $count]) }}

@lang('profile.account')
```

## Advanced Features

### Pluralization

Laravel supports pluralization in translation strings:

```php
// messages.php
return [
    'servers' => '{0} No servers|{1} One server|[2,*] :count servers',
    'apples' => '{0} There are none|[1,19] There are some|[20,*] There are many',
];
```

Usage:

```php
echo trans_choice('messages.servers', 0);  // "No servers"
echo trans_choice('messages.servers', 1);  // "One server"
echo trans_choice('messages.servers', 10); // "10 servers"
```

### Using JSON Translation Files

For simple key-value translations, use JSON files:

```
lang/
└── de.json
```

```json
{
    "Hello": "Hallo",
    "Goodbye": "Auf Wiedersehen",
    "Welcome back, :name": "Willkommen zurück, :name"
}
```

JSON translations are accessed by their English key:

```php
echo __('Hello'); // "Hallo" (if locale is 'de')
```

### Regional Locales

Support regional variations:

```
lang/
├── en/                         # Default English
├── en-GB/                      # British English
├── en-US/                      # American English
├── pt/                         # Portuguese
└── pt-BR/                      # Brazilian Portuguese
```

### Fallback Mechanism

Laravel uses a fallback mechanism:

1. Tries the specified locale (e.g., `de-DE`)
2. Falls back to parent locale (e.g., `de`)
3. Falls back to app fallback locale (e.g., `en`)

Example:
- User selects `de-DE`
- Translation exists in `lang/de/profile.php` but not `lang/de-DE/profile.php`
- Uses `lang/de/profile.php`
- If not found, uses `lang/en/profile.php`

## Best Practices

### 1. Complete Translations

Ensure all keys from English are translated:

```bash
# Check for missing keys
diff <(grep -r "^    '" lang/en/ | sort) \
     <(grep -r "^    '" lang/de/ | sort)
```

### 2. Maintain Consistency

Use consistent terminology throughout:

```php
// ❌ Inconsistent
'submit' => 'Submit',
'save' => 'Save',
'send' => 'Submit',

// ✅ Consistent
'submit' => 'Submit',
'save' => 'Save',
'send' => 'Send',
```

### 3. Context-Aware Translations

Provide context in comments:

```php
return [
    // Button to save changes
    'save' => 'Save',

    // Message shown after saving
    'saved' => 'Changes saved successfully',

    // Used in confirmation dialog
    'confirm_save' => 'Are you sure you want to save these changes?',
];
```

### 4. Handle Parameters

Always preserve parameters in translations:

```php
// English
'welcome' => 'Welcome, :name!',

// German
'welcome' => 'Willkommen, :name!',

// ❌ Wrong - missing parameter
'welcome' => 'Willkommen!',
```

### 5. Respect Grammar Rules

Different languages have different grammar:

```php
// English
'files_selected' => ':count files selected',

// Russian (different pluralization rules)
'files_selected' => '{1} Выбран :count файл|{2,3,4} Выбрано :count файла|[5,*] Выбрано :count файлов',
```

### 6. Test All Translations

- Test with different user roles
- Test with different data (short/long strings)
- Check for truncation in UI
- Verify special characters display correctly

### 7. Document Your Language Pack

Include a README.md:

```markdown
# German Language Pack

Complete German translations for Pelican Panel.

## Coverage

- ✅ Profile page
- ✅ Server management
- ✅ Admin panel
- ✅ Activity logs
- ⏳ Email templates (coming soon)

## Contributors

- John Doe (@johndoe)
- Jane Smith (@janesmith)

## Installation

Enable this extension in `/admin/extensions`.
Users can select German in their profile settings.
```

## Examples

### Example 1: Simple Override Pack

Override just a few English strings:

```json
{
    "id": "custom-branding",
    "name": "Custom Branding Language Pack",
    "description": "Customizes certain terms for our organization",
    "types": ["language-pack"]
}
```

```php
// lang/overrides/en/server.php
<?php

return [
    // Change "Server" to "Instance" throughout the panel
    'server' => 'Instance',
    'servers' => 'Instances',
    'create_server' => 'Create Instance',
];
```

### Example 2: Complete New Language

Add full Spanish support:

```
lang/es/
├── profile.php
├── activity.php
├── server.php
├── admin.php
├── auth.php
└── validation.php
```

```php
// lang/es/profile.php
<?php

return [
    'account' => 'Cuenta',
    'username' => 'Nombre de usuario',
    'email' => 'Correo electrónico',
    'password' => 'Contraseña',
    'timezone' => 'Zona horaria',
    'language' => 'Idioma',
    'profile_updated' => 'Perfil actualizado exitosamente',
];
```

### Example 3: Regional Variation

Provide British English variations:

```php
// lang/overrides/en-GB/validation.php
<?php

return [
    'required' => 'The :attribute field is required',
    'email' => 'The :attribute must be a valid e-mail address', // "e-mail" vs "email"
];
```

```php
// lang/overrides/en-GB/server.php
<?php

return [
    'optimize' => 'Optimise', // British spelling
    'color' => 'Colour',
];
```

### Example 4: Multi-Language Pack

Support multiple languages in one extension:

```
extensions/european-languages/
└── lang/
    ├── de/          # German
    ├── fr/          # French
    ├── es/          # Spanish
    ├── it/          # Italian
    └── pt/          # Portuguese
```

## Troubleshooting

### Translations Not Appearing

1. **Verify language is enabled:**
   - Check extension is enabled in `/admin/extensions`
   - Check files are in `resources/lang/extensions/your-langpack/`

2. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Check locale is set:**
   ```php
   echo app()->getLocale(); // Should show your language code
   ```

4. **Verify file structure:**
   - Files must be in `lang/{locale}/` for new languages
   - Files must be in `lang/overrides/{locale}/` for overrides

### Overrides Not Working

1. **Check override path:**
   - Must be in `lang/overrides/{locale}/filename.php`
   - Filename must match exactly (e.g., `profile.php` not `Profile.php`)

2. **Verify keys match:**
   - Override keys must match exactly: `'account'` not `'Account'`

3. **Check extension priority:**
   - Extensions are loaded in alphabetical order by ID
   - Later extensions can override earlier ones

### Special Characters Not Displaying

1. **Ensure UTF-8 encoding:**
   ```php
   // At top of translation file
   <?php
   // -*- coding: utf-8 -*-

   return [
       'name' => 'José', // Spanish
       'city' => 'München', // German
   ];
   ```

2. **Check database charset:**
   - Database should use `utf8mb4` charset

### Missing Translation Keys

If a key is missing, Laravel falls back to the key itself:

```php
echo __('missing.key'); // Outputs: "missing.key"
```

Check Laravel logs for missing translation warnings.

## Language Codes

Common ISO 639-1 language codes:

- `en` - English
- `de` - German
- `fr` - French
- `es` - Spanish
- `it` - Italian
- `pt` - Portuguese
- `ru` - Russian
- `ja` - Japanese
- `zh` - Chinese
- `ko` - Korean
- `ar` - Arabic
- `hi` - Hindi

Common regional variations:

- `en-US` - American English
- `en-GB` - British English
- `pt-BR` - Brazilian Portuguese
- `pt-PT` - European Portuguese
- `zh-CN` - Simplified Chinese
- `zh-TW` - Traditional Chinese

## Next Steps

- Review [example-langpack](../../extensions/example-langpack/) for a complete example
- Check out [Extension Development Guide](README.md) for more features
- Explore [API Reference](api-reference.md) for advanced topics

## Resources

- [Laravel Localization Docs](https://laravel.com/docs/localization)
- [ISO 639-1 Language Codes](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
- [CLDR Pluralization Rules](https://cldr.unicode.org/index/cldr-spec/plural-rules)
