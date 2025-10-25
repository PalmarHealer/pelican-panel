<?php

namespace App\Filament\Server\Pages\Concerns;

use App\Models\Permission;
use Filament\Facades\Filament;

/**
 * Trait for extension server pages to easily display user permissions.
 *
 * Usage:
 *   1. Add `use HasExtensionPermissions;` to your extension server page
 *   2. Call `$this->getExtensionPermissions('your-extension-id')` in mount() or as a property
 *   3. Display the permissions in your Blade view
 */
trait HasExtensionPermissions
{
    public array $userPermissions = [];

    /**
     * Get all extension permissions for the current user.
     *
     * @param  string  $extensionId  The extension identifier (e.g., 'example-extension')
     * @return array Array of permission categories with their granted status
     */
    protected function getExtensionPermissions(string $extensionId): array
    {
        $user = user();
        $server = Filament::getTenant();

        if (!$user || !$server) {
            return [];
        }

        // Get all registered extension permissions from Permission model
        $registeredExtensions = Permission::getExtensionPermissions();

        // Find permissions for this specific extension
        if (!isset($registeredExtensions[$extensionId])) {
            return [];
        }

        $extensionData = $registeredExtensions[$extensionId];
        $categoryName = $extensionData['name'];

        // Build the permission structure for display
        $permissions = [];
        foreach ($extensionData['permissions'] as $action) {
            $permissionKey = "{$categoryName}.{$action}";
            $permissions[$action] = [
                'key' => $permissionKey,
                'description' => $extensionData['descriptions'][$action] ?? ucfirst(str_replace('-', ' ', $action)),
                'granted' => $user->can($permissionKey, $server),
            ];
        }

        return [[
            'category' => $categoryName,
            'description' => $extensionData['descriptions']['desc'] ?? 'Extension permissions',
            'permissions' => $permissions,
            'icon' => $extensionData['icon'] ?? 'tabler-puzzle',
        ]];
    }

    /**
     * Check if user has a specific extension permission.
     *
     * @param  string  $extensionId  The extension identifier
     * @param  string  $permission  The permission key (e.g., 'read', 'write')
     */
    protected function hasExtensionPermission(string $extensionId, string $permission): bool
    {
        $user = user();
        $server = Filament::getTenant();

        if (!$user || !$server) {
            return false;
        }

        $registeredExtensions = Permission::getExtensionPermissions();

        if (!isset($registeredExtensions[$extensionId])) {
            return false;
        }

        $categoryName = $registeredExtensions[$extensionId]['name'];
        $permissionKey = "{$categoryName}.{$permission}";

        return $user->can($permissionKey, $server);
    }

    /**
     * Get all permissions as a flat list for easy checking.
     *
     * @param  string  $extensionId  The extension identifier
     * @return array Associative array of permission key => granted status
     */
    protected function getExtensionPermissionsFlat(string $extensionId): array
    {
        $permissions = $this->getExtensionPermissions($extensionId);

        if (empty($permissions)) {
            return [];
        }

        $flat = [];
        foreach ($permissions as $category) {
            foreach ($category['permissions'] as $action => $data) {
                $flat[$action] = $data['granted'];
            }
        }

        return $flat;
    }
}
