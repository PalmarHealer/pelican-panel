<?php

namespace App\Extensions;

class ExtensionRegistry
{
    /** @var string|null Current extension being registered */
    protected ?string $currentExtension = null;

    /** @var array<string, mixed> */
    protected array $permissions = [];

    /** @var array<string, mixed> */
    protected array $serverPermissions = [];

    /** @var array<string, mixed> */
    protected array $serverPageRestrictions = [];

    /** @var array<string, mixed> */
    protected array $navigationItems = [];

    /** @var array<string, mixed> */
    protected array $userMenuItems = [];

    /** @var array<string, mixed> */
    protected array $renderHooks = [];

    /**
     * Set the current extension being registered.
     *
     * @param  string|null  $extensionId  Extension identifier
     */
    public function setCurrentExtension(?string $extensionId): void
    {
        $this->currentExtension = $extensionId;
    }

    /**
     * Register custom admin/role permissions.
     *
     * @param  array<string, mixed>  $permissions  Array of model => actions
     */
    public function permissions(array $permissions): void
    {
        foreach ($permissions as $model => $actions) {
            if (!isset($this->permissions[$model])) {
                $this->permissions[$model] = [
                    'actions' => [],
                    'extensions' => [],
                ];
            }
            $this->permissions[$model]['actions'] = array_merge(
                $this->permissions[$model]['actions'],
                $actions
            );
            if ($this->currentExtension) {
                $this->permissions[$model]['extensions'][] = $this->currentExtension;
            }
        }
    }

    /**
     * Register custom server panel (subuser) permissions.
     *
     * @param  string  $extensionId  Extension identifier (e.g., 'example-extension')
     * @param  array<string, mixed>  $permissionData  Array with structure:
     *                                                [
     *                                                'name' => 'extension_name',
     *                                                'icon' => 'tabler-icon',
     *                                                'permissions' => ['action1', 'action2'],
     *                                                'descriptions' => [
     *                                                'desc' => 'Category description',
     *                                                'action1' => 'Action 1 description',
     *                                                'action2' => 'Action 2 description',
     *                                                ],
     *                                                'egg_tags' => ['minecraft', 'java'] // Optional: restrict to specific egg tags
     *                                                ]
     */
    public function serverPermissions(string $extensionId, array $permissionData): void
    {
        $this->serverPermissions[$extensionId] = $permissionData;
    }

    /**
     * Register server page restrictions for specific egg tags.
     * This allows extensions to specify which server types (eggs) their pages should be available for.
     *
     * @param  string  $extensionId  Extension identifier (e.g., 'example-extension')
     * @param  string  $pageClass  Fully qualified page class name
     * @param  array<string>  $eggTags  Array of egg tags (e.g., ['minecraft', 'java'])
     */
    public function serverPageRestriction(string $extensionId, string $pageClass, array $eggTags): void
    {
        $this->serverPageRestrictions[$pageClass] = [
            'extension_id' => $extensionId,
            'egg_tags' => $eggTags,
        ];
    }

    /**
     * Register navigation items for specific panels.
     * Note: App panel does not support navigation items (navigation is disabled).
     *
     * @param  string  $itemId  Unique item identifier
     * @param  string|callable  $label  Display label (or callable returning label)
     * @param  array<string, mixed>  $config  Configuration:
     *                                        - 'url': string|callable - URL for the navigation item
     *                                        - 'icon': string - Icon name
     *                                        - 'sort': int - Sort order
     *                                        - 'group': string|null - Navigation group (admin panel only)
     *                                        - 'visible': callable|bool - Visibility condition
     *                                        - 'panels': array - Which panels to register on ['admin' => true, 'server' => true]
     *                                        - 'egg_tags': array|null - Optional: Restrict to specific egg tags (server panel only)
     */
    public function navigationItem(
        string $itemId,
        string|callable $label,
        array $config
    ): void {
        // Remove 'app' panel from config if present (app panel has navigation disabled)
        if (isset($config['panels']['app'])) {
            unset($config['panels']['app']);
        }

        $this->navigationItems[$itemId] = array_merge([
            'label' => $label,
            'panels' => ['admin' => false, 'server' => false],
            'extension_id' => $this->currentExtension,
        ], $config);
    }

    /**
     * Register user menu items for specific panels.
     *
     * @param  string  $itemId  Unique item identifier
     * @param  string|callable  $label  Display label (or callable returning label)
     * @param  array<string, mixed>  $config  Configuration:
     *                                        - 'url': string|callable - URL for the menu item
     *                                        - 'icon': string - Icon name
     *                                        - 'sort': int - Sort order
     *                                        - 'visible': callable|bool - Visibility condition
     *                                        - 'panels': array - Which panels to register on ['admin' => true, 'server' => true, 'app' => true]
     */
    public function userMenuItem(
        string $itemId,
        string|callable $label,
        array $config
    ): void {
        $this->userMenuItems[$itemId] = array_merge([
            'label' => $label,
            'panels' => ['admin' => false, 'server' => false, 'app' => false],
            'extension_id' => $this->currentExtension,
        ], $config);
    }

    /**
     * Register a render hook.
     *
     * @param  string  $hook  The hook name (use PanelsRenderHook constants)
     * @param  callable  $callback  Callback to render content
     * @param  array<string, mixed>  $options  Additional options
     */
    public function renderHook(
        string $hook,
        callable $callback,
        array $options = []
    ): void {
        $this->renderHooks[$hook][] = [
            'callback' => $callback,
            'options' => $options,
            'extension_id' => $this->currentExtension,
        ];
    }

    /**
     * Get all registered admin permissions.
     *
     * @param  string|null  $extensionId  Optional: Filter by extension ID
     * @return array<string, mixed>
     */
    public function getPermissions(?string $extensionId = null): array
    {
        if ($extensionId === null) {
            // Return in old format for backward compatibility
            $result = [];
            foreach ($this->permissions as $model => $data) {
                $result[$model] = $data['actions'];
            }
            return $result;
        }

        $result = [];
        foreach ($this->permissions as $model => $data) {
            if (in_array($extensionId, $data['extensions'] ?? [])) {
                $result[$model] = $data['actions'];
            }
        }
        return $result;
    }

    /**
     * Get all registered server permissions.
     *
     * @param  string|null  $extensionId  Optional: Filter by extension ID
     * @return array<string, mixed>
     */
    public function getServerPermissions(?string $extensionId = null): array
    {
        if ($extensionId === null) {
            return $this->serverPermissions;
        }

        return array_filter($this->serverPermissions, function ($data, $id) use ($extensionId) {
            return $id === $extensionId;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get all registered navigation items.
     *
     * @param  string|null  $extensionId  Optional: Filter by extension ID
     * @return array<string, mixed>
     */
    public function getNavigationItems(?string $extensionId = null): array
    {
        if ($extensionId === null) {
            return $this->navigationItems;
        }

        return array_filter($this->navigationItems, function ($item) use ($extensionId) {
            return ($item['extension_id'] ?? null) === $extensionId;
        });
    }

    /**
     * Get all registered user menu items.
     *
     * @param  string|null  $extensionId  Optional: Filter by extension ID
     * @return array<string, mixed>
     */
    public function getUserMenuItems(?string $extensionId = null): array
    {
        if ($extensionId === null) {
            return $this->userMenuItems;
        }

        return array_filter($this->userMenuItems, function ($item) use ($extensionId) {
            return ($item['extension_id'] ?? null) === $extensionId;
        });
    }

    /**
     * Get all registered render hooks.
     *
     * @param  string|null  $extensionId  Optional: Filter by extension ID
     * @return array<string, mixed>
     */
    public function getRenderHooks(?string $extensionId = null): array
    {
        if ($extensionId === null) {
            return $this->renderHooks;
        }

        $filtered = [];
        foreach ($this->renderHooks as $hook => $callbacks) {
            $filtered[$hook] = array_filter($callbacks, function ($callback) use ($extensionId) {
                return ($callback['extension_id'] ?? null) === $extensionId;
            });
            // Only include the hook if it has callbacks after filtering
            if (empty($filtered[$hook])) {
                unset($filtered[$hook]);
            }
        }

        return $filtered;
    }

    /**
     * Get all registered server page restrictions.
     *
     * @return array<string, mixed>
     */
    public function getServerPageRestrictions(): array
    {
        return $this->serverPageRestrictions;
    }

    /**
     * Check if a server page is allowed for a specific server (based on egg tags).
     *
     * @param  string  $pageClass  Fully qualified page class name
     * @param  \App\Models\Server  $server  The server instance
     * @return bool True if allowed, false if restricted
     */
    public function isServerPageAllowed(string $pageClass, \App\Models\Server $server): bool
    {
        // If no restriction is registered for this page, it's allowed by default
        if (!isset($this->serverPageRestrictions[$pageClass])) {
            return true;
        }

        $restriction = $this->serverPageRestrictions[$pageClass];
        $requiredTags = $restriction['egg_tags'];

        // Get server's egg tags
        $serverEggTags = $server->egg->tags ?? [];

        // Check if any required tag matches any server egg tag
        foreach ($requiredTags as $requiredTag) {
            if (in_array($requiredTag, $serverEggTags)) {
                return true;
            }
        }

        return false;
    }
}
