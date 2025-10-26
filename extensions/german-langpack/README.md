# German Language Pack - Conflict Detection Demo

This extension demonstrates the **conflict detection system** for language pack overrides.

## What This Extension Does

This extension attempts to override:
1. `en/activity.php` - **WILL CONFLICT** with Pirate Language Pack
2. `de-DE/profile.php` - **NO CONFLICT** (different language)

## Testing Conflict Detection

### Scenario 1: Enable German Pack First

```bash
# 1. Enable German Language Pack
# Admin → Settings → Extensions → Enable "German Language Pack"
# ✅ Success! German pack overrides en/activity.php

# 2. Try to enable Pirate Language Pack
# Admin → Settings → Extensions → Enable "Pirate Language Pack"
# ❌ Error: "Language pack conflict detected: 'en/activity.php' is already
#           overridden by 'German Language Pack'. Please disable the
#           conflicting extension(s) first before enabling this extension."
```

### Scenario 2: Enable Pirate Pack First

```bash
# 1. Enable Pirate Language Pack
# Admin → Settings → Extensions → Enable "Pirate Language Pack"
# ✅ Success! Pirate pack overrides en/activity.php

# 2. Try to enable German Language Pack
# Admin → Settings → Extensions → Enable "German Language Pack"
# ❌ Error: Conflict detected!
```

### Scenario 3: No Conflict (Different Files)

If extensions override **different** language files, there's no conflict:

```
Extension A: overrides en/activity.php
Extension B: overrides en/auth.php
Result: ✅ Both can be enabled simultaneously
```

## How Conflict Detection Works

### 1. Override Tracking

When an extension is enabled, the system tracks which files it overrides:

```php
// Database: extensions table
{
    "identifier": "example-langpack",
    "language_overrides": [
        "en/activity.php",
        "en/auth.php"
    ]
}
```

### 2. Conflict Check

Before enabling a new extension, the system checks if any enabled extension already owns the files:

```php
// Pseudo-code
foreach (override_files as file) {
    blocking_extension = findExtensionOverridingFile(file);
    if (blocking_extension) {
        throw ConflictException;
    }
}
```

### 3. Selective Restoration

When an extension is disabled, **only its overrides** are restored:

```bash
# Extension A overrides: en/activity.php, en/auth.php
# Extension B overrides: en/profile.php

# Disable Extension A:
# - Restores en/activity.php from backup
# - Restores en/auth.php from backup
# - Does NOT touch en/profile.php (belongs to Extension B)
```

## Benefits

### ✅ Prevents Data Loss
- Can't accidentally overwrite another extension's overrides
- Each extension's changes are tracked

### ✅ Clean Rollback
- Disabling an extension only restores what it changed
- Other extensions' overrides remain intact

### ✅ Clear Error Messages
- Users know exactly which extension is blocking
- Easy to resolve: disable the blocking extension first

### ✅ Granular Control
- Different extensions can override different files in the same language
- Extensions can override different languages without conflict

## Testing Steps

1. **Enable German Language Pack:**
   ```bash
   # Go to Admin Panel → Settings → Extensions
   # Click "Scan for Extensions"
   # Enable "German Language Pack"
   ```

2. **Check Activity Log:**
   ```bash
   # Log in and check activity log
   # Should see: "🇩🇪 Successfully logged in - Willkommen!"
   ```

3. **Try to Enable Pirate Pack:**
   ```bash
   # Try to enable "Pirate Language Pack"
   # Should see error toast with conflict message
   ```

4. **Disable German Pack:**
   ```bash
   # Disable "German Language Pack"
   # Activity log should return to normal English
   ```

5. **Enable Pirate Pack:**
   ```bash
   # Now enable "Pirate Language Pack"
   # Should succeed!
   # Activity log shows: "🏴‍☠️ Welcome aboard the ship, captain!"
   ```

## Technical Implementation

### Conflict Detection (ExtensionManager.php)

```php
protected function findExtensionOverridingFile(string $fileKey): ?Extension
{
    $extensions = Extension::where('enabled', true)
        ->whereNotNull('language_overrides')
        ->get();

    foreach ($extensions as $extension) {
        if (in_array($fileKey, $extension->language_overrides)) {
            return $extension;
        }
    }

    return null;
}
```

### Tracking Overrides

```php
// After successful override
$extension->update([
    'language_overrides' => ['en/activity.php', 'en/auth.php']
]);
```

### Selective Restoration

```php
protected function unpublishLanguageOverrides(string $extensionId): void
{
    $extension = Extension::find($extensionId);
    $trackedOverrides = $extension->language_overrides ?? [];

    foreach ($trackedOverrides as $overrideKey) {
        // Only restore files that THIS extension owns
        restoreBackup($overrideKey, $extensionId);
    }

    $extension->update(['language_overrides' => null]);
}
```

## Error Messages

### Conflict Detected
```
Language pack conflict detected: 'en/activity.php' is already overridden
by 'German Language Pack'. Please disable the conflicting extension(s)
first before enabling this extension.
```

### Multiple Conflicts
```
Language pack conflict detected: 'en/activity.php' is already overridden
by 'Pirate Language Pack', 'de-DE/auth.php' is already overridden by
'German Language Pack'. Please disable the conflicting extension(s) first
before enabling this extension.
```

## Advanced Usage

### Checking for Conflicts Before Enable

Extensions can check for potential conflicts programmatically:

```php
$manager = app(ExtensionManager::class);
$conflicts = $manager->checkLanguageConflicts('my-extension');

if (!empty($conflicts)) {
    // Handle conflicts
}
```

### Listing All Overrides

```php
$extensions = Extension::whereNotNull('language_overrides')->get();

foreach ($extensions as $ext) {
    echo "{$ext->name} overrides:\n";
    foreach ($ext->language_overrides as $file) {
        echo "  - $file\n";
    }
}
```

## Best Practices

1. **Document Your Overrides**: Clearly state which files your extension overrides
2. **Minimize Conflicts**: Only override what you absolutely need
3. **Use Namespaces**: Prefer custom namespaced labels over overrides when possible
4. **Test Scenarios**: Test both enabling first and enabling after conflicts
5. **Clear Messages**: Provide helpful messages when conflicts occur

## Comparison: With vs Without Conflict Detection

### Without Conflict Detection ❌
```
1. Enable Extension A (overrides en/activity.php)
2. Enable Extension B (silently overwrites Extension A's changes)
3. Disable Extension B (restores original, loses Extension A's changes!)
Result: Extension A's overrides are lost!
```

### With Conflict Detection ✅
```
1. Enable Extension A (overrides en/activity.php) ✅
2. Try to enable Extension B (conflict detected) ❌
3. User must disable Extension A first
4. Enable Extension B (no conflicts) ✅
5. Disable Extension B (only restores what it changed) ✅
Result: Clean, predictable behavior!
```

## Conclusion

The conflict detection system ensures:
- **Safety**: Can't accidentally break other extensions
- **Predictability**: Always know what will happen
- **Reversibility**: Clean rollback of changes
- **Clarity**: Clear error messages guide users

This makes the language pack system production-ready for environments with multiple extensions!
